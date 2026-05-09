let cart = [];
const fmt = n => n.toLocaleString('fr-DZ');

function loadCart() {
  try {
    const saved = localStorage.getItem('bq_cart');
    if (saved) {
      cart = JSON.parse(saved);
    }
  } catch (e) {
    console.error('Error loading cart:', e);
    cart = [];
  }
}

function sidebarClick(el) {
  el.closest('ul').querySelectorAll('a').forEach(a => a.classList.remove('active'));
  el.classList.add('active');
}

function adjustQty(btn, delta) {
  const input = btn.parentElement.querySelector('input');
  const addBtn = btn.closest('.actions').querySelector('.add-btn');
  const nbDispo = +addBtn.dataset.nbdispo || 999;
  const newVal = Math.max(1, Math.min(nbDispo, parseInt(input.value, 10) + delta));
  input.value = newVal;
}

function triggerAddButtonAnimation(btn) {
  if (!btn) return;
  btn.classList.remove('is-added');
  btn.classList.add('is-adding');

  setTimeout(() => {
    btn.classList.remove('is-adding');
    btn.classList.add('is-added');
    setTimeout(() => btn.classList.remove('is-added'), 550);
  }, 130);
}

function addToCart(btn) {
  const name = btn.dataset.name;
  const price = +btn.dataset.price;
  const image = btn.dataset.image || '';
  const nbDispo = +btn.dataset.nbdispo || 999;
  const qtyInput = btn.closest('.actions')?.querySelector('input');
  const rawQty = qtyInput ? parseInt(qtyInput.value, 10) : 1;
  const qty = Math.max(1, Math.min(nbDispo, Number.isNaN(rawQty) ? 1 : rawQty));

  if (qty > nbDispo) {
    alert(`Quantité demandée dépasse le stock disponible (${nbDispo})`);
    return;
  }

  const ex = cart.find(i => i.name === name);
  if (ex) {
    const newQty = ex.qty + qty;
    if (newQty > nbDispo) {
      alert(`Vous avez déjà ${ex.qty} dans le panier. Stock max : ${nbDispo}`);
      ex.qty = nbDispo;
    } else {
      ex.qty = newQty;
    }
    ex.nbDispo = nbDispo;
  } else {
    cart.push({ name, price, image, qty, nbDispo });
  }
  saveCart();
  updateCartUI();
  triggerAddButtonAnimation(btn);

  const cc = document.getElementById('cartCount');
  if (cc) { cc.classList.add('ping'); setTimeout(() => cc.classList.remove('ping'), 250); }
}

function changeItemQty(name, delta) {
  const item = cart.find(i => i.name === name);
  if (!item) return;
  const max = item.nbDispo || 999;
  const newQty = item.qty + delta;

  if (newQty < 1) {
    removeItem(name);
    return;
  }
  if (newQty > max) {
    return;
  }

  item.qty = newQty;
  saveCart();
  updateCartUI();
}

function setItemQty(name, val) {
  const item = cart.find(i => i.name === name);
  if (!item) return;
  const qty = parseInt(val, 10);
  if (isNaN(qty) || qty < 1) {
    updateCartUI();
    return;
  }
  const max = item.nbDispo || 999;
  if (qty > max) {
    item.qty = max;
    alert(`Quantité max disponible : ${max}`);
  } else {
    item.qty = qty;
  }
  saveCart();
  updateCartUI();
}

function removeItem(name) {
  cart = cart.filter(i => i.name !== name);
  saveCart();
  updateCartUI();
}

function clearCart() {
  if (cart.length === 0) return;
  if (confirm('Êtes-vous sûr de vouloir vider le panier ?')) {
    cart = [];
    localStorage.removeItem('bq_cart');
    updateCartUI();
  }
}
function saveCart() { localStorage.setItem('bq_cart', JSON.stringify(cart)); }

const getTotal = () => cart.reduce((s, i) => s + i.price * i.qty, 0);
const getTotalQty = () => cart.reduce((s, i) => s + i.qty, 0);

function updateCartUI() {
  const total = getTotal(), qty = getTotalQty();
  const el = id => document.getElementById(id);
  if (el('badge')) el('badge').textContent = fmt(total) + ' DA';
  if (el('cartCount')) el('cartCount').textContent = qty;
  if (el('chipCount')) el('chipCount').textContent = qty;
  if (el('drawerTotal')) el('drawerTotal').innerHTML = fmt(total) + ' <span>DA</span>';

  const c = el('drawerItems');
  if (!c) return;

  if (!cart.length) {
    c.innerHTML = `<div class="drawer-empty">Votre panier est vide.</div>`;
    return;
  }

  c.innerHTML = cart.map(i => {
    const safeName = i.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    return `
    <div class="cart-item" id="ci-${encodeURIComponent(i.name)}">
      <div class="cart-item-image">
        <img src="${i.image}" alt="">
      </div>
      <div class="cart-item-details">
        <div class="cart-item-name">${i.name}</div>
        <div class="cart-item-price">${fmt(i.price * i.qty)} DA <span style="color:var(--text-muted);font-size:12px;font-weight:400;">(${fmt(i.price)}/u)</span></div>
        <div class="cart-item-qty">
          <button type="button" onclick="changeItemQty('${safeName}', -1)">−</button>
          <span>${i.qty}</span>
          <button type="button" onclick="changeItemQty('${safeName}', 1)"${i.qty >= (i.nbDispo || 999) ? ' disabled' : ''}>+</button>
        </div>
      </div>
      <button class="cart-item-remove" title="Supprimer" onclick="removeItem('${safeName}')">✕</button>
    </div>`;
  }).join('');
}

function openDrawer() {
  document.getElementById('cartDrawer').classList.add('open');
  document.getElementById('overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('cartDrawer').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
  document.body.style.overflow = '';
}
function goToCart() { openDrawer(); }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeDrawer();
  }
});

let currentPage = 'quinc';
let isScrolling = false;

function goToPage(key) {
  isScrolling = true;
  const target = document.getElementById('page-' + key);
  if (target) target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
  applyTheme(key);
  syncRadio(key);
  updateSwipeHint(key);
  setTimeout(() => { isScrolling = false; }, 700);
}

function applyTheme(key) {
  currentPage = key;
  document.body.classList.toggle('elec', key === 'elec');
}

function syncRadio(key) {
  const rq = document.getElementById('radio-quinc');
  const re = document.getElementById('radio-elec');
  if (rq) rq.checked = key === 'quinc';
  if (re) re.checked = key === 'elec';
}

function updateSwipeHint(key) {
  const hint = document.getElementById('swipeHint');
  if (!hint) return;
  if (key === 'elec') {
    hint.innerHTML = '← Quincaillerie';
    hint.onclick = () => goToPage('quinc');
  } else {
    hint.innerHTML = 'Électricité →';
    hint.onclick = () => goToPage('elec');
  }
}

function onRadioChange(key) { goToPage(key); }

function initScrollObserver() {
  const pager = document.getElementById('hPager');
  if (!pager) return;
  const observer = new IntersectionObserver(entries => {
    if (isScrolling) return;
    entries.forEach(entry => {
      if (entry.intersectionRatio < 0.5) return;
      const key = entry.target.id === 'page-elec' ? 'elec' : 'quinc';
      if (key === currentPage) return;
      applyTheme(key);
      syncRadio(key);
      updateSwipeHint(key);
    });
  }, { root: pager, threshold: 0.5 });
  ['page-quinc', 'page-elec'].forEach(id => {
    const el = document.getElementById(id);
    if (el) observer.observe(el);
  });
}