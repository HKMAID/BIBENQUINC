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

document.addEventListener('click', function (e) {
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
  initScrollObserver();
  updateSwipeHint('quinc');
  const isElec = document.body.classList.contains('elec');
  if (isElec) {
    goToPage('elec');
  }
});

function onRadioChange(cat) {
  const category = cat === 'elec' ? 'electricite' : 'quincaillerie';
  const url = new URL(window.location);
  url.searchParams.set('category', category);
  url.searchParams.delete('cat');
  url.searchParams.delete('page');
  window.location.href = url.toString();
}

function toggleSortMenu(btn) {
  btn.classList.toggle('active');
  const sortMenu = btn.nextElementSibling;
  if (sortMenu && sortMenu.classList.contains('sort-menu')) {
    sortMenu.classList.toggle('open');
  }
}

document.addEventListener('click', function (e) {
  if (!e.target.closest('.search-box')) {
    document.querySelectorAll('.sort-btn').forEach(btn => {
      btn.classList.remove('active');
    });
  }
});

function goToPage(page) {
  const hPager = document.getElementById('hPager');
  const targetPage = document.getElementById(`page-${page}`);
  if (hPager && targetPage) {
    const scrollLeft = page === 'elec' ? hPager.scrollWidth / 2 : 0;
    hPager.scrollTo({ left: scrollLeft, behavior: 'smooth' });
  }
}

function updateProductCount() {
  const visibleProducts = document.querySelectorAll('.product-card:not(.hidden)');
  const quincCount = document.getElementById('product-count-quinc');
  const elecCount = document.getElementById('product-count-elec');

  if (quincCount) {
    const quincProducts = Array.from(visibleProducts).filter(card =>
      card.getAttribute('data-category') === 'quincaillerie'
    );
    quincCount.textContent = `${quincProducts.length} articles`;
  }

  if (elecCount) {
    const elecProducts = Array.from(visibleProducts).filter(card =>
      card.getAttribute('data-category') === 'electricite'
    );
    elecCount.textContent = `${elecProducts.length} articles`;
  }
}

/**
 * Toggle wishlist via AJAX (add/remove favorite)
 * Called by wishlist button onclick="toggleWishlist(this)"
 */
function toggleWishlist(btn) {
  const name = btn.dataset.productName;
  if (!name) return;

  const isFav = btn.classList.contains('active');
  const action = isFav ? 'remove' : 'add';

  // Optimistic UI update
  btn.classList.toggle('active');
  const newTitle = isFav ? 'Ajouter aux favoris' : 'Retirer des favoris';
  btn.title = newTitle;
  btn.setAttribute('aria-label', newTitle);
  btn.disabled = true;

  fetch('wishlist_ajax.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, name })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.ok) {
      // Revert on error
      btn.classList.toggle('active');
      btn.title = isFav ? 'Retirer des favoris' : 'Ajouter aux favoris';
      btn.setAttribute('aria-label', btn.title);
      if (data.redirect) {
        window.location.href = data.redirect;
      } else {
        console.warn('Wishlist error:', data.error);
      }
    }
  })
  .catch(() => {
    // Revert on network error
    btn.classList.toggle('active');
    btn.title = isFav ? 'Retirer des favoris' : 'Ajouter aux favoris';
    btn.setAttribute('aria-label', btn.title);
  })
  .finally(() => {
    btn.disabled = false;
  });
}

/**
 * Toggle the sidebar on mobile
 */
function toggleSidebarMobile(sidebarId, btn) {
  const sidebar = document.getElementById(sidebarId);
  if (!sidebar) return;
  const isOpen = sidebar.classList.toggle('mobile-open');
  btn.classList.toggle('open', isOpen);
  btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}