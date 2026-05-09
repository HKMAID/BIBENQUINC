<?php
// Espace professionnel - Factures
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Espace pro – BibenQuinc</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="views/style/style.css" />

  <style>
    /* ── Layout Overrides ── */
    body {
        overflow-y: auto;
        background: var(--bg);
    }
    
    .app {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        height: auto;
    }

    .dashboard-wrapper {
        display: flex;
        flex: 1;
        overflow: hidden; 
    }

    /* ── Sidebar ── */
    .dashboard-sidebar {
        width: 260px;
        background: var(--surface);
        border-right: 1px solid var(--border);
        padding: 32px 24px;
        display: flex;
        flex-direction: column;
        gap: 32px;
        position: sticky;
        top: 0;
        height: calc(100vh - 64px); 
        overflow-y: auto;
    }

    .sidebar-section h3 {
        font-family: var(--fd);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 16px;
        padding-left: 12px;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        text-decoration: none;
        color: var(--text);
        font-size: 14px;
        font-weight: 500;
        border-radius: var(--r-md);
        transition: all var(--t);
        font-family: var(--fb);
        position: relative;
        overflow: hidden;
    }

    .sidebar-nav a:hover {
        background: var(--surface-2);
        color: var(--accent);
        transform: translateX(4px);
    }

    .sidebar-nav a.active {
        background: var(--accent-glow);
        color: var(--accent);
        font-weight: 700;
    }

    .sidebar-nav a.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--accent);
        border-radius: 0 4px 4px 0;
    }

    /* ── Main Content ── */
    .dashboard-main {
        flex: 1;
        padding: 40px 48px;
        display: flex;
        flex-direction: column;
        gap: 32px;
        background: var(--bg);
        overflow-y: auto;
        height: calc(100vh - 64px);
    }

    /* ── Header Area ── */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 24px;
    }

    .dashboard-title-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .dashboard-title {
        font-family: var(--fd);
        font-size: 40px;
        font-weight: 800;
        color: var(--text);
        line-height: 1;
        letter-spacing: -0.02em;
    }

    .dashboard-subtitle {
        font-family: var(--fb);
        font-size: 15px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .btn-new {
        background: var(--text);
        color: var(--bg);
        border: none;
        padding: 12px 24px;
        border-radius: 100px;
        font-family: var(--fd);
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        cursor: pointer;
        transition: all var(--t);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-new:hover {
        background: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px var(--accent-glow);
    }

    .dashboard-search {
        font-family: var(--fb);
        font-size: 14px;
        padding: 12px 20px 12px 42px;
        width: 300px;
        border: 1.5px solid var(--border);
        border-radius: 100px;
        background: var(--surface);
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%23999999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
        background-repeat: no-repeat;
        background-position: 16px center;

        color: var(--text);
        transition: all var(--t);
        outline: none;
        box-shadow: var(--sh-sm);
    }

    .dashboard-search:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px var(--accent-glow);
        width: 320px;
    }

    .dashboard-search::placeholder {
        color: var(--text-muted);
    }

    /* ── Stat Cards ── */
    .stat-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }

    .stat-card {
        background: var(--surface);
        border-radius: var(--r-lg);
        padding: 24px;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all var(--t);
        box-shadow: var(--sh-sm);
        position: relative;
        overflow: hidden;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: 0; right: 0; bottom: 0; width: 4px;
    }

    .stat-card.c-green::after { background: #22C55E; }
    .stat-card.c-amber::after { background: #F59E0B; }
    .stat-card.c-red::after   { background: #EF4444; }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--sh-lg);
        border-color: var(--accent);
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .stat-icon.green { background: rgba(34, 197, 94, 0.12); color: #16A34A; }
    .stat-icon.amber { background: rgba(245, 158, 11, 0.12); color: #B45309; }
    .stat-icon.red   { background: rgba(239, 68, 68, 0.12); color: #BE123C; }

    .stat-text {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .stat-amount {
        font-family: var(--fd);
        font-size: 30px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.5px;
        color: var(--text);
    }

    .stat-label {
        font-family: var(--fb);
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* ── Table Area ── */
    .controls-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .filter-tabs {
        display: flex;
        gap: 8px;
        background: var(--surface-2);
        padding: 4px;
        border-radius: 100px;
        border: 1px solid var(--border);
    }

    .tab-btn {
        padding: 8px 20px;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: var(--text-muted);
        font-family: var(--fb);
        cursor: pointer;
        transition: all var(--t);
    }

    .tab-btn:hover {
        color: var(--text);
    }

    .tab-btn.active {
        background: var(--surface);
        color: var(--accent);
        box-shadow: var(--sh-sm);
    }

    .table-container {
        background: var(--surface);
        border-radius: var(--r-lg);
        border: 1px solid var(--border);
        box-shadow: var(--sh-sm);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    thead th {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 18px 24px;
        border-bottom: 2px solid var(--border);
        background: var(--surface-2);
        font-family: var(--fb);
    }

    thead th:last-child {
        text-align: right;
    }

    tbody tr {
        border-bottom: 1px solid var(--border);
        transition: all var(--t);
    }

    tbody tr:last-child {
        border-bottom: none;
    }

    tbody tr:hover {
        background: var(--surface-2);
        transform: scale(1.001);
    }

    tbody td {
        padding: 20px 24px;
        font-size: 14px;
        font-family: var(--fb);
        color: var(--text);
        vertical-align: middle;
    }

    .inv-number {
        color: var(--text);
        font-weight: 700;
        font-family: var(--fd);
        font-size: 14px;
        cursor: pointer;
        transition: color var(--t);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .inv-number::before {
        font-size: 14px;
        opacity: 0.5;
    }

    .inv-number:hover {
        color: var(--accent);
    }

    .client-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .client-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--accent-glow);
        color: var(--accent-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 12px;
        font-family: var(--fd);
    }

    .client-name {
        font-weight: 600;
        color: var(--text);
    }

    .date-cell {
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 500;
    }

    .amount-cell {
        text-align: right;
        font-family: var(--fd);
        font-weight: 800;
        font-size: 15px;
        color: var(--text);
    }

    .status-cell {
        text-align: right;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 700;
        font-family: var(--fd);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge::before {
        content: '';
        display: block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .badge.paid {
        background: rgba(34, 197, 94, 0.1);
        color: #16A34A;
    }
    .badge.paid::before { background: #16A34A; }

    .badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #B45309;
    }
    .badge.pending::before { background: #B45309; }

    .badge.overdue {
        background: rgba(239, 68, 68, 0.1);
        color: #BE123C;
    }
    .badge.overdue::before { background: #BE123C; }

    .table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-top: 1px solid var(--border);
        background: var(--surface-2);
    }

    .footer-label {
        font-size: 14px;
        color: var(--text-muted);
        font-weight: 600;
        font-family: var(--fb);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .footer-total {
        font-family: var(--fd);
        font-size: 26px;
        font-weight: 800;
        color: var(--accent);
        letter-spacing: -0.5px;
    }

    /* ── Responsive ── */
    @media (max-width: 1000px) {
        .dashboard-wrapper {
            flex-direction: column;
            overflow: auto;
        }
        .dashboard-sidebar {
            width: 100%;
            height: auto;
            position: static;
            border-right: none;
            border-bottom: 1px solid var(--border);
            padding: 24px;
            gap: 20px;
        }
        .sidebar-nav {
            flex-direction: row;
            flex-wrap: wrap;
        }
        .dashboard-main {
            padding: 24px;
            height: auto;
        }
        .controls-bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .dashboard-search {
            width: 100%;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            min-width: 700px;
        }
    }
  </style>
</head>
<body>

<div class="app">

  <!-- ═ Header ═ -->
  <header class="site-header">
    <div class="logo">
      <span class="logo-bar"></span>
      <span class="logo-image">
        <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
      </span>
    </div>

    <nav class="nav">
      <a href="index.php">🠔 Catalogue</a>
      <a href="cnx.php" class="connection-link">Déconnexion</a>
      <a href="#" class="cart-btn">
      <img src="img/profile.png" alt="Profil" style="width:20px;filter: invert(100%);">
        Mon profil
      </a>
    </nav>
  </header>

  <div class="dashboard-wrapper">
      <!-- Sidebar Navigation -->
      <aside class="dashboard-sidebar">
        <div class="sidebar-section">
          <h3>Tableau de bord</h3>
          <div class="sidebar-nav">
            <a href="#">Vue d'ensemble</a>
            <a href="#">Gestion du Stock</a>
            <a href="#">Commandes</a>
            <a href="#" class="active">Factures</a>
            <a href="#">Clients</a>
          </div>
        </div>
    
      </aside>

      <!-- Main Content -->
      <div class="dashboard-main">

        <!-- Dashboard Header -->
        <div class="dashboard-header">
          <div class="dashboard-title-group">
            <div class="dashboard-title">Factures</div>
            <div class="dashboard-subtitle">Gérez et suivez les factures de votre entreprise</div>
          </div>
          <div class="header-actions">
             <input type="text" class="dashboard-search" placeholder="Rechercher client ou n° facture…" />
             <a href="#" class="btn-new">➕ Nouvelle Facture</a>
          </div>
        </div>

        <!-- Stat Cards -->
        <div class="stat-cards">
          <div class="stat-card c-green">
            <div class="stat-icon green">✅</div>
            <div class="stat-text">
              <div class="stat-amount green">65 520 DA</div>
              <div class="stat-label">3 factures payées</div>
            </div>
          </div>
          <div class="stat-card c-amber">
            <div class="stat-icon amber">⏳</div>
            <div class="stat-text">
              <div class="stat-amount amber">66 140 DA</div>
              <div class="stat-label">3 factures en attente</div>
            </div>
          </div>
          <div class="stat-card c-red">
            <div class="stat-icon red">⚠️</div>
            <div class="stat-text">
              <div class="stat-amount red">34 350 DA</div>
              <div class="stat-label">2 factures en retard</div>
            </div>
          </div>
        </div>

        <!-- Table Area -->
        <div class="table-container">
            
          <div class="controls-bar" style="padding: 16px 24px 0;">
              <div class="filter-tabs">
                <button class="tab-btn active">Toutes</button>
                <button class="tab-btn">Payée</button>
                <button class="tab-btn">En attente</button>
                <button class="tab-btn">En retard</button>
              </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>N° Facture</th>
                <th>Client</th>
                <th>Émission</th>
                <th>Échéance</th>
                <th style="width: 140px;">Montant</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="inv-number">2026000001</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar">KB</div>
                        <div class="client-name">Karim Bensalem</div>
                    </div>
                </td>
                <td class="date-cell">15 Fév 2025</td>
                <td class="date-cell">15 Mar 2025</td>
                <td class="amount-cell">23 140 DA</td>
                <td class="status-cell"><span class="badge paid">Payée</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000002</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar" style="background: rgba(245, 158, 11, 0.1); color: #B45309;">AH</div>
                        <div class="client-name">Amira Hadj</div>
                    </div>
                </td>
                <td class="date-cell">19 Fév 2025</td>
                <td class="date-cell">19 Mar 2025</td>
                <td class="amount-cell">9 440 DA</td>
                <td class="status-cell"><span class="badge pending">En attente</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000003</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar" style="background: rgba(239, 68, 68, 0.1); color: #BE123C;">YM</div>
                        <div class="client-name">Youcef Meziane</div>
                    </div>
                </td>
                <td class="date-cell">21 Fév 2025</td>
                <td class="date-cell" style="color: #BE123C; font-weight: 700;">05 Mar 2025</td>
                <td class="amount-cell">14 450 DA</td>
                <td class="status-cell"><span class="badge overdue">En retard</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000004</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar">SO</div>
                        <div class="client-name">Salima Oukaci</div>
                    </div>
                </td>
                <td class="date-cell">23 Fév 2025</td>
                <td class="date-cell">23 Mar 2025</td>
                <td class="amount-cell">24 400 DA</td>
                <td class="status-cell"><span class="badge paid">Payée</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000005</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar" style="background: rgba(245, 158, 11, 0.1); color: #B45309;">HA</div>
                        <div class="client-name">Hassan Aouadi</div>
                    </div>
                </td>
                <td class="date-cell">01 Mar 2025</td>
                <td class="date-cell">01 Avr 2025</td>
                <td class="amount-cell">27 600 DA</td>
                <td class="status-cell"><span class="badge pending">En attente</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000006</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar">FK</div>
                        <div class="client-name">Farida Kaci</div>
                    </div>
                </td>
                <td class="date-cell">03 Mar 2025</td>
                <td class="date-cell">03 Avr 2025</td>
                <td class="amount-cell">17 980 DA</td>
                <td class="status-cell"><span class="badge paid">Payée</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000007</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar" style="background: rgba(239, 68, 68, 0.1); color: #BE123C;">MB</div>
                        <div class="client-name">Mohamed Belaid</div>
                    </div>
                </td>
                <td class="date-cell">10 Jan 2025</td>
                <td class="date-cell" style="color: #BE123C; font-weight: 700;">10 Fév 2025</td>
                <td class="amount-cell">31 290 DA</td>
                <td class="status-cell"><span class="badge overdue">En retard</span></td>
              </tr>
              <tr>
                <td><span class="inv-number">2026000008</span></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar" style="background: rgba(245, 158, 11, 0.1); color: #B45309;">RB</div>
                        <div class="client-name">Razika Bouhali</div>
                    </div>
                </td>
                <td class="date-cell">02 Mar 2025</td>
                <td class="date-cell">02 Avr 2025</td>
                <td class="amount-cell">16 660 DA</td>
                <td class="status-cell"><span class="badge pending">En attente</span></td>
              </tr>
            </tbody>
          </table>
          <div class="table-footer">
            <span class="footer-label">Total — 8 factures</span>
            <span class="footer-total">166 010 DA</span>
          </div>
        </div>

      </div>
  </div>

</div>

<script>
  // Tab switching
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
</script>

</body>
</html>
