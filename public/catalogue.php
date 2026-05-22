<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/products.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');
$flash = getFlash();
$all_products = catalogueProducts();

$wishlist = [];
if ($authUser) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT a.intitule FROM favori f JOIN article a ON f.idArticle = a.idArt WHERE f.idClient = ?');
        $stmt->execute([$authUser['id']]);
        $wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $wishlist = $_SESSION['wishlist'] ?? [];
    }
} else {
    $wishlist = $_SESSION['wishlist'] ?? [];
}
$search  = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$cat_key = isset($_GET['category']) ? trim($_GET['category']) : 'quincaillerie';
$subcat = isset($_GET['cat'])  ? trim($_GET['cat'])  : '';
$page    = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;
$sort    = isset($_GET['sort'])     ? trim($_GET['sort'])     : '';

function getProducts($all, $cat_key, $search, $subcat = '') {
    return array_values(array_filter($all, fn($p) =>
        $p['cat_key'] === $cat_key &&
        ($subcat === '' || ($p['sub_cat'] ?? '') === $subcat) &&
        ($search === '' || stripos($p['name'], $search) !== false)
    ));
}

function sortProducts($products, $sort) {
    switch ($sort) {
        case 'name_asc':
            usort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
            break;
        case 'name_desc':
            usort($products, fn($a, $b) => strcmp($b['name'], $a['name']));
            break;
        case 'price_asc':
            usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
            break;
        case 'price_desc':
            usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
            break;
        case 'note_desc':
            usort($products, fn($a, $b) => ($b['note'] ?? 0) <=> ($a['note'] ?? 0));
            break;
    }
    return $products;
}

$quinc_products = getProducts($all_products, 'quincaillerie', $search, $subcat);
$elec_products  = getProducts($all_products, 'electricite',   $search, $subcat);
$quinc_products = sortProducts($quinc_products, $sort);
$elec_products = sortProducts($elec_products, $sort);
$is_elec        = $cat_key === 'electricite';

// Pagination — separate variables for each category
$items_per_page = 12;

// Quincaillerie pagination
$quinc_total   = count($quinc_products);
$quinc_pages   = max(1, (int)ceil($quinc_total / $items_per_page));
$quinc_page    = !$is_elec ? $page : 1; // only use URL page for active category
$quinc_page    = min($quinc_page, $quinc_pages);
$quinc_offset  = ($quinc_page - 1) * $items_per_page;
$quinc_paged   = array_slice($quinc_products, $quinc_offset, $items_per_page);

// Electricite pagination
$elec_total    = count($elec_products);
$elec_pages    = max(1, (int)ceil($elec_total / $items_per_page));
$elec_page     = $is_elec ? $page : 1;
$elec_page     = min($elec_page, $elec_pages);
$elec_offset   = ($elec_page - 1) * $items_per_page;
$elec_paged    = array_slice($elec_products, $elec_offset, $items_per_page);

// Keep backward-compat aliases for template variables
$current_products   = $is_elec ? $elec_products : $quinc_products;
$total_items        = $is_elec ? $elec_total : $quinc_total;
$total_pages        = $is_elec ? $elec_pages : $quinc_pages;
$paginated_products = $is_elec ? $elec_paged : $quinc_paged;

