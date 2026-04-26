<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/products.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');
$flash = getFlash();
$all_products = catalogueProducts();

$wishlist = $_SESSION['wishlist'] ?? [];
$search  = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$cat_key = isset($_GET['category']) ? trim($_GET['category']) : 'quincaillerie';
$sub_cat = isset($_GET['sub_cat'])  ? trim($_GET['sub_cat'])  : '';
$page    = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;
$sort    = isset($_GET['sort'])     ? trim($_GET['sort'])     : '';

function getProducts($all, $cat, $search, $sub_cat = '') {
    return array_values(array_filter($all, fn($p) =>
        $p['cat_key'] === $cat &&
        ($sub_cat === '' || ($p['sub_cat'] ?? '') === $sub_cat) &&
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

$quinc_products = getProducts($all_products, 'quincaillerie', $search, $sub_cat);
$elec_products  = getProducts($all_products, 'electricite',   $search, $sub_cat);
$quinc_products = sortProducts($quinc_products, $sort);
$elec_products = sortProducts($elec_products, $sort);
$is_elec        = $cat_key === 'electricite';

// Pagination
$items_per_page = 12; // 3 lignes × 4 colonnes
$current_products = $is_elec ? $elec_products : $quinc_products;
$total_items = count($current_products);
$total_pages = ceil($total_items / $items_per_page);
$page = min($page, max(1, $total_pages)); // Clamp page entre 1 et total_pages
$offset = ($page - 1) * $items_per_page;
$paginated_products = array_slice($current_products, $offset, $items_per_page);

// Helper to render a product card
function productCard($p) {
    global $wishlist;
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
    $wishlistUrl = $isFav ? "pref.php?remove=" . urlencode($p['name']) : "pref.php?add=" . urlencode($p['name']);
    $wishlistClass = $isFav ? "wishlist-btn active" : "wishlist-btn";
    $wishlistTitle = $isFav ? "Retirer des favoris" : "Ajouter aux favoris";

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
            <a href=\"{$wishlistUrl}\" class=\"{$wishlistClass}\" title=\"{$wishlistTitle}\">
                <svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">
                    <path d=\"M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z\"></path>
                </svg>
            </a>
        </div>
    </div>";
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
            <button class=\"add-btn\"
                    data-name=\"{$name}\"
                    data-price=\"{$price}\"
                    data-image=\"{$image}\"
                    data-nbdispo=\"{$nbDispo}\"
                    onclick=\"addToCart(this)\">+ Panier</button>
            <a href=\"{$wishlistUrl}\" class=\"{$wishlistClass}\" title=\"{$wishlistTitle}\">
                <svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">
                    <path d=\"M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z\"></path>
                </svg>
            </a>
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="catalogue.css">
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
      <a href="contact.php">Contact</a>
      <?php if ($authUser): ?>
        <div class="user-menu">
          <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)">
            <?= htmlspecialchars($authName, ENT_QUOTES) ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <a href="profile.php">Mon profil</a>
            <a href="achats.php">Mes achats</a>
            <a href="pref.php">Mes préférences</a>
            <a href="logout.php">Déconnexion</a>
          </div>
        </div>
      <?php else: ?>
        <a href="cnx.php">Connexion</a>
      <?php endif; ?>
      <a href="#" class="cart-btn" onclick="openDrawer(); return false;">
        Mon panier
      </a>
    </nav>
  </header>

  <!-- ═ Horizontal Pager ═ -->
  <div class="h-pager" id="hPager">

    <!-- PAGE : Quincaillerie -->
    <div class="page" id="page-quinc">
      <div class="page-inner">
        <div class="sidebar">
          <h2>Catégories</h2>
          <ul>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>" class="<?= $sub_cat === '' ? 'active' : '' ?>">Tous les produits</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;sub_cat=outillage" class="<?= $sub_cat === 'outillage' ? 'active' : '' ?>">Outillage</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;sub_cat=visserie" class="<?= $sub_cat === 'visserie' ? 'active' : '' ?>">Visserie &amp; Boulonnerie</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;sub_cat=plomberie" class="<?= $sub_cat === 'plomberie' ? 'active' : '' ?>">Plomberie</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;sub_cat=peinture" class="<?= $sub_cat === 'peinture' ? 'active' : '' ?>">Peinture</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;sub_cat=jardinage" class="<?= $sub_cat === 'jardinage' ? 'active' : '' ?>">Jardinage</a></li>
            <li><a href="?category=quincaillerie&amp;search=<?= urlencode($search) ?>&amp;sub_cat=serrurerie" class="<?= $sub_cat === 'serrurerie' ? 'active' : '' ?>">Serrurerie</a></li>
          </ul>
        </div>
        <div class="main-content">
          <div class="page-header">
            <h1>Quincaillerie générale<small id="product-count-quinc"><?= count($quinc_products) ?> articles</small></h1>
            <form class="search-box" method="get" action="">
              <input type="search" name="search" placeholder="Rechercher…"
                    value="<?= htmlspecialchars($search) ?>">
              <input type="hidden" name="category" value="quincaillerie">
              <input type="hidden" name="sub_cat" value="<?= htmlspecialchars($sub_cat) ?>">
              <button type="button" class="sort-btn" onclick="toggleSortMenu(this)" title="Trier">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="4" y1="6" x2="20" y2="6"></line>
                  <line x1="4" y1="12" x2="14" y2="12"></line>
                  <line x1="4" y1="18" x2="8" y2="18"></line>
                </svg>
              </button>
              <div class="sort-menu">
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=name_asc" class="<?= ($_GET['sort'] ?? '') === 'name_asc' ? 'active' : '' ?>">Nom (A-Z)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=name_desc" class="<?= ($_GET['sort'] ?? '') === 'name_desc' ? 'active' : '' ?>">Nom (Z-A)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=price_asc" class="<?= ($_GET['sort'] ?? '') === 'price_asc' ? 'active' : '' ?>">Prix (croissant)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=price_desc" class="<?= ($_GET['sort'] ?? '') === 'price_desc' ? 'active' : '' ?>">Prix (décroissant)</a>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=note_desc" class="<?= ($_GET['sort'] ?? '') === 'note_desc' ? 'active' : '' ?>">Mieux notés</a>
              </div>
            </form>
          </div>
          <div class="product-grid">
            <?php if (empty($quinc_products)): ?>
              <p style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted)">Aucun article trouvé.</p>
            <?php else: ?>
              <?php foreach ($paginated_products as $p): echo productCard($p); endforeach ?>
            <?php endif ?>
          </div>
          <?php if (!empty($quinc_products) && $total_pages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $page - 1 ?>">← Précédent</a>
            <?php else: ?>
              <span class="disabled">← Précédent</span>
            <?php endif; ?>
            
            <?php 
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              if ($start > 1): ?>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=1">1</a>
              <?php endif;
              if ($start > 2): ?>
                <span>...</span>
              <?php endif;
              for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                  <span class="current"><?= $i ?></span>
                <?php else: ?>
                  <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
              <?php endfor;
              if ($end < $total_pages - 1): ?>
                <span>...</span>
              <?php endif;
              if ($end < $total_pages): ?>
                <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
              <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
              <a href="?category=quincaillerie&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $page + 1 ?>">Suivant →</a>
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

    <!-- PAGE : Électricité -->
    <div class="page" id="page-elec">
      <div class="page-inner">
        <div class="sidebar">
          <h2>Catégories</h2>
          <ul>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>" class="<?= $sub_cat === '' ? 'active' : '' ?>">Tous les produits</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;sub_cat=eclairage" class="<?= $sub_cat === 'eclairage' ? 'active' : '' ?>">Ampoules &amp; Éclairage</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;sub_cat=cables" class="<?= $sub_cat === 'cables' ? 'active' : '' ?>">Câbles &amp; Fils</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;sub_cat=disjoncteurs" class="<?= $sub_cat === 'disjoncteurs' ? 'active' : '' ?>">Disjoncteurs</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;sub_cat=prises" class="<?= $sub_cat === 'prises' ? 'active' : '' ?>">Prises &amp; Interrupteurs</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;sub_cat=projecteurs" class="<?= $sub_cat === 'projecteurs' ? 'active' : '' ?>">Projecteurs</a></li>
            <li><a href="?category=electricite&amp;search=<?= urlencode($search) ?>&amp;sub_cat=comptage" class="<?= $sub_cat === 'comptage' ? 'active' : '' ?>">Comptage</a></li>
          </ul>
        </div>
        <div class="main-content">
          <div class="page-header">
            <h1>Électricité <small id="product-count-elec"><?= count($elec_products) ?> articles</small></h1>
            <form class="search-box" method="get" action="">
              <input type="search" name="search" placeholder="Rechercher…"
                    value="<?= htmlspecialchars($search) ?>">
              <input type="hidden" name="category" value="electricite">
              <input type="hidden" name="sub_cat" value="<?= htmlspecialchars($sub_cat) ?>">
              <button type="button" class="sort-btn" onclick="toggleSortMenu(this)" title="Trier">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="4" y1="6" x2="20" y2="6"></line>
                  <line x1="4" y1="12" x2="14" y2="12"></line>
                  <line x1="4" y1="18" x2="8" y2="18"></line>
                </svg>
              </button>
              <div class="sort-menu">
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=name_asc" class="<?= ($_GET['sort'] ?? '') === 'name_asc' ? 'active' : '' ?>">Nom (A-Z)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=name_desc" class="<?= ($_GET['sort'] ?? '') === 'name_desc' ? 'active' : '' ?>">Nom (Z-A)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=price_asc" class="<?= ($_GET['sort'] ?? '') === 'price_asc' ? 'active' : '' ?>">Prix (croissant)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=price_desc" class="<?= ($_GET['sort'] ?? '') === 'price_desc' ? 'active' : '' ?>">Prix (décroissant)</a>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&sort=note_desc" class="<?= ($_GET['sort'] ?? '') === 'note_desc' ? 'active' : '' ?>">Mieux notés</a>
              </div>
            </form>
          </div>
          <div class="product-grid">
            <?php if (empty($elec_products)): ?>
              <p style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted)">Aucun produit trouvé.</p>
            <?php else: ?>
              <?php foreach ($paginated_products as $p): echo productCard($p); endforeach ?>
            <?php endif ?>
          </div>
          <?php if (!empty($elec_products) && $total_pages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $page - 1 ?>">← Précédent</a>
            <?php else: ?>
              <span class="disabled">← Précédent</span>
            <?php endif; ?>
            
            <?php 
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              if ($start > 1): ?>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=1">1</a>
              <?php endif;
              if ($start > 2): ?>
                <span>...</span>
              <?php endif;
              for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                  <span class="current"><?= $i ?></span>
                <?php else: ?>
                  <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
              <?php endfor;
              if ($end < $total_pages - 1): ?>
                <span>...</span>
              <?php endif;
              if ($end < $total_pages): ?>
                <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
              <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
              <a href="?category=electricite&search=<?= urlencode($search) ?>&sub_cat=<?= urlencode($sub_cat) ?>&page=<?= $page + 1 ?>">Suivant →</a>
            <?php else: ?>
              <span class="disabled">Suivant →</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <footer class="site-footer">
            <p>&copy; 2026 BibenQuinc. Tous droits réservés.</p>
          </footer>
        </div> <!-- End main-content -->
      </div> <!-- End page-inner -->
    </div> <!-- End page-elec -->
  </div><!-- /h-pager -->
</div><!-- /app -->

<!-- Swipe hint -->
<div class="swipe-hint" id="swipeHint" onclick="goToPage('elec')">
  Électricité →
</div>

<!-- Overlay + Cart Drawer -->
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
    <button class="checkout-btn">Commander maintenant →</button>
    <span class="clear-link" onclick="clearCart()">Vider le panier</span>
  </div>
</div>

<script src="script.js"></script>
<script src="catalogue.js" defer></script>

</body>
</html>