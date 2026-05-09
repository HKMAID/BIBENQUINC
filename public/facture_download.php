<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';

initSession();
$authUser = currentUser();

if (!$authUser || ($authUser['type'] ?? '') !== 'professionnel') {
    http_response_code(403);
    exit('Accès refusé.');
}

$idFac = (int) ($_GET['idFac'] ?? 0);
if ($idFac <= 0) {
    http_response_code(400);
    exit('Identifiant de facture invalide.');
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare("
        SELECT f.idFac, f.etat, f.dateFac,
               a.idAchat, a.total, a.dateAchat, a.modeLivraison, a.wilaya,
               u.nom, u.prenom, u.email, u.numTel
        FROM facture f
        JOIN achat a       ON f.idAchat  = a.idAchat
        JOIN utilisateur u ON a.idClient = u.idUtili
        WHERE f.idFac = ?
        LIMIT 1
    ");
    $stmt->execute([$idFac]);
    $fac = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fac) {
        http_response_code(404);
        exit('Facture introuvable.');
    }

    $stmtArt = $pdo->prepare("
        SELECT ar.intitule, aa.quantite, ar.prix
        FROM article_achat aa
        JOIN article ar ON aa.idArticle = ar.idArt
        WHERE aa.idAchat = ?
        ORDER BY ar.intitule
    ");
    $stmtArt->execute([$fac['idAchat']]);
    $articles = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('facture_download error: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur serveur.');
}

$facRef  = 'FAC-' . str_pad((string)$fac['idFac'], 4, '0', STR_PAD_LEFT);
$achRef  = 'BQ-' . date('Y', strtotime($fac['dateAchat'])) . '-' . str_pad((string)$fac['idAchat'], 4, '0', STR_PAD_LEFT);
$dateFac = date('d/m/Y H:i', strtotime($fac['dateFac']));
$dateAch = date('d/m/Y H:i', strtotime($fac['dateAchat']));
$client  = $fac['prenom'] . ' ' . $fac['nom'];
$etat    = ucfirst($fac['etat']);

$subtotal = 0;
foreach ($articles as $art) {
    $subtotal += (int)$art['prix'] * (int)$art['quantite'];
}
$total       = (int)$fac['total'];
$fraisLiv    = $total - $subtotal;

$modeLabel = match($fac['modeLivraison'] ?? '') {
    'home'      => 'Livraison a domicile',
    'zrexpress' => 'Bureau ZrExpress' . (!empty($fac['wilaya']) ? ' - Wilaya ' . $fac['wilaya'] : ''),
    default     => 'Non specifie',
};

$W = 52; 
$sep   = str_repeat('=', $W);
$line  = str_repeat('-', $W);

function padRow(string $label, string $value, int $w): string {
    $dots = max(1, $w - strlen($label) - strlen($value));
    return $label . str_repeat('.', $dots) . $value;
}

$out  = $sep . "\n";
$out .= str_pad('BIBENQUINC', $W, ' ', STR_PAD_BOTH) . "\n";
$out .= str_pad('Quincaillerie & Electricite', $W, ' ', STR_PAD_BOTH) . "\n";
$out .= $sep . "\n\n";

$out .= padRow('N Facture   : ', $facRef,  $W) . "\n";
$out .= padRow('N Commande  : ', $achRef,  $W) . "\n";
$out .= padRow('Date facture: ', $dateFac, $W) . "\n";
$out .= padRow('Date achat  : ', $dateAch, $W) . "\n";
$out .= padRow('Statut      : ', $etat,    $W) . "\n\n";

$out .= $line . "\n";
$out .= " CLIENT\n";
$out .= $line . "\n";
$out .= padRow('Nom    : ', $client,           $W) . "\n";
$out .= padRow('Email  : ', $fac['email'],     $W) . "\n";
$out .= padRow('Tel    : ', $fac['numTel'],    $W) . "\n\n";

$out .= $line . "\n";
$out .= " ARTICLES COMMANDES\n";
$out .= $line . "\n";

foreach ($articles as $art) {
    $qty    = (int)$art['quantite'];
    $prix   = (int)$art['prix'];
    $total_ligne = number_format($prix * $qty, 0, ',', ' ') . ' DA';
    $desc   = mb_strimwidth($art['intitule'], 0, 30, '…');
    $left   = $desc . ' x' . $qty;
    $right  = str_pad($total_ligne, 12, ' ', STR_PAD_LEFT);
    $mid    = max(1, $W - strlen($left) - strlen($right));
    $out   .= $left . str_repeat(' ', $mid) . $right . "\n";
}

$out .= "\n" . $line . "\n";
$out .= padRow('Sous-total articles : ', number_format($subtotal, 0, ',', ' ') . ' DA', $W) . "\n";
$out .= padRow('Mode livraison      : ', $modeLabel, $W) . "\n";

if ($fraisLiv > 0) {
    $out .= padRow('Frais de livraison  : ', number_format($fraisLiv, 0, ',', ' ') . ' DA', $W) . "\n";
}

$out .= $line . "\n";
$out .= padRow('TOTAL               : ', number_format($total, 0, ',', ' ') . ' DA', $W) . "\n";
$out .= $sep . "\n\n";
$out .= str_pad('Merci pour votre confiance !', $W, ' ', STR_PAD_BOTH) . "\n";
$out .= str_pad('BibenQuinc - 2026', $W, ' ', STR_PAD_BOTH) . "\n";

$filename = $facRef . '_' . date('Ymd', strtotime($fac['dateFac'])) . '.txt';

header('Content-Type: text/plain; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($out));
header('Cache-Control: no-cache, no-store');

echo $out;