function productCard($p) {
    global $wishlist, $authUser;
    $name  = htmlspecialchars($p['name'], ENT_QUOTES);
    $price = $p['price'];
    $image  = htmlspecialchars($p['image'], ENT_QUOTES);
    $cat_key = htmlspecialchars($p['cat_key'], ENT_QUOTES);
    $nbDispo = $p['nbDispo'] ?? 0;
    $note = number_format($p['note'] ?? 0, 1, ',', '');
    $fmt   = number_format($price, 0, ',', ' ');
    $slug = urlencode((string) ($p['slug'] ?? ''));
    $articleUrl = "article.php?product={$slug}";
    $oldPrice = isset($p['old_price']) ? (int) $p['old_price'] : null;
    $oldFmt = $oldPrice !== null ? number_format($oldPrice, 0, ',', ' ') : '';
    $priceHtml = $oldPrice !== null
        ? "<div class=\"price promo-price\"><span class=\"old-price\">{$oldFmt} DA</span><span class=\"new-price\">{$fmt} <span>DA</span></span></div>"
        : "<div class=\"price\">{$fmt} <span>DA</span></div>";
    
    $isFav = in_array($p['name'], $wishlist);
    $wishlistClass = $isFav ? "wishlist-btn active" : "wishlist-btn";
    $wishlistTitle = $isFav ? "Retirer des favoris" : "Ajouter aux favoris";

    $wishlistBtn = '';
    if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel') {
        if ($authUser) {
            // AJAX wishlist toggle for logged-in users
            $wishlistBtn = "<button class=\"{$wishlistClass}\" title=\"{$wishlistTitle}\"
                data-product-name=\"{$name}\"
                onclick=\"toggleWishlist(this)\"
                aria-label=\"{$wishlistTitle}\">
                <svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">
                    <path d=\"M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z\"></path>
                </svg>
            </button>";
        } else {
            // Link to login for guests
            $wishlistBtn = "<a href=\"cnx.php\" class=\"wishlist-btn\" title=\"Connectez-vous pour sauvegarder\" aria-label=\"Se connecter pour ajouter aux favoris\">
                <svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">
                    <path d=\"M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z\"></path>
                </svg>
            </a>";
        }
    }

    $ratingHtml = "
        <div style=\"display:flex;align-items:center;gap:4px;margin-bottom:6px;\">
            <svg viewBox=\"0 0 24 24\" width=\"14\" height=\"14\" fill=\"#f59e0b\" stroke=\"none\">
                <polygon points=\"12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2\"></polygon>
            </svg>
            <span style=\"font-size:13px;font-weight:700;color:var(--text);\">{$note}</span>
            <span style=\"font-size:12px;font-weight:500;color:var(--text-muted);\">/ 10</span>
        </div>";
    
    if ($nbDispo === 0) {
        return "
    <div class=\"product-card unavailable\" data-category=\"{$cat_key}\">
        <a class=\"product-card-link\" href=\"{$articleUrl}\" aria-label=\"Voir l'article {$name}\"></a>
        <div class=\"image\"><img src=\"{$image}\" alt=\"{$name}\" loading=\"lazy\"></div>
        <div class=\"product-card-body\">
            <h3>{$name}</h3>
            {$ratingHtml}
            {$priceHtml}
        </div>
        <div class=\"actions\">
            <div class=\"unavailable-msg\">Article Indisponible</div>
            {$wishlistBtn}
        </div>
    </div>";
    }
    $cartBtn = '';
    if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel') {
        $cartBtn = "<button class=\"add-btn\"
                    data-name=\"{$name}\"
                    data-price=\"{$price}\"
                    data-image=\"{$image}\"
                    data-nbdispo=\"{$nbDispo}\"
                    onclick=\"addToCart(this)\">+ Panier</button>";
    }

    return "
    <div class=\"product-card\" data-category=\"{$cat_key}\">
        <a class=\"product-card-link\" href=\"{$articleUrl}\" aria-label=\"Voir l'article {$name}\"></a>
        <div class=\"image\"><img src=\"{$image}\" alt=\"{$name}\" loading=\"lazy\"></div>
        <div class=\"product-card-body\">
            <h3>{$name}</h3>
            {$ratingHtml}
            {$priceHtml}
        </div>
        <div class=\"actions\">
            {$cartBtn}
            {$wishlistBtn}
        </div>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="views/style/style.css">
    <link rel="stylesheet" href="views/style/catalogue.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body<?= $is_elec ? ' class="elec"' : '' ?>>
