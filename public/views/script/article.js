function adjustArticleQty(delta) {
    const input = document.getElementById('articleQty');
    if (!input) return;

    const min = parseInt(input.min || '1', 10);
    const max = parseInt(input.max || '1', 10);
    const current = parseInt(input.value || String(min), 10);
    const next = Math.max(min, Math.min(max, current + delta));
    input.value = String(next);
}

function normalizeArticleQty(forceDefault = false) {
    const input = document.getElementById('articleQty');
    if (!input) return;
    const min = parseInt(input.min || '1', 10);
    const max = parseInt(input.max || '1', 10);

    if (input.value.trim() === '') {
        if (forceDefault) {
            input.value = String(min);
        }
        return;
    }

    let current = parseInt(input.value, 10);
    if (Number.isNaN(current)) {
        input.value = String(min);
        return;
    }

    current = Math.max(min, Math.min(max, current));
    input.value = String(current);
}

document.addEventListener('DOMContentLoaded', () => {
    loadCart();
    updateCartUI();
});

function toggleUserMenu(e) {
    e.preventDefault();
    const btn = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userDropdown');
    if (!btn || !dropdown) return;
    const isOpen = dropdown.classList.contains('open');
    dropdown.classList.toggle('open', !isOpen);
    btn.classList.toggle('active', !isOpen);
}

document.addEventListener('click', function (e) {
    const userMenu = document.querySelector('.user-menu');
    if (!userMenu || userMenu.contains(e.target)) return;
    const dropdown = document.getElementById('userDropdown');
    const btn = document.getElementById('userMenuBtn');
    if (dropdown) dropdown.classList.remove('open');
    if (btn) btn.classList.remove('active');
});