<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/ratings_store.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim((($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? ''))) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');
$productSlug = trim((string) ($_GET['product'] ?? ''));
$product = $productSlug !== '' ? findProductBySlug($productSlug) : null;
$isElec = $product !== null && (($product['cat_key'] ?? '') === 'electricite');

if ($product === null) {
    http_response_code(404);
}

$error = '';
$success = '';

if ($product !== null && $authUser && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawRating = trim((string) ($_POST['rating'] ?? ''));

    if ($rawRating === '' || !is_numeric($rawRating)) {
        $error = 'Veuillez saisir une note numérique.';
    } else {
        $rating = (float) $rawRating;
        if ($rating < 0 || $rating > 10) {
            $error = 'La note doit être comprise entre 0.0 et 10.0.';
        } else {
            saveProductRating((string) $authUser['id'], (string) $product['product_id'], $rating);
            $success = 'Votre note a été enregistrée.';
        }
    }
}

$userRating = null;
if ($product !== null && $authUser) {
    $userRating = getUserRating((string) $authUser['id'], (string) $product['product_id']);
}

$globalRating = null;
$globalVotes = 0;
if ($product !== null) {
    $global = getProductGlobalRating((string) $product['product_id']);
    $globalRating = $global['average'];
    $globalVotes = (int) $global['count'];
    if ($globalRating === null) {
        $globalRating = isset($product['note']) ? (float) $product['note'] : null;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article - BibenQuinc</title>
    <link rel="stylesheet" href="views/style/style.css">
    <link rel="stylesheet" href="views/style/article.css">
</head>
<body<?= $isElec ? ' class="elec"' : '' ?>>
<header class="site-header">
    <div class="logo">
      <span class="logo-bar"></span>
      <span class="logo-bolt"> </span>
      <span class="logo-image">
        <img src="img/logo1.png" alt="BibenQuinc Logo Quincaillerie" class="logo-word logo-quinc">
        <img src="img/logo2.png" alt="BibenQuinc Logo Électricité" class="logo-word logo-elec">
      </span>
    </div>

    <nav class="nav" id="siteNav">
      <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
      <a href="contact.php">Contact</a>
      <?php endif; ?>
    </nav>

    <div style="display:flex;align-items:center;gap:4px;margin-left:auto;">
      <?php if ($authUser): ?>
        <div class="user-menu">
          <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)" style="background-color: <?= $isElec ? 'rgba(11, 105, 248, 0.2)' : 'rgba(232, 93, 4, 0.2)' ?>;">
            <span><?= htmlspecialchars((string) $authName, ENT_QUOTES) ?></span>
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
        <a href="cnx.php" style="color:#999;text-decoration:none;padding:6px 14px;font-size:14px;font-weight:500;">Connexion</a>
      <?php endif; ?>
      <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
      <a href="#" class="cart-btn" onclick="openDrawer(); return false;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="count-chip" id="chipCount">0</span>
      </a>
      <?php endif; ?>
      <button class="nav-toggle" id="navToggle" onclick="toggleMobileNav()" aria-label="Menu" aria-expanded="false">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>
  </header>
  <script>
    function toggleMobileNav() {
      var nav = document.getElementById('siteNav');
      var btn = document.getElementById('navToggle');
      var open = nav.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  </script>

<main class="article-page">
    <a class="back-link" href="catalogue.php">← Retour au catalogue</a>

    <?php if ($product === null): ?>
        <section class="article-card">
            <h1>Article introuvable</h1>
            <p>Le produit demande n'existe pas ou n'est plus disponible.</p>
        </section>
    <?php else: ?>
        <?php
            $name = htmlspecialchars((string) $product['name'], ENT_QUOTES);
            $image = htmlspecialchars((string) $product['image'], ENT_QUOTES);
            $brand = htmlspecialchars((string) ($product['brand'] ?? 'BibenQuinc'), ENT_QUOTES);
            $description = htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES);
            $price = number_format((float) $product['price'], 0, ',', ' ');
            $priceRaw = (float) $product['price'];
            $oldPrice = isset($product['old_price']) ? number_format((float) $product['old_price'], 0, ',', ' ') : null;
            $stock = (int) ($product['nbDispo'] ?? 0);
            $categoryLabel = ucfirst((string) ($product['sub_cat'] ?? $product['cat_key'] ?? 'general'));
        ?>
        <section class="article-card">
            <div class="article-image">
                <img src="<?= $image ?>" alt="<?= $name ?>">
            </div>

            <div class="article-content">
                <p class="article-category"><?= htmlspecialchars($categoryLabel, ENT_QUOTES) ?></p>
                <h1><?= $name ?></h1>
                <p class="article-description"><?= $description ?></p>
                <p class="article-stock">Disponiblité: <?= stockStatus((int) ($product['nbDispo'] ?? 0)) ?></p>
                <?php if ($globalRating !== null): ?>
                    <p class="article-global-rating">
                        Note globale: <?= number_format($globalRating, 1, ',', '') ?>/10
                        <span>
                            <?= $globalVotes > 0 ? '(' . $globalVotes . ' avis)' : ''?>
                        </span>
                    </p>
                <?php endif; ?>

                <div class="article-price">
                    <?php if ($oldPrice !== null): ?>
                        <span class="old-price"><?= $oldPrice ?> DA</span>
                    <?php endif; ?>
                    <span class="new-price"><?= $price ?> DA</span>
                </div>
                <p class="article-brand">Marque: <strong><?= $brand ?></strong></p>

                <div class="article-cart">
                    <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
                        <?php if ($stock > 0): ?>
                            <div class="actions">
                                <div class="quantity">
                                    <button type="button" onclick="adjustArticleQty(-1)">−</button>
                                    <input id="articleQty" type="number" value="1" min="1" max="<?= $stock ?>" onblur="normalizeArticleQty(true)">
                                    <button type="button" onclick="adjustArticleQty(1)">+</button>
                                </div>
                                <button class="add-btn"
                                        type="button"
                                        data-name="<?= $name ?>"
                                        data-price="<?= $priceRaw ?>"
                                        data-image="<?= $image ?>"
                                        data-nbdispo="<?= $stock ?>"
                                        onclick="addToCart(this)">
                                    Ajouter au panier
                                </button>
                            </div>
                            <small class="stock-help">Quantite max autorisee: <?= $stock ?></small>
                        <?php else: ?>
                            <p class="feedback error">Article indisponible actuellement.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
                <div class="rating-zone">
                    <h2>Noter cet article</h2>
                    <br>
                    <?php if ($authUser): ?>
                        <?php if ($error !== ''): ?><p class="feedback error"><?= htmlspecialchars($error, ENT_QUOTES) ?></p><?php endif; ?>
                        <?php if ($success !== ''): ?><p class="feedback success"><?= htmlspecialchars($success, ENT_QUOTES) ?></p><?php endif; ?>
                        <?php if ($userRating !== null): ?>
                            <p class="current-rating">Votre note actuelle: <strong><?= number_format($userRating, 1, ',', '') ?>/10</strong></p>
                        <?php endif; ?>
                        <form method="post" action="">
                            <label for="rating">Note (0.0 a 10.0)</label>
                            <input
                                type="number"
                                id="rating"
                                name="rating"
                                step="0.1"
                                min="0"
                                max="10"
                                value="<?= htmlspecialchars($userRating !== null ? (string) number_format($userRating, 1, '.', '') : '', ENT_QUOTES) ?>"
                                required
                            >
                            <button type="submit">Enregistrer ma note</button>
                        </form>
                    <?php else: ?>
                        <p>Connectez-vous pour noter cet article.</p>
                        <br>
                        <a class="login-link" href="cnx.php">Se connecter</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <p>© 2026 BibenQuinc - Tous droits réservés</p>
</footer>

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
    <button class="checkout-btn" type="button">Commander maintenant →</button>
    <span class="clear-link" onclick="clearCart()">Vider le panier</span>
  </div>
</div>
<script src="views/script/script.js"></script>
<script src="views/script/article.js"></script>
</body>
</html>