<?php if ($flash): ?>
  <div style="position:fixed;top:74px;left:50%;transform:translateX(-50%);z-index:9999;background:#111827;color:#fff;padding:10px 14px;border-radius:8px;font-family:var(--fb);font-size:13px;">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
  </div>
<?php endif; ?>

<div class="app">

  <header class="site-header">
    <div class="logo">
      <span class="logo-bar"></span>
      <span class="logo-bolt"> </span>
      <span class="logo-image">
        <img src="img/logo1.png" alt="BibenQuinc Logo Quincaillerie" class="logo-word logo-quinc">
        <img src="img/logo2.png" alt="BibenQuinc Logo Électricité" class="logo-word logo-elec">
      </span>
    </div>

    <div class="cat-toggle">
      <input type="radio" class="cat-radio" id="radio-quinc" name="cat" value="quinc"
            onchange="onRadioChange('quinc')" <?= !$is_elec ? 'checked' : '' ?>>
      <label class="cat-label" for="radio-quinc">Quincaillerie générale</label>
      <input type="radio" class="cat-radio" id="radio-elec" name="cat" value="elec"
            onchange="onRadioChange('elec')" <?= $is_elec ? 'checked' : '' ?>>
      <label class="cat-label" for="radio-elec">Électricité</label>
    </div>

    <nav class="nav">
      <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
      <a href="contact.php">Contact</a>
      <?php endif; ?>
      <?php if ($authUser): ?>
        <div class="user-menu">
          <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)" style="background-color: <?= $is_elec ? 'rgba(11, 105, 248, 0.2)' : 'rgba(232, 93, 4, 0.2)' ?>;">
            <?= htmlspecialchars($authName, ENT_QUOTES) ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <a href="profile.php">Mon profil</a>
            <?php if (($authUser['type'] ?? null) === 'professionnel'): ?>
            <a href="dashboard.php">Tableau de bord</a>
            <?php else: ?>
            <a href="achats.php">Mes achats</a>
            <a href="pref.php">Mes préférences</a>
            <?php endif; ?>
            <a href="logout.php">Déconnexion</a>
          </div>
        </div>
      <?php else: ?>
        <a href="cnx.php">Connexion</a>
      <?php endif; ?>
      <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
      <a href="#" class="cart-btn" onclick="openDrawer(); return false;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
      </a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="h-pager" id="hPager">
    <div class="page" id="page-quinc">
      <div class="page-inner">
        <div class="sidebar" id="sidebar-quinc">
          <!-- Mobile toggle button -->
          <button class="sidebar-toggle-btn" id="sidebarToggleQuinc"
                  onclick="toggleSidebarMobile('sidebar-quinc', this)"
                  aria-expanded="false" aria-controls="sidebar-quinc-list">
            <span>Catégories</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <h2>Catégories</h2>
          <ul id="sidebar-quinc-list">
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>" class="<?= $subcat === '' ? 'active' : '' ?>">Tous les produits</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;cat=outillage" class="<?= $subcat === 'outillage' ? 'active' : '' ?>">Outillage</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;cat=visserie" class="<?= $subcat === 'visserie' ? 'active' : '' ?>">Visserie &amp; Boulonnerie</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;cat=plomberie" class="<?= $subcat === 'plomberie' ? 'active' : '' ?>">Plomberie</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;cat=peinture" class="<?= $subcat === 'peinture' ? 'active' : '' ?>">Peinture</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;cat=jardinage" class="<?= $subcat === 'jardinage' ? 'active' : '' ?>">Jardinage</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;cat=serrurerie" class="<?= $subcat === 'serrurerie' ? 'active' : '' ?>">Serrurerie</a></li>
          </ul>
        </div>
        <div class="main-content">
          <div class="page-header">
            <h1>Quincaillerie générale<small id="product-count-quinc"><?= count($quinc_products) ?> articles</small></h1>
            <form class="search-box" method="get" action="">
              <input type="search" name="search" placeholder="Rechercher…"
                    value="<?= htmlspecialchars($search) ?>">
              <input type="hidden" name="category" value="quincaillerie">
              <input type="hidden" name="cat" value="<?= htmlspecialchars($subcat) ?>">
              <button type="button" class="sort-btn" onclick="toggleSortMenu(this)" title="Trier">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="4" y1="6" x2="20" y2="6"></line>
                  <line x1="4" y1="12" x2="14" y2="12"></line>
                  <line x1="4" y1="18" x2="8" y2="18"></line>
                </svg>
              </button>
              <div class="sort-menu">
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=name_asc" class="<?= ($_GET['sort'] ?? '') === 'name_asc' ? 'active' : '' ?>">Nom (A-Z)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=name_desc" class="<?= ($_GET['sort'] ?? '') === 'name_desc' ? 'active' : '' ?>">Nom (Z-A)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=price_asc" class="<?= ($_GET['sort'] ?? '') === 'price_asc' ? 'active' : '' ?>">Prix (croissant)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=price_desc" class="<?= ($_GET['sort'] ?? '') === 'price_desc' ? 'active' : '' ?>">Prix (décroissant)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=note_desc" class="<?= ($_GET['sort'] ?? '') === 'note_desc' ? 'active' : '' ?>">Mieux notés</a>
              </div>
            </form>
          </div>
          <div class="product-grid">
            <?php if (empty($quinc_products)): ?>
              <p style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted)">Aucun article trouvé.</p>
            <?php else: ?>
              <?php foreach ($quinc_paged as $p): echo productCard($p); endforeach ?>
            <?php endif ?>
          </div>
          <?php if (!empty($quinc_products) && $quinc_pages > 1): ?>
          <div class="pagination">
            <?php if ($quinc_page > 1): ?>
              <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $quinc_page - 1 ?>">← Précédent</a>
            <?php else: ?>
              <span class="disabled">← Précédent</span>
            <?php endif; ?>
            
            <?php 
              $start = max(1, $quinc_page - 2);
              $end = min($quinc_pages, $quinc_page + 2);
              if ($start > 1): ?>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=1">1</a>
              <?php endif;
              if ($start > 2): ?>
                <span>...</span>
              <?php endif;
              for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $quinc_page): ?>
                  <span class="current"><?= $i ?></span>
                <?php else: ?>
                  <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
              <?php endfor;
              if ($end < $quinc_pages - 1): ?>
                <span>...</span>
              <?php endif;
              if ($end < $quinc_pages): ?>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $quinc_pages ?>"><?= $quinc_pages ?></a>
              <?php endif; ?>
            
            <?php if ($quinc_page < $quinc_pages): ?>
              <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $quinc_page + 1 ?>">Suivant →</a>
            <?php else: ?>
              <span class="disabled">Suivant →</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <footer class="site-footer">
            <p>&copy; 2026 BibenQuinc. Tous droits réservés.</p>
          </footer>
        </div>
      </div>
    </div>

    <div class="page" id="page-elec">
      <div class="page-inner">
        <div class="sidebar" id="sidebar-elec">
          <!-- Mobile toggle button -->
          <button class="sidebar-toggle-btn" id="sidebarToggleElec"
                  onclick="toggleSidebarMobile('sidebar-elec', this)"
                  aria-expanded="false" aria-controls="sidebar-elec-list">
            <span>Catégories</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <h2>Catégories</h2>
          <ul id="sidebar-elec-list">
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>" class="<?= $subcat === '' ? 'active' : '' ?>">Tous les produits</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;cat=eclairage" class="<?= $subcat === 'eclairage' ? 'active' : '' ?>">Ampoules &amp; Éclairage</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;cat=cables" class="<?= $subcat === 'cables' ? 'active' : '' ?>">Câbles &amp; Fils</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;cat=disjoncteurs" class="<?= $subcat === 'disjoncteurs' ? 'active' : '' ?>">Disjoncteurs</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;cat=prises" class="<?= $subcat === 'prises' ? 'active' : '' ?>">Prises &amp; Interrupteurs</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;cat=projecteurs" class="<?= $subcat === 'projecteurs' ? 'active' : '' ?>">Projecteurs</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;cat=comptage" class="<?= $subcat === 'comptage' ? 'active' : '' ?>">Comptage</a></li>
          </ul>
        </div>
        <div class="main-content">
          <div class="page-header">
            <h1>Électricité <small id="product-count-elec"><?= count($elec_products) ?> articles</small></h1>
            <form class="search-box" method="get" action="">
              <input type="search" name="search" placeholder="Rechercher…"
                    value="<?= htmlspecialchars($search) ?>">
              <input type="hidden" name="category" value="electricite">
              <input type="hidden" name="cat" value="<?= htmlspecialchars($subcat) ?>">
              <button type="button" class="sort-btn" onclick="toggleSortMenu(this)" title="Trier">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="4" y1="6" x2="20" y2="6"></line>
                  <line x1="4" y1="12" x2="14" y2="12"></line>
                  <line x1="4" y1="18" x2="8" y2="18"></line>
                </svg>
              </button>
              <div class="sort-menu">
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=name_asc" class="<?= ($_GET['sort'] ?? '') === 'name_asc' ? 'active' : '' ?>">Nom (A-Z)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=name_desc" class="<?= ($_GET['sort'] ?? '') === 'name_desc' ? 'active' : '' ?>">Nom (Z-A)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=price_asc" class="<?= ($_GET['sort'] ?? '') === 'price_asc' ? 'active' : '' ?>">Prix (croissant)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=price_desc" class="<?= ($_GET['sort'] ?? '') === 'price_desc' ? 'active' : '' ?>">Prix (décroissant)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&sort=note_desc" class="<?= ($_GET['sort'] ?? '') === 'note_desc' ? 'active' : '' ?>">Mieux notés</a>
              </div>
            </form>
          </div>
          <div class="product-grid">
            <?php if (empty($elec_products)): ?>
              <p style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted)">Aucun produit trouvé.</p>
            <?php else: ?>
              <?php foreach ($elec_paged as $p): echo productCard($p); endforeach ?>
            <?php endif ?>
          </div>
          <?php if (!empty($elec_products) && $elec_pages > 1): ?>
          <div class="pagination">
            <?php if ($elec_page > 1): ?>
              <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $elec_page - 1 ?>">← Précédent</a>
            <?php else: ?>
              <span class="disabled">← Précédent</span>
            <?php endif; ?>
            
            <?php 
              $start = max(1, $elec_page - 2);
              $end = min($elec_pages, $elec_page + 2);
              if ($start > 1): ?>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=1">1</a>
              <?php endif;
              if ($start > 2): ?>
                <span>...</span>
              <?php endif;
              for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $elec_page): ?>
                  <span class="current"><?= $i ?></span>
                <?php else: ?>
                  <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
              <?php endfor;
              if ($end < $elec_pages - 1): ?>
                <span>...</span>
              <?php endif;
              if ($end < $elec_pages): ?>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $elec_pages ?>"><?= $elec_pages ?></a>
              <?php endif; ?>
            
            <?php if ($elec_page < $elec_pages): ?>
              <a href="?category=electricite&search=<?= urlencode($search) ?>&cat=<?= urlencode($subcat) ?>&page=<?= $elec_page + 1 ?>">Suivant →</a>
            <?php else: ?>
              <span class="disabled">Suivant →</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <footer class="site-footer">
            <p>&copy; 2026 BibenQuinc. Tous droits réservés.</p>
          </footer>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="swipe-hint" id="swipeHint" onclick="goToPage('elec')">
  Électricité →
