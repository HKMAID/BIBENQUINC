<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';

initSession();
$authUser = currentUser();

if (!$authUser) {
    echo json_encode(['ok' => false, 'error' => 'Non connecté. Veuillez vous identifier.']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['ok' => false, 'error' => 'Panier vide ou données invalides.']);
    exit;
}

$cart         = $data['cart'];
$deliveryMode = in_array($data['deliveryMode'] ?? '', ['home', 'zrexpress'], true)
                ? $data['deliveryMode'] : 'home';
$wilaya       = htmlspecialchars(trim($data['wilaya'] ?? ''), ENT_QUOTES);

// Table de correspondance wilaya -> frais (source de vérité côté serveur)
// Identique au select HTML dans catalogue.php
$wilayaFeeMap = [
    '06' => 200,  // Béjaïa
    '15' => 400,  // Tizi-Ouzou
    '10' => 400,  // Bouira
    '16' => 400,  // Alger
    '18' => 400,  // Jijel
    '34' => 400,  // Bordj Bou Arréridj
    '19' => 400,  // Sétif
];

// Validation stricte mode <-> frais (indépendant du client JS)
if ($deliveryMode === 'home') {
    $deliveryFee = 600;
} elseif ($deliveryMode === 'zrexpress') {
    if (!isset($wilayaFeeMap[$wilaya])) {
        echo json_encode(['ok' => false, 'error' => 'Wilaya non couverte par ZrExpress.']);
        exit;
    }
    $deliveryFee = $wilayaFeeMap[$wilaya];
} else {
    echo json_encode(['ok' => false, 'error' => 'Mode de livraison invalide.']);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    $articlesSubTotal = 0;
    $cartWithIds      = [];

    foreach ($cart as $item) {
        $name = trim((string) ($item['name'] ?? ''));
        $qty  = max(1, (int) ($item['qty']  ?? 1));

        if ($name === '' || $qty < 1) continue;

        // Prix recalculé depuis la DB — le prix envoyé par le JS est ignoré
        $stmt = $pdo->prepare(
            'SELECT idArt, prix, nbDispo, intitule FROM article WHERE intitule = ? LIMIT 1'
        );
        $stmt->execute([$name]);
        $article = $stmt->fetch();

        if (!$article) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => "Article introuvable : {$name}"]);
            exit;
        }
        if ((int)$article['nbDispo'] < $qty) {
            $pdo->rollBack();
            echo json_encode([
                'ok'    => false,
                'error' => "Stock insuffisant pour « {$name} » (disponible : {$article['nbDispo']})",
            ]);
            exit;
        }

        $dbPrice           = (int) $article['prix'];
        $articlesSubTotal += $dbPrice * $qty;
        $cartWithIds[]     = [
            'idArt' => (int) $article['idArt'],
            'qty'   => $qty,
            'price' => $dbPrice,
            'name'  => $article['intitule'],
        ];
    }

    if (empty($cartWithIds)) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'Aucun article valide dans le panier.']);
        exit;
    }

    $totalWithDelivery = $articlesSubTotal + $deliveryFee;

    // INSERT achat — total inclut les frais de livraison, mode et wilaya stockés
    $pdo->prepare(
        'INSERT INTO achat (idClient, total, dateAchat, modeLivraison, wilaya) VALUES (?, ?, NOW(), ?, ?)'
    )->execute([$authUser['id'], $totalWithDelivery, $deliveryMode, $wilaya !== '' ? $wilaya : null]);
    $idAchat = (int) $pdo->lastInsertId();

    // INSERT article_achat + décrémentation stock atomique
    $stmtArt   = $pdo->prepare(
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
            echo json_encode([
                'ok'    => false,
                'error' => "Stock épuisé pour « {$ci['name']} » au moment de la validation.",
            ]);
            exit;
        }
    }

    // Facture générée automatiquement à l'état « non payée »
    $pdo->prepare("INSERT INTO facture (idAchat, etat, dateFac) VALUES (?, 'non payée', NOW())")
        ->execute([$idAchat]);

    $pdo->commit();

    $orderId = 'BQ-' . date('Y') . '-' . str_pad((string)$idAchat, 4, '0', STR_PAD_LEFT);
    echo json_encode([
        'ok'          => true,
        'orderId'     => $orderId,
        'idAchat'     => $idAchat,
        'deliveryFee' => $deliveryFee,
        'subtotal'    => $articlesSubTotal,
        'total'       => $totalWithDelivery,
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Checkout error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur. Veuillez réessayer.']);
}
