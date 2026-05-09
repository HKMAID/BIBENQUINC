'use strict';

const SECTIONS = ['clients', 'stock', 'achats', 'promos'];

function showSection(id, linkEl) {
    SECTIONS.forEach(s => {
        const el = document.getElementById('section-' + s);
        if (el) el.style.display = s === id ? '' : 'none';
    });
    document.querySelectorAll('.dash-nav-link').forEach(l => l.classList.remove('active'));
    if (linkEl) linkEl.classList.add('active');
    history.replaceState(null, '', '?section=' + id);
}

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(location.search);
    let sec = params.get('section');
    if (!sec || !SECTIONS.includes(sec)) sec = 'clients';
    const link = document.querySelector(`.dash-nav-link[onclick*="'${sec}'"]`);
    if (link) showSection(sec, link);
    else showSection(sec, null);
});

/* ── User Menu Toggle ── */
function toggleUserMenu(e) {
    e.preventDefault();
    const dropdown = document.getElementById('userDropdown');
    const btn = document.getElementById('userMenuBtn');
    dropdown.classList.toggle('open');
    btn.classList.toggle('active');
}

document.addEventListener('click', e => {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu && !userMenu.contains(e.target)) {
        document.getElementById('userDropdown')?.classList.remove('open');
        document.getElementById('userMenuBtn')?.classList.remove('active');
    }
});

/* ── Edit Stock – modal inline, pas de prompt() natif ── */
function editStock(productId, currentQty) {
    const modal = document.getElementById('stockModal');
    const input = document.getElementById('stock-qty-input');
    const label = document.getElementById('stock-product-id');
    if (!modal) return;

    label.textContent = '#' + productId;
    input.value = currentQty ?? '';
    modal.dataset.productId = productId;
    modal.classList.add('open');
    input.focus();
    input.select();
}

function closeStockModal() {
    document.getElementById('stockModal')?.classList.remove('open');
}

function submitStockUpdate() {
    const modal = document.getElementById('stockModal');
    const input = document.getElementById('stock-qty-input');
    const productId = modal?.dataset.productId;
    const qty = parseInt(input.value, 10);

    if (!productId || isNaN(qty) || qty < 0) {
        input.classList.add('input-error');
        input.focus();
        return;
    }
    input.classList.remove('input-error');

    const btn = document.getElementById('stock-save-btn');
    btn.disabled = true;
    btn.textContent = 'Enregistrement…';

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `update_stock=1&idArt=${productId}&qty=${qty}`
    })
        .then(r => {
            if (!r.ok) throw new Error('Erreur réseau');
            closeStockModal();
            /* Mettre à jour la cellule sans rechargement complet */
            const row = document.querySelector(`[data-art-id="${productId}"]`);
            if (row) {
                row.querySelector('.stock-qty').textContent = qty + ' unités';
                const badge = row.querySelector('.badge');
                if (qty === 0) { badge.className = 'badge danger'; badge.textContent = 'Rupture'; }
                else if (qty < 10) { badge.className = 'badge warning'; badge.textContent = 'Faible'; }
                else { badge.className = 'badge success'; badge.textContent = 'En stock'; }
            } else {
                location.reload();
            }
        })
        .catch(() => {
            showToast('Erreur lors de la mise à jour.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Enregistrer';
        });
}

/* ── Facture Modal ── */
function openFacture(idFac, idAchat, client, total, date, etat) {
    const modal = document.getElementById('factureModal');
    if (!modal) return;

    document.getElementById('fac-id').textContent = 'FAC-' + String(idFac).padStart(4, '0');
    document.getElementById('fac-order').textContent = 'BQ-2026-' + String(idAchat).padStart(4, '0');
    document.getElementById('fac-client').textContent = client;
    document.getElementById('fac-total').textContent = total + ' DA';
    document.getElementById('fac-date').textContent = date;

    const badge = document.getElementById('fac-etat');
    badge.textContent = etat === 'payée' ? 'Payée' : 'Non payée';
    badge.className = 'badge ' + (etat === 'payée' ? 'success' : 'warning');

    const cont = document.getElementById('fac-items');
    cont.innerHTML = `<div class="fac-loading">
        <span class="fac-spinner"></span> Chargement des articles…
    </div>`;

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('facture_detail.php?idAchat=' + idAchat)
        .then(r => {
            if (!r.ok) throw new Error();
            return r.json();
        })
        .then(items => {
            if (!items.length) {
                cont.innerHTML = '<p class="fac-empty">Aucun article trouvé.</p>';
                return;
            }
            cont.innerHTML = items.map(i => `
                <div class="fac-item">
                    <div class="fac-item-info">
                        <span class="fac-item-name">${escHtml(i.intitule)}</span>
                        <span class="fac-item-meta">Qté : ${i.quantite} × ${Number(i.prix).toLocaleString('fr-DZ')} DA</span>
                    </div>
                    <span class="fac-item-total">${(i.quantite * i.prix).toLocaleString('fr-DZ')} DA</span>
                </div>`).join('');
        })
        .catch(() => {
            cont.innerHTML = '<p class="fac-empty" style="color:var(--danger)">Erreur de chargement des articles.</p>';
        });
}

function closeFacture() {
    document.getElementById('factureModal')?.classList.remove('open');
    document.body.style.overflow = '';
}

/* ── Edit Promo – modal dédié ── */
function editPromo(productId) {
    const modal = document.getElementById('editPromoModal');
    if (!modal) { alert('Modification de la promotion pour le produit #' + productId); return; }
    modal.querySelector('[name="promo_idart"]').value = productId;
    modal.querySelector('#edit-promo-id-label').textContent = '#' + productId;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeEditPromo() {
    document.getElementById('editPromoModal')?.classList.remove('open');
    document.body.style.overflow = '';
}

/* ── Fermer les modals avec Escape ── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeFacture();
        closeStockModal();
        closeEditPromo();
    }
});

/* ── Fermer en cliquant sur l'overlay ── */
document.addEventListener('click', e => {
    ['factureModal', 'stockModal', 'editPromoModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
});

/* ── Toast Notifications ── */
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${escHtml(message)}</span>
        <button onclick="this.parentElement.remove()" aria-label="Fermer">×</button>`;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('visible'));
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

/* ── Utilitaire ── */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}