</div>

<div class="drawer-overlay" id="overlay" onclick="closeDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="drawer-head">
    <h2>Mon panier <span class="count-chip" id="chipCount">0</span></h2>
    <button class="close-btn" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-items" id="drawerItems"></div>
  <div class="drawer-footer">
    <div class="total-row">
      <span class="total-label">Total TTC</span>
      <span class="total-amount" id="drawerTotal">0 <span>DA</span></span>
    </div>
    <button class="checkout-btn" onclick="openCheckout()"><?= $authUser ? 'Commander maintenant →' : 'Se connecter pour commander' ?></button>
    <span class="clear-link" onclick="clearCart()">Vider le panier</span>
  </div>
</div>

<div class="checkout-overlay" id="checkoutOverlay" onclick="if(event.target===this)closeCheckout()"></div>
<div class="checkout-panel" id="checkoutPanel">
  <div class="checkout-panel-head">
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      Finaliser la commande
    </h2>
    <button class="close-btn" onclick="closeCheckout()">✕</button>
  </div>
  <div class="checkout-panel-body">

    <div class="checkout-recap">
      <h3>Récapitulatif</h3>
      <div id="checkoutItems" class="checkout-items-list"></div>
      <div class="checkout-subtotal-row">
        <span>Sous-total articles</span>
        <strong id="checkoutSubtotal">0 DA</strong>
      </div>
    </div>

    <div class="checkout-delivery">
      <h3>Mode de livraison</h3>

      <label class="delivery-option">
        <input type="radio" name="delivery" value="home" id="del-home" checked onchange="updateDelivery()">
        <div class="delivery-option-content"><div>
            <div class="delivery-name">Livraison à domicile</div>
            <div class="delivery-desc">Livré chez vous partout en Algérie</div>
          </div>
          <div class="delivery-price">600 DA</div>
        </div>
      </label>

      <label class="delivery-option">
        <input type="radio" name="delivery" value="zrexpress" id="del-zr" onchange="updateDelivery()">
        <div class="delivery-option-content">
          <div>
            <div class="delivery-name">Bureau ZrExpress</div>
            <div class="delivery-desc">Retrait dans un bureau ZrExpress</div>
          </div>
          <div class="delivery-price" id="zrPrice">—</div>
        </div>
      </label>

      <div class="wilaya-select-wrap" id="wilayaWrap" style="display:none">
        <select id="wilayaSelect" onchange="updateDelivery()" class="wilaya-select">
          <option value="">— Choisir votre wilaya —</option>
          <optgroup label="200 DA — Béjaïa">
            <option value="06" data-fee="200">Béjaïa (06)</option>
          </optgroup>
          <optgroup label="400 DA — Autres wilayas">
            <option value="15" data-fee="400">Tizi-Ouzou (15)</option>
            <option value="10" data-fee="400">Bouira (10)</option>
            <option value="16" data-fee="400">Alger (16)</option>
            <option value="18" data-fee="400">Jijel (18)</option>
            <option value="34" data-fee="400">Bordj Bou Arréridj (34)</option>
            <option value="19" data-fee="400">Sétif (19)</option>
          </optgroup>
        </select>
      </div>
    </div>

    <div class="checkout-total-block">
      <div class="checkout-total-row">
        <span>Articles</span><span id="ctSubtotal">0 DA</span>
      </div>
      <div class="checkout-total-row">
        <span>Livraison</span><span id="ctDelivery">600 DA</span>
      </div>
      <div class="checkout-total-row checkout-total-main">
        <span>Total</span><span id="ctTotal">0 DA</span>
      </div>
    </div>

    <?php if ($authUser): ?>
    <button class="checkout-submit-btn" id="checkoutSubmitBtn" onclick="submitOrder()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      Confirmer la commande
    </button>
    <?php else: ?>
    <a href="cnx.php" class="checkout-submit-btn" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Se connecter pour commander
    </a>
    <?php endif; ?>

  </div>
