<?php
require_once __DIR__ . '/includes/session.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

// Handle wishlist actions
if (isset($_GET['add']) && isLoggedIn()) {
    $productName = trim($_GET['add']);
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    if (!in_array($productName, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $productName;
        setFlash('success', 'Produit ajouté aux favoris');
    }
    header('Location: pref.php');
    exit;
}

if (isset($_GET['remove']) && isLoggedIn()) {
    $productName = trim($_GET['remove']);
    $_SESSION['wishlist'] = array_filter($_SESSION['wishlist'], fn($p) => $p !== $productName);
    setFlash('success', 'Produit retiré des favoris');
    header('Location: pref.php');
    exit;
}

$flash = getFlash();

// All products (same as catalogue)
$all_products = [
    ['name'=>'Bâche de protection 4x5m','price'=>650, 'image'=>'img/batche-protection.jpg','cat_key'=>'quincaillerie','sub_cat'=>'plomberie','nbDispo'=>15, 'note'=>7.6],
    ['name'=>'Boulons M8 (50pcs)','price'=>890, 'image'=>'img/boulons.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>8, 'note'=>8.6],
    ['name'=>'Cadenas 40mm','price'=>650, 'image'=>'img/cadenas.jpg','cat_key'=>'quincaillerie','sub_cat'=>'serrurerie','nbDispo'=>4, 'note'=>8.4],
    ['name'=>'Chevilles 8mm (100pcs)','price'=>420, 'image'=>'img/chevilles.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>25, 'note'=>5.9],
    ['name'=>'Gants de travail','price'=>1200, 'image'=>'img/gants.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>12, 'note'=>7.7],
    ['name'=>'Peinture blanche 1L','price'=>780, 'image'=>'img/peinture.jpg','cat_key'=>'quincaillerie','sub_cat'=>'peinture','nbDispo'=>0, 'note'=>6.0],
    ['name'=>'Tournevis électrique','price'=>2500, 'image'=>'img/tournevis.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>5, 'note'=>9.7],
    ['name'=>'Clou galvanisé (1kg)','price'=>350, 'image'=>'img/clou.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>40, 'note'=>7.4],
    ['name'=>'Échelle aluminium 3m','price'=>4500, 'image'=>'img/echelle.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>3, 'note'=>5.9],
    ['name'=>'Marteau de charpentier','price'=>1500, 'image'=>'img/marteau.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>7, 'note'=>7.3],
    ['name'=>'Pince multiprise','price'=>950, 'image'=>'img/pince.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>18, 'note'=>7.0],
    ['name'=>'Ruban adhésif 50m','price'=>250, 'image'=>'img/ruban.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>30, 'note'=>6.9],
    ['name'=>'Se à métaux','price'=>1800, 'image'=>'img/scie.jpg','cat_key'=>'quincaillerie','sub_cat'=>'outillage','nbDispo'=>9, 'note'=>9.7],
    ['name'=>'Vis autoforeuses (100pcs)','price'=>600, 'image'=>'img/vis.jpg','cat_key'=>'quincaillerie','sub_cat'=>'visserie','nbDispo'=>22, 'note'=>7.5],
    ['name'=>'Colle époxy 200ml','price'=>450, 'image'=>'img/colle.jpg','cat_key'=>'quincaillerie','sub_cat'=>'plomberie','nbDispo'=>16, 'note'=>8.6],
    ['name'=>'Ampoule LED 9W','price'=>450, 'image'=>'img/ampoule.jpg','cat_key'=>'electricite','sub_cat'=>'eclairage','nbDispo'=>50, 'note'=>8.0],
    ['name'=>'Câble électrique 10m','price'=>2200, 'image'=>'img/cable.jpg','cat_key'=>'electricite','sub_cat'=>'cables','nbDispo'=>10, 'note'=>5.6],
    ['name'=>'Disjoncteur 16A Legrand','price'=>1800, 'image'=>'img/legrand-a16.jpg','cat_key'=>'electricite','sub_cat'=>'disjoncteurs','nbDispo'=>6, 'note'=>7.0],
    ['name'=>'Prise électrique double','price'=>550, 'image'=>'img/prise.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>0, 'note'=>5.3],
    ['name'=>'Projecteur LED 50W','price'=>3200, 'image'=>'img/projecteur.jpg','cat_key'=>'electricite','sub_cat'=>'projecteurs','nbDispo'=>2, 'note'=>7.7],
    ['name'=>'Rallonge électrique 5m','price'=>1900, 'image'=>'img/rallonge.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>14, 'note'=>6.5],
    ['name'=>'Interrupteur blanc','price'=>380, 'image'=>'img/interrupteur.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>20, 'note'=>7.3],
    ['name'=>'Boîte de dérivation Legrand','price'=>280, 'image'=>'img/boite-deriv.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>9, 'note'=>5.2],
    ['name'=>'Compteur électrique','price'=>8500, 'image'=>'img/compteur.jpg','cat_key'=>'electricite','sub_cat'=>'comptage','nbDispo'=>1, 'note'=>5.9],
    ['name'=>'Fusible 10A','price'=>120, 'image'=>'img/fusible.jpg','cat_key'=>'electricite','sub_cat'=>'comptage','nbDispo'=>100, 'note'=>6.4],
    ['name'=>'Multiprise 4 prises','price'=>1200, 'image'=>'img/multiprise.jpg','cat_key'=>'electricite','sub_cat'=>'prises','nbDispo'=>12, 'note'=>5.9],
    ['name'=>'Lampe halogène 100W','price'=>800, 'image'=>'img/halogene.jpg','cat_key'=>'electricite','sub_cat'=>'eclairage','nbDispo'=>8, 'note'=>6.6],
    ['name'=>'Câble coaxial 20m','price'=>1500, 'image'=>'img/coaxial.jpg','cat_key'=>'electricite','sub_cat'=>'cables','nbDispo'=>5, 'note'=>8.7],
    ['name'=>'Disjoncteur différentiel','price'=>3200, 'image'=>'img/diff.jpg','cat_key'=>'electricite','sub_cat'=>'disjoncteurs','nbDispo'=>3, 'note'=>9.5],
    ['name'=>'Transformateur 220V','price'=>4500, 'image'=>'img/transformateur.jpg','cat_key'=>'electricite','sub_cat'=>'comptage','nbDispo'=>2, 'note'=>7.8],
];

// Get wishlist products
$wishlist = $_SESSION['wishlist'] ?? [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';

// Filter products by wishlist
$wishlist_products = array_values(array_filter($all_products, fn($p) => in_array($p['name'], $wishlist)));

// Apply search
if ($search !== '') {
    $wishlist_products = array_values(array_filter($wishlist_products, fn($p) => stripos($p['name'], $search) !== false));
}

// Apply sorting
function sortProducts($products, $sort) {
    switch ($sort) {
        case 'name_asc':
            usort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
            break;
        case 'name_desc':
            usort($products, fn($a, $b) => strcmp($b['name'], $a['name']));
            break;
        case 'price_asc':
            usort($products, fn($a, $b) => $a['price'] - $b['price']);
            break;
        case 'price_desc':
            usort($products, fn($a, $b) => $b['price'] - $a['price']);
            break;
    }
    return $products;
}

$wishlist_products = sortProducts($wishlist_products, $sort);

// Helper to render a product card
function wishlistCard($p) {
    $name = htmlspecialchars($p['name'], ENT_QUOTES);
    $price = $p['price'];
    $image = htmlspecialchars($p['image'], ENT_QUOTES);
    $nbDispo = $p['nbDispo'] ?? 0;
    $fmt = number_format($price, 0, ',', ' ');
    
    if ($nbDispo === 0) {
        return "
    <div class=\"product-card unavailable\">
        <div class=\"image\"><img src=\"{$image}\" alt=\"{$name}\" loading=\"lazy\"></div>
        <div class=\"product-card-body\">
            <h3>{$name}</h3>
            <div class=\"price\">{$fmt} <span>DA</span></div>
        </div>
        <div class=\"actions\">
            <div class=\"unavailable-msg\">Article Indisponible</div>
            <a href=\"pref.php?remove=" . urlencode($p['name']) . "\" class=\"remove-btn\" title=\"Retirer des favoris\">
                <svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">
                    <polyline points=\"3 6 5 6 21 6\"></polyline>
                    <path d=\"M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\"></path>
                </svg>
            </a>
        </div>
    </div>";
    }
    
    return "
    <div class=\"product-card\">
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
            <a href=\"pref.php?remove=" . urlencode($p['name']) . "\" class=\"remove-btn\" title=\"Retirer des favoris\">
                <svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">
                    <polyline points=\"3 6 5 6 21 6\"></polyline>
                    <path d=\"M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\"></path>
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
    <title>Mes préférences - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="catalogue.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .pref-page {
            flex: 1;
        }
        .pref-header {
            background: var(--header-bg);
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
        }
        .pref-header h1 {
            font-family: var(--fd);
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }
        .pref-header p {
            color: var(--text-muted);
            font-size: 14px;
        }
        .pref-stats {
            display: flex;
            gap: 24px;
            margin-top: 16px;
        }
        .pref-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-md);
        }
        .pref-stat svg {
            width: 20px;
            height: 20px;
            color: var(--accent);
        }
        .pref-stat span {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }
        .remove-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: var(--r-md);
            background: var(--surface-2);
            color: #dc2626;
            border: 1px solid var(--border);
            transition: all var(--t);
            flex-shrink: 0;
        }
        .remove-btn:hover {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fca5a5;
            transform: scale(1.05);
        }
        .empty-wishlist {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            text-align: center;
        }
        .empty-wishlist svg {
            width: 80px;
            height: 80px;
            color: var(--text-muted);
            margin-bottom: 24px;
            opacity: 0.5;
        }
        .empty-wishlist h2 {
            font-family: var(--fd);
            font-size: 20px;
            color: var(--text);
            margin-bottom: 12px;
        }
        .empty-wishlist p {
            color: var(--text-muted);
            font-size: 15px;
            margin-bottom: 24px;
        }
        .empty-wishlist a {
            display: inline-block;
            padding: 12px 24px;
            background: var(--accent);
            color: #fff;
            border-radius: var(--r-md);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
        }
        .empty-wishlist a:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
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

    <nav class="nav">
      <a href="catalogue.php">Catalogue</a>
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
            <a href="pref.php" class="active">Mes préférences</a>
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

  <div class="pref-header">
    <h1>Mes préférences</h1>
    <p>Gérez votre liste de favoris et vos produits préférés</p>
    <div class="pref-stats">
      <div class="pref-stat">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <span><?= count($wishlist) ?> favori<?= count($wishlist) !== 1 ? 's' : '' ?></span>
      </div>
    </div>
  </div>

  <main class="main-content pref-page" style="max-width: 1400px; margin: 0 auto; padding: 28px 32px;">
    <div class="page-header">
      <h1>Ma wishlist<small><?= count($wishlist_products) ?> article<?= count($wishlist_products) !== 1 ? 's' : '' ?></small></h1>
      <form class="search-box" method="get" action="">
        <input type="search" name="search" placeholder="Rechercher…"
              value="<?= htmlspecialchars($search) ?>">
        <button type="button" class="sort-btn" onclick="toggleSortMenu(this)" title="Trier">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="4" y1="6" x2="20" y2="6"></line>
            <line x1="4" y1="12" x2="14" y2="12"></line>
            <line x1="4" y1="18" x2="8" y2="18"></line>
          </svg>
        </button>
        <div class="sort-menu">
          <a href="?search=<?= urlencode($search) ?>&sort=name_asc" class="<?= ($_GET['sort'] ?? '') === 'name_asc' ? 'active' : '' ?>">Nom (A-Z)</a>
          <a href="?search=<?= urlencode($search) ?>&sort=name_desc" class="<?= ($_GET['sort'] ?? '') === 'name_desc' ? 'active' : '' ?>">Nom (Z-A)</a>
          <a href="?search=<?= urlencode($search) ?>&sort=price_asc" class="<?= ($_GET['sort'] ?? '') === 'price_asc' ? 'active' : '' ?>">Prix (croissant)</a>
          <a href="?search=<?= urlencode($search) ?>&sort=price_desc" class="<?= ($_GET['sort'] ?? '') === 'price_desc' ? 'active' : '' ?>">Prix (décroissant)</a>
        </div>
      </form>
    </div>

    <?php if (!isLoggedIn()): ?>
      <div class="empty-wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <h2>Connectez-vous pour voir vos favoris</h2>
        <p>Vous devez être connecté pour accéder à votre liste de favoris.</p>
        <a href="cnx.php">Se connecter</a>
      </div>
    <?php elseif (empty($wishlist)): ?>
      <div class="empty-wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <h2>Votre wishlist est vide</h2>
        <p>Ajoutez des produits à vos favoris depuis le catalogue.</p>
        <a href="catalogue.php">Parcourir le catalogue</a>
      </div>
    <?php elseif (empty($wishlist_products)): ?>
      <div class="empty-wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <h2>Aucun résultat pour "<?= htmlspecialchars($search) ?>"</h2>
        <p>Essayez avec un autre terme de recherche.</p>
        <a href="pref.php">Voir tous les favoris</a>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($wishlist_products as $p): echo wishlistCard($p); endforeach ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Cart Drawer -->
  <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
  <div class="cart-drawer" id="cartDrawer">
    <div class="drawer-head">
      <h2>
        Mon panier
        <span class="count-chip" id="cartCount">0</span>
      </h2>
      <button class="close-btn" onclick="closeDrawer()">×</button>
    </div>
    <div class="drawer-items" id="drawerItems">
      <div class="drawer-empty">Votre panier est vide</div>
    </div>
    <div class="drawer-footer">
      <div class="total-row">
        <span class="total-label">Total</span>
        <span class="total-amount" id="cartTotal">0 <span>DA</span></span>
      </div>
      <button class="checkout-btn" onclick="alert('Fonctionnalité de paiement à venir')">Passer à la caisse</button>
      <a href="#" class="clear-link" onclick="clearCart(); return false;">Vider le panier</a>
    </div>
  </div>

  <footer class="site-footer">
    <p>© 2026 BibenQuinc - Tous droits réservés</p>
  </footer>

</div>

<script src="catalogue.js"></script>
<script>
function toggleUserMenu(e) {
  e.preventDefault();
  const btn = document.getElementById('userMenuBtn');
  const dropdown = document.getElementById('userDropdown');
  const isOpen = dropdown.classList.contains('open');
  
  if (isOpen) {
    dropdown.classList.remove('open');
    btn.classList.remove('active');
  } else {
    dropdown.classList.add('open');
    btn.classList.add('active');
  }
}

document.addEventListener('click', function(e) {
  const userMenu = document.querySelector('.user-menu');
  if (userMenu && !userMenu.contains(e.target)) {
    const dropdown = document.getElementById('userDropdown');
    const btn = document.getElementById('userMenuBtn');
    if (dropdown) {
      dropdown.classList.remove('open');
      btn.classList.remove('active');
    }
  }
});

document.addEventListener('DOMContentLoaded', () => {
  loadCart();
  updateCartUI();
});

function toggleSortMenu(btn) {
  btn.classList.toggle('active');
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.search-box')) {
    document.querySelectorAll('.sort-btn').forEach(btn => {
      btn.classList.remove('active');
    });
  }
});
</script>

</body>
</html>