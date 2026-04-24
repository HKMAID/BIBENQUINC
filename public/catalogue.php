<?php
require_once __DIR__ . '/includes/session.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');
$flash = getFlash();
$all_products = [
    ['name'=>'Bâche de protection 4x5m','price'=>650, 'image'=>'img/batche-protection.jpg','cat_key'=>'quincaillerie','sub_cat'=>'plomberie','nbDispo'=>15],
    ['name'=>'Boulons M8 (50pcs)',       'price'=>890, 'image'=>'img/boulons.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>8],
    ['name'=>'Cadenas 40mm',            'price'=>650, 'image'=>'img/cadenas.jpg','cat_key'=>'quincaillerie','sub_cat'=>'serrurerie','nbDispo'=>4],
    ['name'=>'Chevilles 8mm (100pcs)',  'price'=>420, 'image'=>'img/chevilles.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>25],
    ['name'=>'Gants de travail',        'price'=>1200,'image'=>'img/gants.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>12],
    ['name'=>'Peinture blanche 1L',     'price'=>780, 'image'=>'img/peinture.jpg','cat_key'=>'quincaillerie','sub_cat'=>'peinture','nbDispo'=>0],
    ['name'=>'Tournevis électrique',    'price'=>2500,'image'=>'img/tournevis.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>5],
    ['name'=>'Clou galvanisé (1kg)',    'price'=>350, 'image'=>'img/clou.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>40],
    ['name'=>'Échelle aluminium 3m',    'price'=>4500,'image'=>'img/echelle.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>3],
    ['name'=>'Marteau de charpentier',  'price'=>1500,'image'=>'img/marteau.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>7],
    ['name'=>'Pince multiprise',        'price'=>950, 'image'=>'img/pince.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>18],
    ['name'=>'Ruban adhésif 50m',       'price'=>250, 'image'=>'img/ruban.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>30],
    ['name'=>'Se à métaux',           'price'=>1800,'image'=>'img/scie.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>9],
    ['name'=>'Vis autoforeuses (100pcs)','price'=>600,'image'=>'img/vis.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>22],
    ['name'=>'Colle époxy 200ml',       'price'=>450, 'image'=>'img/colle.jpg','cat_key'=>'quincaillerie','sub_cat'=>'plomberie','nbDispo'=>16],
    ['name'=>'Ampoule LED 9W',          'price'=>450, 'image'=>'img/ampoule.jpg','cat_key'=>'electricite','sub_cat'=>'eclairage','nbDispo'=>50],
    ['name'=>'Câble électrique 10m',    'price'=>2200,'image'=>'img/cable.jpg','cat_key'=>'electricite','sub_cat'=>'cables','nbDispo'=>10],
    ['name'=>'Disjoncteur 16A Legrand', 'price'=>1800,'image'=>'img/legrand-a16.jpg','cat_key'=>'electricite','sub_cat'=>'disjoncteurs','nbDispo'=>6],
    ['name'=>'Prise électrique double', 'price'=>550, 'image'=>'img/prise.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>0],
    ['name'=>'Projecteur LED 50W',      'price'=>3200,'image'=>'img/projecteur.jpg','cat_key'=>'electricite','sub_cat'=>'projecteurs','nbDispo'=>2],
    ['name'=>'Rallonge électrique 5m',  'price'=>1900,'image'=>'img/rallonge.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>14],
    ['name'=>'Interrupteur blanc',      'price'=>380, 'image'=>'img/interrupteur.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>20],
    ['name'=>'Boîte de dérivation Legrand',  'price'=>280, 'image'=>'img/boite-deriv.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>9],
    ['name'=>'Compteur électrique',     'price'=>8500,'image'=>'img/compteur.jpg','cat_key'=>'electricite','sub_cat'=>'comptage','nbDispo'=>1],
    ['name'=>'Fusible 10A',             'price'=>120, 'image'=>'img/fusible.jpg','cat_key'=>'electricite','sub_cat'=>'comptage','nbDispo'=>100],
    ['name'=>'Multiprise 4 prises',     'price'=>1200,'image'=>'img/multiprise.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>12],
    ['name'=>'Lampe halogène 100W',     'price'=>800, 'image'=>'img/halogene.jpg','cat_key'=>'electricite','sub_cat'=>'eclairage','nbDispo'=>8],
    ['name'=>'Câble coaxial 20m',       'price'=>1500,'image'=>'img/coaxial.jpg','cat_key'=>'electricite','sub_cat'=>'cables','nbDispo'=>5],
    ['name'=>'Disjoncteur différentiel', 'price'=>3200,'image'=>'img/diff.jpg','cat_key'=>'electricite','sub_cat'=>'disjoncteurs','nbDispo'=>3],
    ['name'=>'Transformateur 220V',     'price'=>4500,'image'=>'img/transformateur.jpg','cat_key'=>'electricite','sub_cat'=>'comptage','nbDispo'=>2],
];

$search  = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$cat_key = isset($_GET['category']) ? trim($_GET['category']) : 'quincaillerie';
$sub_cat = isset($_GET['sub_cat'])  ? trim($_GET['sub_cat'])  : '';
$page    = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;

function getProducts($all, $cat, $search, $sub_cat = '') {
    return array_values(array_filter($all, fn($p) =>
        $p['cat_key'] === $cat &&
        ($sub_cat === '' || ($p['sub_cat'] ?? '') === $sub_cat) &&
        ($search === '' || stripos($p['name'], $search) !== false)
    ));
}

$quinc_products = getProducts($all_products, 'quincaillerie', $search, $sub_cat);
$elec_products  = getProducts($all_products, 'electricite',   $search, $sub_cat);
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
    $name  = htmlspecialchars($p['name'], ENT_QUOTES);
    $price = $p['price'];
    $image  = htmlspecialchars($p['image'], ENT_QUOTES);
    $cat_key = htmlspecialchars($p['cat_key'], ENT_QUOTES);
    $nbDispo = $p['nbDispo'] ?? 0;
    $fmt   = number_format($price, 0, ',', ' ');
    
    if ($nbDispo === 0) {
        return "
    <div class=\"product-card unavailable\" data-category=\"{$cat_key}\">
        <div class=\"image\"><img src=\"{$image}\" alt=\"{$name}\" loading=\"lazy\"></div>
        <div class=\"product-card-body\">
            <h3>{$name}</h3>
            <div class=\"price\">{$fmt} <span>DA</span></div>
        </div>
        <div class=\"actions\">
            <div class=\"unavailable-msg\">Article Indisponible</div>
        </div>
    </div>";
    }
    
    return "
    <div class=\"product-card\" data-category=\"{$cat_key}\">
        <div class=\"image\"><img src=\"{$image}\" alt=\"{$name}\" loading=\"lazy\"></div>
        <div class=\"product-card-body\">
            <h3>{$name}</h3>
            <div class=\"price\">{$fmt} <span>DA</span></div>
        </div>
        <div class=\"actions\">
            <button class=\"add-btn\"
                    data-name=\"{$name}\"
                    data-price=\"{$price}\"
                    data-image=\"{$image}\"
                    data-nbdispo=\"{$nbDispo}\"
                    onclick=\"addToCart(this)\">+ Panier</button>
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
            <a href="#">Mes achats</a>
            <a href="#">Mes préférences</a>
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
        </div> <!-- End main-content -->
      </div> <!-- End page-inner -->
    </div> <!-- End page-elec -->
  </div><!-- /h-pager -->

  <footer class="site-footer">
    <p>&copy; 2026 BibenQuinc. Tous droits réservés.</p>
  </footer>
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