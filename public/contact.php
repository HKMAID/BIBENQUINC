<?php
session_start();
// Page de contact - BibenQuinc
$nom   = $_SESSION['nom']   ?? $_SESSION['prenom'] ?? 'Client';
$email = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contact – BibenQuinc</title>
    <meta name="description" content="Contactez BibenQuinc pour toute réclamation ou support technique. Notre équipe vous répond rapidement.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="contact.css">
</head>
<body class="contact-body">
  <div class="contact-app">

    <a href="cnx.php" class="contact-login-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Connexion
    </a>

    <div class="contact-split">

      <!-- Panneau gauche -->
      <div class="contact-left">
        <div class="contact-logo-container">

          <div class="logo contact-logo">
            <span class="logo-image">
              <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
            </span>
          </div>

          <p class="contact-subtitle">Votre partenaire de confiance pour la quincaillerie et l'électricité</p>

          <div class="contact-info">
            <div class="contact-info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.56 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 5.61 5.61l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              07 92 97 61 28
            </div>
            <div class="contact-info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              contact@bibenquinc.dz
            </div>
            <div class="contact-info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Ouzellaguen, Béjaia, Algérie
            </div>
          </div>

        </div>
      </div>

      <div class="contact-right">
        <div class="contact-content">

          <h1 class="contact-title">Contactez-<span>nous</span></h1>
          <p class="contact-desc">Nous sommes à votre disposition pour répondre à toutes vos questions et vous accompagner dans vos projets.</p>

          <form class="contact-form" id="contact-form" method="POST" action="#">
            <div class="form-group">
              <label for="subject" class="form-label">Sujet</label>
              <select id="subject" name="subject" class="form-input" onchange="toggleAutre(this)" required>
                <option value="">Sélectionnez un sujet</option>
                <option value="reclam">Réclamation</option>
                <option value="support">Support technique</option>
                <option value="autre">Autre</option>
              </select>
            </div>

            <div class="form-group" id="autre-group" style="display:none">
              <label for="autre_sujet" class="form-label">Précisez votre sujet</label>
              <input type="text" id="autre_sujet" name="autre_sujet" class="form-input"
                placeholder="Ex : partenariat, réclamation...">
            </div>

            <div class="form-group">
              <label for="message" class="form-label">Message</label>
              <textarea id="message" name="message" class="form-input" rows="5" placeholder="Décrivez votre besoin en détail..." required></textarea>
            </div>

            <button type="submit" class="contact-btn" id="contact-submit">
              Envoyer le message
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>

          </form>
        </div>
      </div>

    </div>
  </div>
</body>
<script>
function toggleAutre(sel) {
  const group = document.getElementById('autre-group');
  const input = document.getElementById('autre_sujet');
  const show  = sel.value === 'autre';
  group.style.display = show ? 'block' : 'none';
  input.required      = show;
  if (!show) input.value = '';
}

document.getElementById('contact-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const submitBtn = document.getElementById('contact-submit');
  const originalText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = 'Envoi en cours...';

  try {
    const formData = new FormData(this);
    const response = await fetch('contact_submit.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      alert('Message envoyé avec succès !');
      this.reset();
      toggleAutre(document.getElementById('subject'));
    } else {
      alert('Erreur : ' + result.error);
    }
  } catch (error) {
    alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
    console.error('Erreur:', error);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
});
</script>
</html>