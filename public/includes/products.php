<?php
declare(strict_types=1);

function slugifyProductName(string $name): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $value = strtolower($value ?: $name);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value ?? '');
    $value = trim((string) $value, '-');

    return $value !== '' ? $value : 'article';
}

function productPromotions(): array
{
    $promos = [
        'Tournevis électrique' => 2900,
        'Échelle aluminium 3m' => 5100,
        'Disjoncteur différentiel' => 3600,
        'Projecteur LED 50W' => 3700,
    ];
    
    try {
        require_once dirname(__DIR__, 2) . '/config/db.php';
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT a.intitule, p.oldPrice FROM promo p JOIN article a ON p.idArt = a.idArt');
        foreach ($stmt as $row) {
            $promos[$row['intitule']] = (int)$row['oldPrice'];
        }
    } catch (Exception $e) {
        // DB issue, fallback to hardcoded
    }
    
    return $promos;
}

function productNotes(): array
{
    $notes = [];
    try {
        require_once dirname(__DIR__, 2) . '/config/db.php';
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT a.intitule, AVG(n.note) AS avg_note FROM note n JOIN article a ON n.idArticle = a.idArt GROUP BY a.idArt');
        foreach ($stmt as $row) {
            if ($row['avg_note'] !== null) {
                $notes[$row['intitule']] = round((float)$row['avg_note'], 1);
            }
        }
    } catch (Exception $e) {
        // DB issue, ignore
    }
    return $notes;
}

function productBrands(): array
{
    return [
        'outillage' => 'Bosch',
        'visserie' => 'Fischer',
        'plomberie' => 'Sika',
        'peinture' => 'Dulux',
        'serrurerie' => 'Yale',
        'eclairage' => 'Philips',
        'cables' => 'Nexans',
        'disjoncteurs' => 'Legrand',
        'prises' => 'Schneider',
        'projecteurs' => 'Osram',
        'comptage' => 'Siemens',
    ];
}

function productDescription(array $product): string
{
    $name = (string) ($product['name'] ?? 'Article');
    $cat = (string) ($product['cat_key'] ?? '');
    $stock = (int) ($product['nbDispo'] ?? 0);

    $domain = $cat === 'electricite'
        ? 'Concu pour les installations electriques domestiques et professionnelles.'
        : 'Concu pour les travaux de quincaillerie, bricolage et maintenance.';

    return $name . ' - ' . $domain;
}

function stockStatus(int $stock): string
{
    if ($stock > 0) {
        return (string)$stock . ' restant(s)';
    }
    return 'Rupture de stock';
}

