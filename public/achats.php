<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/products.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

if (!$authUser) {
    header('Location: cnx.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$pdo = getPDO();

$stmt = $pdo->prepare('
    SELECT a.idAchat, a.dateAchat, a.total
    FROM achat a
    WHERE a.idClient = ?
');
$stmt->execute([$authUser['id']]);
$db_orders = $stmt->fetchAll();

$orders = [];
foreach ($db_orders as $row) {
    // Récupérer les articles pour chaque achat
    $stmtItems = $pdo->prepare('
        SELECT aa.quantite, art.intitule, art.prix, art.photo
        FROM article_achat aa
        JOIN article art ON aa.idArticle = art.idArt
        WHERE aa.idAchat = ?
    ');
    $stmtItems->execute([$row['idAchat']]);
    $items = [];
    foreach ($stmtItems->fetchAll() as $item) {
        $items[] = [
            'name'  => $item['intitule'],
            'price' => (int) $item['prix'],
            'qty'   => (int) $item['quantite'],
            'image' => $item['photo']
        ];
    }

    $orders[] = [
        'id'     => 'BQ-' . date('Y', strtotime($row['dateAchat'])) . '-' . str_pad((string)$row['idAchat'], 3, '0', STR_PAD_LEFT),
        'date'   => $row['dateAchat'],
        'items'  => $items,
        'total'  => (int) $row['total'],
        'status' => 'livré' 
    ];
}


$sort = $_GET['sort'] ?? 'date_desc';
usort($orders, function($a, $b) use ($sort) {
    return match($sort) {
        'price_asc'  => $a['total'] - $b['total'],
        'price_desc' => $b['total'] - $a['total'],
        'date_asc'   => strcmp($a['date'], $b['date']),
        default      => strcmp($b['date'], $a['date']), // date_desc
    };
});

$fmt = fn($n) => number_format($n, 0, ',', ' ');
$fmtDate = fn($d) => (new DateTime($d))->format('d/m/Y à H:i');

$statusClass = ['livré'=>'status-ok','en cours'=>'status-pending','annulé'=>'status-cancelled'];

$flash    = getFlash();
$pageTitle = 'Mes achats — BibenQuinc';
$pageDesc  = 'Historique de vos commandes BibenQuinc.';
$activeNav = 'achats';
$extraCss  = ['views/style/profile.css'];

include __DIR__ . '/includes/header.php';
?>

  <div class="acc-banner">
    <div class="acc-banner-inner">
      <div class="acc-avatar"><?= mb_strtoupper(mb_substr($authName, 0, 1)) ?></div>
      <div>
        <h1 class="acc-banner-title">Mes achats</h1>
        <p class="acc-banner-sub"><?= count($orders) ?> commande<?= count($orders) !== 1 ? 's' : '' ?></p>
      </div>
    </div>
  </div>

  <main class="acc-main">
    <div class="orders-toolbar">
      <span class="orders-count"><?= count($orders) ?> commande<?= count($orders) !== 1 ? 's' : '' ?></span>
      <div class="sort-pills">
        <span class="sort-label">Trier par :</span>
        <?php
        $sortOptions = [
            'date_desc'  => 'Date (récent)',
            'date_asc'   => 'Date (ancien)',
            'price_desc' => 'Prix (élevé)',
            'price_asc'  => 'Prix (bas)',
        ];
        foreach ($sortOptions as $key => $label): ?>
          <a href="?sort=<?= $key ?>"
             class="sort-pill <?= $sort === $key ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (empty($orders)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <h2>Aucune commande</h2>
        <p>Vous n'avez pas encore passé de commande.</p>
        <a href="catalogue.php" class="btn-primary">Parcourir le catalogue</a>
      </div>
    <?php else: ?>

      <div class="orders-list">
        <?php foreach ($orders as $order): ?>
          <article class="order-card" id="order-<?= htmlspecialchars($order['id']) ?>">

            <div class="order-card-head">
              <div class="order-meta">
                <span class="order-id"><?= htmlspecialchars($order['id']) ?></span>
                <span class="order-date"><?= $fmtDate($order['date']) ?></span>
              </div>
              <div class="order-right">
                <span class="order-status <?= $statusClass[$order['status']] ?? 'status-pending' ?>">
                  <?= htmlspecialchars(ucfirst($order['status'])) ?>
                </span>
                <span class="order-total"><?= $fmt($order['total']) ?> <small>DA</small></span>
              </div>
            </div>

            <div class="order-items">
              <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                  <div class="order-item-img">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                  </div>
                  <div class="order-item-info">
                    <p class="order-item-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="order-item-sub">
                      <?= $item['qty'] ?> × <?= $fmt($item['price']) ?> DA
                    </p>
                  </div>
                  <div class="order-item-subtotal"><?= $fmt($item['price'] * $item['qty']) ?> <small>DA</small></div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="order-card-foot">
              <a href="catalogue.php" class="btn-outline-sm">Commander à nouveau</a>
              <div class="order-summary-total">
                Total : <strong><?= $fmt($order['total']) ?> DA</strong>
              </div>
            </div>

          </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
