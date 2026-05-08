<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/products.php';

initSession();
$authUser = currentUser();

if (!$authUser) {
    echo json_encode(['ok' => false, 'error' => 'Non connecté. Veuillez vous identifier.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['ok' => false, 'error' => 'Panier vide ou données invalides.']);
    exit;
}

$cart         = $data['cart'];
$deliveryMode = in_array($data['deliveryMode'] ?? '', ['home', 'zrexpress'], true) ? $data['deliveryMode'] : 'home';
$deliveryFee  = (int) ($data['deliveryFee'] ?? 600);
$wilaya       = htmlspecialchars(trim($data['wilaya'] ?? ''), ENT_QUOTES);

// Validate delivery fee
$validFees = [0, 200, 400, 600];
if (!in_array($deliveryFee, $validFees, true)) {
    echo json_encode(['ok' => false, 'error' => 'Frais de livraison invalides.']);
    exit;
}

// Validate wilaya for ZrExpress
$validWilayas = ['06', '15', '10', '16', '18', '34', '19'];
if ($deliveryMode === 'zrexpress' && !in_array($wilaya, $validWilayas, true)) {
    echo json_encode(['ok' => false, 'error' => 'Wilaya non couverte par ZrExpress.']);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // Validate cart items against DB stock
    $articlesSubTotal = 0;
    $cartWithIds = [];

    foreach ($cart as $item) {
        $name  = trim((string) ($item['name'] ?? ''));
        $qty   = max(1, (int) ($item['qty'] ?? 1));
        $price = (int) ($item['price'] ?? 0);

        if ($name === '' || $qty < 1 || $price < 0) continue;

        // Fetch article from DB
        $stmt = $pdo->prepare('SELECT idArt, prix, nbDispo, intitule FROM article WHERE intitule = ? LIMIT 1');
        $stmt->execute([$name]);
        $article = $stmt->fetch();

        if (!$article) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => "Article introuvable : {$name}"]);
            exit;
        }
        if ($article['nbDispo'] < $qty) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => "Stock insuffisant pour « {$name} » (disponible : {$article['nbDispo']})"]);
            exit;
        }

        $articlesSubTotal += $price * $qty;
        $cartWithIds[] = ['idArt' => $article['idArt'], 'qty' => $qty, 'price' => $price, 'name' => $name];
    }

    if (empty($cartWithIds)) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'Aucun article valide dans le panier.']);
        exit;
    }

    $totalWithDelivery = $articlesSubTotal + $deliveryFee;

    // 1. Insert achat
    $stmtAchat = $pdo->prepare(
        'INSERT INTO achat (idClient, total, dateAchat) VALUES (?, ?, NOW())'
    );
    $stmtAchat->execute([$authUser['id'], $totalWithDelivery]);
    $idAchat = (int) $pdo->lastInsertId();

    // 2. Insert article_achat + deduct stock
    $stmtArt = $pdo->prepare(
        'INSERT INTO article_achat (idAchat, idArticle, quantite) VALUES (?, ?, ?)'
    );
    $stmtStock = $pdo->prepare(
        'UPDATE article SET nbDispo = nbDispo - ? WHERE idArt = ? AND nbDispo >= ?'
    );
    foreach ($cartWithIds as $ci) {
        $stmtArt->execute([$idAchat, $ci['idArt'], $ci['qty']]);
        $stmtStock->execute([$ci['qty'], $ci['idArt'], $ci['qty']]);
        if ($stmtStock->rowCount() === 0) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => "Stock épuisé pour « {$ci['name']} » au moment de la validation."]);
            exit;
        }
    }

    // 3. Auto-generate facture
    $pdo->prepare("INSERT INTO facture (idAchat, etat, dateFac) VALUES (?, 'non payée', NOW())")->execute([$idAchat]);

    $pdo->commit();

    $orderId = 'BQ-' . date('Y') . '-' . str_pad((string)$idAchat, 4, '0', STR_PAD_LEFT);
    echo json_encode(['ok' => true, 'orderId' => $orderId, 'idAchat' => $idAchat]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Checkout error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur. Veuillez réessayer.']);
}
