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

// Close dropdown when clicking outside
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
  loadCart(); // Load cart from localStorage first
  updateCartUI();
  initScrollObserver();
  updateSwipeHint('quinc');
  // Scroll to correct page on load
  const isElec = document.body.classList.contains('elec');
  if (isElec) {
    goToPage('elec');
  }
});

// Category switching
function onRadioChange(cat) {
  const category = cat === 'elec' ? 'electricite' : 'quincaillerie';
  const url = new URL(window.location);
  url.searchParams.set('category', category);
  url.searchParams.delete('sub_cat'); // Reset sub_cat when switching categories
  url.searchParams.delete('page'); // Reset page
  window.location.href = url.toString();
}

// Sort menu toggle
function toggleSortMenu(btn) {
  btn.classList.toggle('active');
  // Toggle .open on the sort-menu sibling
  const sortMenu = btn.nextElementSibling;
  if (sortMenu && sortMenu.classList.contains('sort-menu')) {
    sortMenu.classList.toggle('open');
  }
}

// Close sort menu when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.search-box')) {
    document.querySelectorAll('.sort-btn').forEach(btn => {
      btn.classList.remove('active');
    });
  }
});

// Page navigation
function goToPage(page) {
  const hPager = document.getElementById('hPager');
  const targetPage = document.getElementById(`page-${page}`);
  if (hPager && targetPage) {
    const scrollLeft = page === 'elec' ? hPager.scrollWidth / 2 : 0;
    hPager.scrollTo({ left: scrollLeft, behavior: 'smooth' });
  }
}

// Compteur dynamique
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

// Wishlist button toggle
document.querySelectorAll('.wishlist-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    this.classList.toggle('active');
  });
});