function catalogueProducts(): array
{
    $products = [
        ['name' => 'Bâche de protection 4x5m', 'price' => 650, 'image' => 'img/batche-protection.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'plomberie', 'nbDispo' => 15, 'note' => 7.6],
        ['name' => 'Boulons M8 (50pcs)', 'price' => 890, 'image' => 'img/boulons.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'visserie', 'nbDispo' => 8, 'note' => 8.6],
        ['name' => 'Cadenas 40mm', 'price' => 650, 'image' => 'img/cadenas.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'serrurerie', 'nbDispo' => 4, 'note' => 8.4],
        ['name' => 'Chevilles 8mm (100pcs)', 'price' => 420, 'image' => 'img/chevilles.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'visserie', 'nbDispo' => 25, 'note' => 5.9],
        ['name' => 'Gants de travail', 'price' => 1200, 'image' => 'img/gants.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 12, 'note' => 7.7],
        ['name' => 'Peinture blanche 1L', 'price' => 780, 'image' => 'img/peinture.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'peinture', 'nbDispo' => 0, 'note' => 6.0],
        ['name' => 'Tournevis électrique', 'price' => 2500, 'image' => 'img/tournevis.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 5, 'note' => 9.7],
        ['name' => 'Clou galvanisé (1kg)', 'price' => 350, 'image' => 'img/clou.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'visserie', 'nbDispo' => 40, 'note' => 7.4],
        ['name' => 'Échelle aluminium 3m', 'price' => 4500, 'image' => 'img/echelle.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 3, 'note' => 5.9],
        ['name' => 'Marteau de charpentier', 'price' => 1500, 'image' => 'img/marteau.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 7, 'note' => 7.3],
        ['name' => 'Pince multiprise', 'price' => 950, 'image' => 'img/pince.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 18, 'note' => 7.0],
        ['name' => 'Ruban adhésif 50m', 'price' => 250, 'image' => 'img/ruban.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 30, 'note' => 6.9],
        ['name' => 'Se à métaux', 'price' => 1800, 'image' => 'img/scie.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'outillage', 'nbDispo' => 9, 'note' => 9.7],
        ['name' => 'Vis autoforeuses (100pcs)', 'price' => 600, 'image' => 'img/vis.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'visserie', 'nbDispo' => 22, 'note' => 7.5],
        ['name' => 'Colle époxy 200ml', 'price' => 450, 'image' => 'img/colle.jpg', 'cat_key' => 'quincaillerie', 'sub_cat' => 'plomberie', 'nbDispo' => 16, 'note' => 8.6],
        ['name' => 'Ampoule LED 9W', 'price' => 450, 'image' => 'img/ampoule.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'eclairage', 'nbDispo' => 50, 'note' => 8.0],
        ['name' => 'Câble électrique 10m', 'price' => 2200, 'image' => 'img/cable.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'cables', 'nbDispo' => 10, 'note' => 5.6],
        ['name' => 'Disjoncteur 16A Legrand', 'price' => 1800, 'image' => 'img/legrand-a16.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'disjoncteurs', 'nbDispo' => 6, 'note' => 7.0],
        ['name' => 'Prise électrique double', 'price' => 550, 'image' => 'img/prise.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'prises', 'nbDispo' => 0, 'note' => 5.3],
        ['name' => 'Projecteur LED 50W', 'price' => 3200, 'image' => 'img/projecteur.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'projecteurs', 'nbDispo' => 2, 'note' => 7.7],
        ['name' => 'Rallonge électrique 5m', 'price' => 1900, 'image' => 'img/rallonge.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'prises', 'nbDispo' => 14, 'note' => 6.5],
        ['name' => 'Interrupteur blanc', 'price' => 380, 'image' => 'img/interrupteur.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'prises', 'nbDispo' => 20, 'note' => 7.3],
        ['name' => 'Boîte de dérivation Legrand', 'price' => 280, 'image' => 'img/boite-deriv.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'prises', 'nbDispo' => 9, 'note' => 5.2],
        ['name' => 'Compteur électrique', 'price' => 8500, 'image' => 'img/compteur.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'comptage', 'nbDispo' => 1, 'note' => 5.9],
        ['name' => 'Fusible 10A', 'price' => 120, 'image' => 'img/fusible.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'comptage', 'nbDispo' => 100, 'note' => 6.4],
        ['name' => 'Multiprise 4 prises', 'price' => 1200, 'image' => 'img/multiprise.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'prises', 'nbDispo' => 12, 'note' => 5.9],
        ['name' => 'Lampe halogène 100W', 'price' => 800, 'image' => 'img/halogene.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'eclairage', 'nbDispo' => 8, 'note' => 6.6],
        ['name' => 'Câble coaxial 20m', 'price' => 1500, 'image' => 'img/coaxial.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'cables', 'nbDispo' => 5, 'note' => 8.7],
        ['name' => 'Disjoncteur différentiel', 'price' => 3200, 'image' => 'img/diff.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'disjoncteurs', 'nbDispo' => 3, 'note' => 9.5],
        ['name' => 'Transformateur 220V', 'price' => 4500, 'image' => 'img/transformateur.jpg', 'cat_key' => 'electricite', 'sub_cat' => 'comptage', 'nbDispo' => 2, 'note' => 7.8],
    ];

    $promos = productPromotions();
    $brands = productBrands();
    $dbNotes = productNotes();

    foreach ($products as &$product) {
        $slug = slugifyProductName((string) $product['name']);
        $product['slug'] = $slug;
        $product['product_id'] = $slug;
        $product['brand'] = $brands[$product['sub_cat']] ?? 'BibenQuinc';
        $product['description'] = productDescription($product);

        if (isset($promos[$product['name']])) {
            $product['old_price'] = (int) $promos[$product['name']];
        }
        
        if (isset($dbNotes[$product['name']])) {
            $product['note'] = $dbNotes[$product['name']];
        }
    }
    unset($product);

    return $products;
}

function findProductBySlug(string $slug): ?array
{
    foreach (catalogueProducts() as $product) {
        if (($product['slug'] ?? '') === $slug) {
            return $product;
        }
    }

    return null;
}
function getProductIdByName(string $name): ?int
{
    try {
        require_once dirname(__DIR__, 2) . '/config/db.php';
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT idArt FROM article WHERE intitule = ? LIMIT 1');
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row ? (int)$row['idArt'] : null;
    } catch (Exception $e) {
        return null;
    }
}
