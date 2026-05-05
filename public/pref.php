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

/* ── Wishlist actions ── */
if (isset($_GET['add'])) {
    $productName = trim($_GET['add']);
    if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
    if (!in_array($productName, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $productName;
        setFlash('success', '❤ Produit ajouté aux favoris');
    }
    header('Location: pref.php'); exit;
}

if (isset($_GET['remove'])) {
    $productName = trim($_GET['remove']);
    $_SESSION['wishlist'] = array_values(
        array_filter($_SESSION['wishlist'] ?? [], fn($p) => $p !== $productName)
    );
    setFlash('info', 'Produit retiré des favoris');
    header('Location: pref.php'); exit;
}

$flash = getFlash();

/* ── Data ── */
$all_products    = catalogueProducts();
$wishlist        = $_SESSION['wishlist'] ?? [];
$search          = trim($_GET['search'] ?? '');
$sort            = $_GET['sort'] ?? '';

$wishlist_products = array_values(array_filter($all_products, fn($p) => in_array($p['name'], $wishlist)));

if ($search !== '') {
    $wishlist_products = array_values(
        array_filter($wishlist_products, fn($p) => stripos($p['name'], $search) !== false)
    );
}

function sortWishlist(array $products, string $sort): array {
    switch ($sort) {
        case 'name_asc':  usort($products, fn($a,$b)=>strcmp($a['name'],$b['name'])); break;
        case 'name_desc': usort($products, fn($a,$b)=>strcmp($b['name'],$a['name'])); break;
        case 'price_asc': usort($products, fn($a,$b)=>$a['price']-$b['price']);        break;
        case 'price_desc':usort($products, fn($a,$b)=>$b['price']-$a['price']);        break;
        case 'note_desc': usort($products, fn($a,$b)=>($b['note']??0)<=>($a['note']??0)); break;
    }
    return $products;
}
$wishlist_products = sortWishlist($wishlist_products, $sort);

function renderFavCard(array $p): string {
    $name    = htmlspecialchars($p['name'], ENT_QUOTES);
    $image   = htmlspecialchars($p['image'], ENT_QUOTES);
    $price   = $p['price'];
    $nbDispo = $p['nbDispo'] ?? 0;
    $note    = $p['note'] ?? 0;
    $fmt     = number_format($price, 0, ',', ' ');
    $removeUrl = 'pref.php?remove=' . urlencode($p['name']);
    $stars   = str_repeat('★', (int)round($note/2)) . str_repeat('☆', 5-(int)round($note/2));

    $actions = $nbDispo === 0
        ? '<div class="unavailable-msg">Article Indisponible</div>'
        : '<button class="add-btn"
                   data-name="'.$name.'"
                   data-price="'.$price.'"
                   data-image="'.$image.'"
                   data-nbdispo="'.$nbDispo.'"
                   onclick="addToCart(this)">+ Panier</button>';

    return <<<HTML
    <div class="product-card pref-card">
      <a href="article.php?id={$p['slug']}" class="product-card-link" tabindex="-1" aria-hidden="true"></a>
      <div class="image"><img src="{$image}" alt="{$name}" loading="lazy"></div>
      <div class="product-card-body">
        <div class="pref-rating" title="Note: {$note}/10">{$stars} <small>{$note}/10</small></div>
        <h3>{$name}</h3>
        <div class="price">{$fmt} <span>DA</span></div>
      </div>
      <div class="actions">
        {$actions}
        <a href="{$removeUrl}" class="remove-btn" title="Retirer des favoris">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </a>
      </div>
    </div>
HTML;
}

$pageTitle = 'Mes favoris — BibenQuinc';
$pageDesc  = 'Gérez votre liste de produits favoris sur BibenQuinc.';
$activeNav = 'pref';
$extraCss  = ['profile.css', 'pref.css'];

include __DIR__ . '/includes/header.php';
?>

  <!-- Page Banner -->
  <div class="acc-banner">
    <div class="acc-banner-inner">
      <div class="acc-avatar"><?= mb_strtoupper(mb_substr($authName, 0, 1)) ?></div>
      <div>
        <h1 class="acc-banner-title">Mes favoris</h1>
        <p class="acc-banner-sub">
          <?= count($wishlist) ?> article<?= count($wishlist) !== 1 ? 's' : '' ?> sauvegardé<?= count($wishlist) !== 1 ? 's' : '' ?>
        </p>
      </div>
    </div>
    <nav class="acc-tabs">
      <a href="profile.php" class="acc-tab">Informations</a>
      <a href="achats.php"  class="acc-tab">Mes achats</a>
      <a href="pref.php"    class="acc-tab active">Favoris</a>
    </nav>
  </div>

  <main class="acc-main">

    <!-- Search + Sort bar -->
    <div class="orders-toolbar">
      <form method="get" action="" class="pref-search-form">
        <div class="search-inline">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input type="search" name="search" placeholder="Rechercher dans mes favoris…"
                 value="<?= htmlspecialchars($search) ?>" id="prefSearch">
          <?php if ($sort): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
        </div>
      </form>

      <div class="sort-pills">
        <span class="sort-label">Trier :</span>
        <?php
        $sortOpts = [
            '' => 'Défaut', 'name_asc' => 'A-Z', 'name_desc' => 'Z-A',
            'price_asc' => 'Prix ↑', 'price_desc' => 'Prix ↓', 'note_desc' => 'Note ↓',
        ];
        foreach ($sortOpts as $k => $lbl): ?>
          <a href="?search=<?= urlencode($search) ?>&sort=<?= $k ?>"
             class="sort-pill <?= $sort === $k ? 'active' : '' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (empty($wishlist)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        <h2>Votre wishlist est vide</h2>
        <p>Cliquez sur le cœur d'un produit dans le catalogue pour l'ajouter ici.</p>
        <a href="catalogue.php" class="btn-primary">Parcourir le catalogue</a>
      </div>

    <?php elseif (empty($wishlist_products)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <h2>Aucun résultat pour «&nbsp;<?= htmlspecialchars($search) ?>&nbsp;»</h2>
        <p>Essayez avec un autre terme.</p>
        <a href="pref.php" class="btn-primary">Voir tous les favoris</a>
      </div>

    <?php else: ?>
      <div class="product-grid pref-grid">
        <?php foreach ($wishlist_products as $p): echo renderFavCard($p); endforeach; ?>
      </div>
    <?php endif; ?>

  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>