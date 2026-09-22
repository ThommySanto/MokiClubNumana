<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/eventi_functions.php";

$prenotazioniNonLette = contaPrenotazioniNonLette($conn);

// Recupero statistiche reali
$query_iscritti = "SELECT COUNT(*) as totale FROM iscritti";
$res_iscritti = $conn->query($query_iscritti);
$tot_iscritti = $res_iscritti->fetch_assoc()['totale'] ?? 0;

$query_rimessaggi = "SELECT COUNT(*) as totale FROM rimessaggi";
$res_rimessaggi = $conn->query($query_rimessaggi);
$tot_rimessaggi = $res_rimessaggi->fetch_assoc()['totale'] ?? 0;

$extraCssFiles = ['/assets/css/admin-dashboard.css', '/assets/css/admin-eventi.css'];
$pageTitle = "Dashboard Admin - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-dashboard-wrapper">
   <!-- Header Dashboard -->
   <header class="db-header-section">
      <div class="db-title-area">
         <h1>Moki Control <span class="green-dot">.</span></h1>
         <p>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION["admin"]); ?></strong>. Pannello di controllo
            centrale.</p>
      </div>
      <div class="db-status-badge">
         <span class="status-dot"></span> System Online
      </div>
   </header>

   <!-- Sezione Statistiche Rapide -->
   <div class="stats-overview">
      <div class="neu-stat-card">
         <span class="stat-label">Iscritti al Club</span>
         <div class="stat-value"><?php echo $tot_iscritti; ?></div>
         <div class="stat-trend positive">Membri attivi</div>
      </div>
      <div class="neu-stat-card">
         <span class="stat-label">Rimessaggi Totali</span>
         <div class="stat-value"><?php echo $tot_rimessaggi; ?></div>
         <div class="stat-trend">Tavole in magazzino</div>
      </div>
   </div>

   <!-- Bento Grid Navigazione -->
   <div class="admin-dashboard-bento">

      <!-- Iscritti (Card Grande) -->
      <a href="iscritti.php" class="bento-card bento-iscritti">
         <div class="bento-icon">🏄‍♂️</div>
         <div class="bento-text">
            <h3>Gestisci Iscritti</h3>
            <p>Anagrafica, pagamenti e certificati.</p>
         </div>
      </a>

      <!-- Rimessaggi -->
      <a href="rimessaggi.php" class="bento-card bento-rimessaggi">
         <div class="bento-icon">📦</div>
         <div class="bento-text">
            <h3>Rimessaggi</h3>
            <p>Slot tavole e pagamenti.</p>
         </div>
      </a>

      <!-- Eventi -->
      <a href="eventi.php" class="bento-card bento-rimessaggi">
         <div class="bento-icon">🌊</div>
         <div class="bento-text">
            <h3>I Nostri Eventi
               <?php if ($prenotazioniNonLette > 0): ?>
                  <span class="admin-badge-nuove"><?php echo $prenotazioniNonLette; ?></span>
               <?php endif; ?>
            </h3>
            <p>Uscite, tavole e prenotazioni.</p>
         </div>
      </a>

      <!-- Registro Presenze -->
      <a href="presenze.php" class="bento-card bento-rimessaggi">
         <div class="bento-icon">🕐</div>
         <div class="bento-text">
            <h3>Registro Presenze</h3>
            <p>Cartellino digitale, timbrature e storico ore.</p>
         </div>
      </a>

      <!-- Utility Grid (Full Width) -->
      <div class="bento-utility-grid">
         <a href="email_log.php" class="bento-card mini-bento">
            <div class="bento-icon-small">📧</div>
            <span>Log Email</span>
         </a>
         <a href="gestione_admin.php" class="bento-card mini-bento">
            <div class="bento-icon-small">🛡️</div>
            <span>Gestione Admin</span>
         </a>
         <a href="logout.php" class="bento-card mini-bento bento-logout">
            <div class="bento-icon-small">🚪</div>
            <span>Scollegati</span>
         </a>
      </div>


      <!-- Card per il Pannello di InfinityFree -->
      <?php if ($_SESSION['admin']): ?>
      <div class="neu-stat-card">
          <a href="https://dash.infinityfree.com/accounts/if0_41207556/filemanager" target="_blank" class="dashboard-filemanager-link">
              <div class="bento-icon">🔗</div>
              <div class="bento-text">
                  <h3>Pannello InfinityFree</h3>
                  <p>Accedi al pannello di controllo di InfinityFree.</p>
              </div>
          </a>
      </div>
      <?php endif; ?>

   </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>