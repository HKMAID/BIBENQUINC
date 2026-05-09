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

function toggleUserMenu(e) {
    e.preventDefault();
    document.getElementById('userDropdown')?.classList.toggle('open');
    document.getElementById('userMenuBtn')?.classList.toggle('active');
}
document.addEventListener('click', e => {
    const m = document.querySelector('.user-menu');
    if (m && !m.contains(e.target)) {
        document.getElementById('userDropdown')?.classList.remove('open');
        document.getElementById('userMenuBtn')?.classList.remove('active');
    }
});

function filterTable(sectionId, q, colIndices) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    const val = q.toLowerCase().trim();
    section.querySelectorAll('table tbody tr').forEach(tr => {
        const text = colIndices
            ? colIndices.map(i => tr.cells[i]?.textContent || '').join(' ').toLowerCase()
            : tr.textContent.toLowerCase();
        tr.style.display = text.includes(val) ? '' : 'none';
    });
}

function sortTableBy(sectionId, val) {
    if (!val) return;
    const [colStr, type, dir] = val.split(':');
    const col = parseInt(colStr, 10);
    const tbody = document.getElementById(sectionId)?.querySelector('table tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
        const aV = a.cells[col]?.textContent.trim() || '';
        const bV = b.cells[col]?.textContent.trim() || '';
        const cmp = type === 'num'
            ? parseFloat(aV.replace(/[^\d.-]/g, '') || 0) - parseFloat(bV.replace(/[^\d.-]/g, '') || 0)
            : aV.localeCompare(bV, 'fr');
        return dir === 'asc' ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
}

function filterPromos(q) {
    const val = q.toLowerCase().trim();
    document.querySelectorAll('#section-promos .promo-card').forEach(card => {
        const text = (card.querySelector('h3')?.textContent || '') +
            (card.querySelector('.category')?.textContent || '');
        card.style.display = text.toLowerCase().includes(val) ? '' : 'none';
    });
}

function toggleAddArticle() {
    const form = document.getElementById('add-article-form');
    if (!form) return;
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function previewPhoto(input) {
    const prev = document.getElementById('photo-preview');
    if (!prev) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { prev.src = e.target.result; prev.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        prev.style.display = 'none';
    }
}

function editStock(productId, currentQty) {
    const qty = prompt(`Nouveau stock pour le produit #${productId} (actuel : ${currentQty}) :`, currentQty);
    if (qty !== null && !isNaN(parseInt(qty, 10))) {
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `update_stock=1&idArt=${productId}&qty=${parseInt(qty, 10)}`
        })
            .then(r => { if (!r.ok) throw new Error(); location.reload(); })
            .catch(() => alert('Erreur lors de la mise à jour du stock.'));
    }
}

function openFacture(idFac, idAchat, client, total, date, etat) {
    alert(`Facture FAC-${String(idFac).padStart(4, '0')}\nClient : ${client}\nMontant : ${total} DA\nDate : ${date}\nStatut : ${etat}`);
}

function editPromo(productId) {
    alert('Modification de la promotion pour le produit #' + productId);
}