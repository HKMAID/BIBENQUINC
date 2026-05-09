  <footer class="site-footer">
    <p>© 2026 BibenQuinc — Tous droits réservés</p>
  </footer>
</div><!-- /.app -->

<!-- Cart Drawer -->
<div class="drawer-overlay" id="overlay" onclick="closeDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="drawer-head">
    <h2>Mon panier <span class="count-chip" id="cartCount">0</span></h2>
    <button class="close-btn" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-items" id="drawerItems">
    <div class="drawer-empty">Votre panier est vide</div>
  </div>
  <div class="drawer-footer">
    <div class="total-row">
      <span class="total-label">Total</span>
      <span class="total-amount" id="drawerTotal">0 <span>DA</span></span>
    </div>
    <button class="checkout-btn" onclick="confirmCheckout()">Passer à la caisse</button>
    <a href="#" class="clear-link" onclick="clearCart(); return false;">Vider le panier</a>
  </div>
</div>

<script src="views/script/script.js"></script>
<script>
function toggleUserMenu(e) {
  e.preventDefault();
  const btn = document.getElementById('userMenuBtn');
  const dd  = document.getElementById('userDropdown');
  if (!dd) return;
  const open = dd.classList.toggle('open');
  btn.classList.toggle('active', open);
}
document.addEventListener('click', function(e) {
  const menu = document.querySelector('.user-menu');
  if (menu && !menu.contains(e.target)) {
    const dd  = document.getElementById('userDropdown');
    const btn = document.getElementById('userMenuBtn');
    if (dd)  dd.classList.remove('open');
    if (btn) btn.classList.remove('active');
  }
});
document.addEventListener('DOMContentLoaded', () => {
  loadCart();
  updateCartUI();
});
function confirmCheckout() {
  if (!cart.length) return alert('Votre panier est vide.');
  alert('Fonctionnalité de paiement à venir.');
}
</script>
<?php foreach ($extraJs ?? [] as $js): ?>
<script src="<?= htmlspecialchars($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