</div>

<script src="views/script/script.js"></script>
<script src="views/script/catalogue.js" defer></script>
<script>
const DELIVERY_FEES = { home: 600 };
let deliveryFee = 600;

function openCheckout() {
  if (cart.length === 0) { alert('Votre panier est vide.'); return; }
  renderCheckoutItems();
  updateDelivery();
  document.getElementById('checkoutOverlay').classList.add('open');
  document.getElementById('checkoutPanel').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeCheckout() {
  document.getElementById('checkoutOverlay').classList.remove('open');
  document.getElementById('checkoutPanel').classList.remove('open');
  document.body.style.overflow = '';
}

function renderCheckoutItems() {
  const cont = document.getElementById('checkoutItems');
  cont.innerHTML = cart.map(i => `
    <div class="checkout-item">
      <img src="${i.image}" alt="">
      <div class="checkout-item-name">${i.name}</div>
      <div class="checkout-item-qty">×${i.qty}</div>
      <div class="checkout-item-price">${fmt(i.price * i.qty)} DA</div>
    </div>`).join('');
  const sub = getTotal();
  document.getElementById('checkoutSubtotal').textContent = fmt(sub) + ' DA';
  document.getElementById('ctSubtotal').textContent = fmt(sub) + ' DA';
}

function updateDelivery() {
  const mode = document.querySelector('input[name="delivery"]:checked')?.value;
  const wilayaWrap = document.getElementById('wilayaWrap');
  const zrPrice = document.getElementById('zrPrice');

  if (mode === 'zrexpress') {
    wilayaWrap.style.display = '';
    const sel = document.getElementById('wilayaSelect');
    const fee = sel.selectedIndex > 0 ? +sel.options[sel.selectedIndex].dataset.fee : 0;
    deliveryFee = fee;
    zrPrice.textContent = fee ? fee + ' DA' : 'Choisir wilaya';
  } else {
    wilayaWrap.style.display = 'none';
    deliveryFee = 600;
    zrPrice.textContent = 'Choisir wilaya';
  }

  document.getElementById('ctDelivery').textContent = deliveryFee ? fmt(deliveryFee) + ' DA' : '— DA';
  document.getElementById('ctTotal').textContent = fmt(getTotal() + deliveryFee) + ' DA';
}

function submitOrder() {
  const mode = document.querySelector('input[name="delivery"]:checked')?.value;
  if (mode === 'zrexpress') {
    const sel = document.getElementById('wilayaSelect');
    if (!sel.value) { alert('Veuillez choisir votre wilaya.'); return; }
  }
  if (!deliveryFee && mode === 'zrexpress') { alert('Veuillez choisir votre wilaya.'); return; }

  const btn = document.getElementById('checkoutSubmitBtn');
  btn.disabled = true;
  btn.textContent = 'Traitement…';

  const wilayaVal = mode === 'zrexpress' ? document.getElementById('wilayaSelect').value : '';
  const total = getTotal() + deliveryFee;

  fetch('checkout_submit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ cart, deliveryMode: mode, wilaya: wilayaVal, deliveryFee, total })
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      cart = []; saveCart(); updateCartUI();
      document.getElementById('checkoutPanel').querySelector('.checkout-panel-body').innerHTML = `
        <div class="checkout-success">
          <div class="checkout-success-icon">✅</div>
          <h2>Commande confirmée !</h2>
          <p>Votre commande <strong>#${data.orderId}</strong> a été enregistrée avec succès.<br>
          Livraison : <strong>${mode === 'home' ? 'À domicile' : 'Bureau ZrExpress'}</strong> — ${fmt(data.deliveryFee)} DA<br>
          Total réglé : <strong>${fmt(data.total)} DA</strong></p>
          <button class="checkout-submit-btn" onclick="closeCheckout()" style="width:auto;padding:12px 24px">Fermer</button>
        </div>`;
    } else {
      alert(data.error || 'Une erreur est survenue.');
      btn.disabled = false;
      btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Confirmer la commande';
    }
  })
  .catch(() => {
    alert('Erreur réseau. Veuillez réessayer.');
    btn.disabled = false;
  });
}
</script>

</body>
</html>