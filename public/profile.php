<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_db.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

if (!$authUser) {
    header('Location: cnx.php');
    exit;
}

$errors  = [];
$success = false;

/* ── Handle form POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname  = trim($_POST['firstname']       ?? '');
    $lastname   = trim($_POST['lastname']        ?? '');
    $phone      = trim($_POST['phone']           ?? '');
    $newPwd     = (string) ($_POST['password']        ?? '');
    $confirmPwd = (string) ($_POST['password_confirm'] ?? '');

    if ($firstname === '') $errors[] = 'Le prénom est obligatoire.';
    if ($lastname  === '') $errors[] = 'Le nom est obligatoire.';
    if ($phone !== '' && !preg_match('/^[0-9]{9,10}$/', $phone))
        $errors[] = 'Numéro de téléphone invalide (9 ou 10 chiffres).';

    if ($newPwd !== '') {
        if (strlen($newPwd) < 6)
            $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        if ($newPwd !== $confirmPwd)
            $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        $upd = updateUser(
            (int) $authUser['id'],
            ['firstname' => $firstname, 'lastname' => $lastname, 'phone' => $phone],
            $newPwd
        );

        if (!$upd['ok']) {
            $errors[] = $upd['error'];
        } else {
            // Rafraîchir la session
            $_SESSION['user']['firstname'] = $firstname;
            $_SESSION['user']['lastname']  = $lastname;
            $_SESSION['user']['phone']     = $phone;
            $authUser = currentUser();
            $authName = trim($firstname . ' ' . $lastname);

        setFlash('success', 'Vos informations ont été mises à jour avec succès.');
            header('Location: profile.php');
            exit;
        }       // end else (upd ok)
    }           // end if empty(errors)
}               // end POST


$flash   = getFlash();
$pageTitle = 'Mon profil — BibenQuinc';
$pageDesc  = 'Gérez vos informations personnelles sur BibenQuinc.';
$activeNav = 'profile';
$extraCss  = ['profile.css'];

include __DIR__ . '/includes/header.php';
?>

  <!-- Page Banner -->
  <div class="acc-banner">
    <div class="acc-banner-inner">
      <div class="acc-avatar"><?= mb_strtoupper(mb_substr($authName, 0, 1)) ?></div>
      <div>
        <h1 class="acc-banner-title">Mon profil</h1>
        <p class="acc-banner-sub">Gérez vos coordonnées et votre mot de passe</p>
      </div>
    </div>
  </div>

  <main class="acc-main">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul>
      </div>
    <?php endif; ?>

    <form class="profile-form" method="post" action="" id="profileForm" novalidate>

      <!-- ── Section : Coordonnées ── -->
      <section class="form-section">
        <div class="form-section-head">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          <h2>Informations personnelles</h2>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="firstname">Prénom <span class="req">*</span></label>
            <input type="text" id="firstname" name="firstname" autocomplete="given-name"
                   value="<?= htmlspecialchars($_POST['firstname'] ?? $authUser['firstname'] ?? '') ?>"
                   placeholder="Votre prénom" required>
          </div>
          <div class="form-group">
            <label for="lastname">Nom <span class="req">*</span></label>
            <input type="text" id="lastname" name="lastname" autocomplete="family-name"
                   value="<?= htmlspecialchars($_POST['lastname'] ?? $authUser['lastname'] ?? '') ?>"
                   placeholder="Votre nom" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email"
                   value="<?= htmlspecialchars($authUser['email'] ?? '') ?>"
                   placeholder="email@exemple.com" readonly>
            <span class="form-hint">L'email ne peut pas être modifié.</span>
          </div>
          <div class="form-group">
            <label for="phone">Téléphone</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel"
                   value="<?= htmlspecialchars($_POST['phone'] ?? $authUser['phone'] ?? '') ?>"
                   placeholder="+213 6XX XXX XXX">
          </div>
          <div class="form-group form-group--full">
            <label for="address">Adresse</label>
            <input type="text" id="address" name="address" autocomplete="street-address"
                   value="<?= htmlspecialchars($_POST['address'] ?? $authUser['address'] ?? '') ?>"
                   placeholder="N° rue, nom de rue">
          </div>
          <div class="form-group">
            <label for="city">Ville</label>
            <input type="text" id="city" name="city" autocomplete="address-level2"
                   value="<?= htmlspecialchars($_POST['city'] ?? $authUser['city'] ?? '') ?>"
                   placeholder="Votre ville">
          </div>
          <div class="form-group">
            <label for="zip">Code postal</label>
            <input type="text" id="zip" name="zip" autocomplete="postal-code"
                   value="<?= htmlspecialchars($_POST['zip'] ?? $authUser['zip'] ?? '') ?>"
                   placeholder="Ex: 16000">
          </div>
        </div>
      </section>

      <!-- ── Section : Mot de passe ── -->
      <section class="form-section">
        <div class="form-section-head">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <h2>Changer le mot de passe</h2>
        </div>
        <p class="form-section-note">Laissez vide si vous ne souhaitez pas changer votre mot de passe.</p>
        <div class="form-grid">
          <div class="form-group pwd-group">
            <label for="password">Nouveau mot de passe</label>
            <div class="pwd-wrap">
              <input type="password" id="password" name="password"
                     autocomplete="new-password" placeholder="Au moins 6 caractères">
              <button type="button" class="pwd-toggle" onclick="togglePwd('password', this)" title="Afficher/Masquer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
          <div class="form-group pwd-group">
            <label for="password_confirm">Confirmer le mot de passe</label>
            <div class="pwd-wrap">
              <input type="password" id="password_confirm" name="password_confirm"
                     autocomplete="new-password" placeholder="Répétez le mot de passe">
              <button type="button" class="pwd-toggle" onclick="togglePwd('password_confirm', this)" title="Afficher/Masquer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
        </div>
      </section>

      <div class="form-actions">
        <a href="catalogue.php" class="btn-secondary">Annuler</a>
        <button type="submit" class="btn-primary" id="saveBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Enregistrer
        </button>
      </div>

    </form>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function togglePwd(inputId, btn) {
  const inp = document.getElementById(inputId);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.style.opacity = show ? '1' : '0.5';
}
</script>