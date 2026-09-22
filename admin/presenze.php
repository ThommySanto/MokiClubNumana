<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

// ── Utenti autorizzati ────────────────────────────────────────────────────────
$utenti_autorizzati = ['thomas', 'riccardo', 'carlo', 'admin'];
$utente_corrente    = strtolower(trim($_SESSION['admin'] ?? ''));
$accesso_ok         = in_array($utente_corrente, $utenti_autorizzati, true);

$extraCssFiles = ['/assets/css/admin-presenze.css'];
$pageTitle     = 'Registro Presenze – Moki SUP Club';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="presenze-wrapper">

<?php if (!$accesso_ok): ?>
    <!-- Accesso negato -->
    <div class="accesso-negato">
        <div class="icon-lock">🔒</div>
        <h2>Accesso non autorizzato</h2>
        <p>Solo Thomas, Riccardo e Carlo possono accedere al registro presenze.</p>
        <a href="dashboard.php" style="color:var(--secondary-color);font-weight:700;">← Torna alla Dashboard</a>
    </div>

<?php else: ?>

    <!-- ── HEADER ──────────────────────────────────────────────────────────── -->
    <header class="presenze-header">
        <div class="presenze-title-area">
            <h1>Registro Presenze<span style="color:var(--secondary-color)">.</span></h1>
            <p>Ciao, <strong><?php echo htmlspecialchars($_SESSION['admin']); ?></strong> — timbra entrata e uscita ogni giornata lavorativa.</p>
        </div>
        <div class="db-status-badge">
            <span class="status-dot"></span> Sistema attivo
        </div>
    </header>

    <!-- ── CARD TIMBRATURA ─────────────────────────────────────────────────── -->
    <div class="timbratura-card">
        <h2>⏱ Cartellino</h2>
        <div class="ora-corrente" id="oraLive">--:--:--</div>
        <div class="data-corrente" id="dataLive">--</div>

        <!-- Stato turno -->
        <div class="stato-turno" id="statoTurno">
            <span class="stato-dot"></span>
            <span id="statoTurnoTesto">Caricamento…</span>
        </div>

        <!-- Bottoni -->
        <div class="timbratura-buttons">
            <button class="btn-timbra btn-entrata" id="btnEntrata" onclick="timbra('entrata')" disabled>
                <span class="timbra-icon">🟢</span>
                Timbra Entrata
            </button>
            <button class="btn-timbra btn-uscita" id="btnUscita" onclick="apriModalNota()" disabled>
                <span class="timbra-icon">🔴</span>
                Timbra Uscita
            </button>
        </div>

        <!-- Info geolocalizzazione -->
        <div class="geo-info" id="geoInfo">
            <span>📍</span>
            <span id="geoTesto">Rilevamento posizione…</span>
        </div>
    </div>

    <!-- ── MODAL NOTA USCITA ───────────────────────────────────────────────── -->
    <div class="modal-overlay" id="modalNota">
        <div class="modal-box">
            <h3>📝 Nota uscita (opzionale)</h3>
            <p>Aggiungi un commento alla giornata lavorativa, oppure lascia vuoto.</p>
            <textarea class="modal-textarea" id="notaText" placeholder="es. Gita in barca, lavori in deposito, manutenzione…" maxlength="300"></textarea>
            <div class="modal-buttons">
                <button class="btn-modal btn-annulla" onclick="chiudiModal()">Annulla</button>
                <button class="btn-modal btn-conferma" onclick="timbra('uscita')">✓ Conferma Uscita</button>
            </div>
        </div>
    </div>

    <!-- ── STATISTICHE MESE CORRENTE ──────────────────────────────────────── -->
    <div class="stats-presenze" id="statsPresenze">
        <div class="stat-pres-card">
            <div class="stat-pres-label">Giorni lavorati</div>
            <div class="stat-pres-value" id="statGiorni">–</div>
            <div class="stat-pres-sub">questo mese</div>
        </div>
        <div class="stat-pres-card">
            <div class="stat-pres-label">Ore totali</div>
            <div class="stat-pres-value verde" id="statOre">–</div>
            <div class="stat-pres-sub">questo mese</div>
        </div>
        <div class="stat-pres-card">
            <div class="stat-pres-label">Media giornaliera</div>
            <div class="stat-pres-value" id="statMedia">–</div>
            <div class="stat-pres-sub">ore/giorno</div>
        </div>
    </div>

    <!-- ── SEZIONE CALENDARIO / TABELLA ───────────────────────────────────── -->
    <div class="calendario-section">
        <div class="calendario-header">
            <h2>📅 Storico Presenze</h2>

            <!-- Navigazione mese -->
            <div class="nav-mese">
                <button class="btn-mese" onclick="cambiaMese(-1)" title="Mese precedente">‹</button>
                <span class="label-mese" id="labelMese">–</span>
                <button class="btn-mese" onclick="cambiaMese(1)" title="Mese successivo">›</button>
            </div>

            <!-- Filtro utente -->
            <div class="filtro-utente">
                <button class="btn-filtro attivo" data-utente="tutti" onclick="setFiltro('tutti', this)">Tutti</button>
                <button class="btn-filtro" data-utente="thomas"   onclick="setFiltro('thomas', this)">Thomas</button>
                <button class="btn-filtro" data-utente="riccardo" onclick="setFiltro('riccardo', this)">Riccardo</button>
                <button class="btn-filtro" data-utente="carlo"    onclick="setFiltro('carlo', this)">Carlo</button>
            </div>
        </div>

        <!-- Tabella -->
        <div class="table-wrapper">
            <table class="tabella-presenze">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Utente</th>
                        <th>Tipo</th>
                        <th>Ore lavorate</th>
                        <th>Nota</th>
                        <th>Posizione</th>
                    </tr>
                </thead>
                <tbody id="tabellaBody">
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="spinner"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Export -->
        <div class="export-bar">
            <button class="btn-export" onclick="esportaCSV()">
                📥 Esporta CSV
            </button>
        </div>
    </div>

<?php endif; ?>
</main>

<!-- ── TOAST CONTAINER ───────────────────────────────────────────────────── -->
<div class="toast-container" id="toastContainer"></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// ═══════════════════════════════════════════════════════════════
//  REGISTRO PRESENZE — JavaScript
// ═══════════════════════════════════════════════════════════════

const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const UTENTE       = <?php echo json_encode($utente_corrente); ?>;
const AJAX_URL     = 'ajax_presenze.php';

// Formatta mese come "YYYY-MM" senza usare toISOString() (evita sfasamento UTC)
function meseToString(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    return y + '-' + m;
}
let meseCorrente = meseToString(new Date());
let filtroUtente   = 'tutti';
let posLat         = null;
let posLng         = null;
let turnoAperto    = false;

// ── Orologio in tempo reale ────────────────────────────────────────────────
function aggiornaOrologio() {
    const now = new Date();
    document.getElementById('oraLive').textContent = now.toLocaleTimeString('it-IT');
    document.getElementById('dataLive').textContent = now.toLocaleDateString('it-IT', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
}
aggiornaOrologio();
setInterval(aggiornaOrologio, 1000);

// ── Geolocalizzazione ──────────────────────────────────────────────────────
function rilevaPosizione() {
    const geoEl  = document.getElementById('geoInfo');
    const testoEl = document.getElementById('geoTesto');

    if (!navigator.geolocation) {
        geoEl.className = 'geo-info geo-err';
        testoEl.textContent = 'Geolocalizzazione non supportata da questo browser.';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            posLat = pos.coords.latitude;
            posLng = pos.coords.longitude;
            const acc = Math.round(pos.coords.accuracy);
            geoEl.className = 'geo-info geo-ok';
            testoEl.textContent = `Posizione rilevata (±${acc}m)`;
        },
        (err) => {
            geoEl.className = 'geo-info geo-err';
            testoEl.textContent = 'Posizione non disponibile — potrai comunque timbrare.';
        },
        { enableHighAccuracy: false, timeout: 8000, maximumAge: 60000 }
    );
}
rilevaPosizione();

// ── Carica stato turno ─────────────────────────────────────────────────────
async function caricaStato() {
    try {
        const res  = await fetch(`${AJAX_URL}?action=stato`);
        const data = await res.json();
        if (data.ok) aggiornaUI(data.turno_aperto);
    } catch (e) {
        console.error('Errore stato:', e);
    }
}

function aggiornaUI(aperto) {
    turnoAperto = aperto;
    const statoEl   = document.getElementById('statoTurno');
    const testoEl   = document.getElementById('statoTurnoTesto');
    const btnEntrata = document.getElementById('btnEntrata');
    const btnUscita  = document.getElementById('btnUscita');

    if (aperto) {
        statoEl.className   = 'stato-turno attivo';
        testoEl.textContent = 'Turno in corso';
        btnEntrata.disabled = true;
        btnUscita.disabled  = false;
    } else {
        statoEl.className   = 'stato-turno';
        testoEl.textContent = 'Nessun turno in corso';
        btnEntrata.disabled = false;
        btnUscita.disabled  = true;
    }
}

// ── Modal nota ─────────────────────────────────────────────────────────────
function apriModalNota() {
    document.getElementById('notaText').value = '';
    document.getElementById('modalNota').classList.add('aperto');
    setTimeout(() => document.getElementById('notaText').focus(), 100);
}

function chiudiModal() {
    document.getElementById('modalNota').classList.remove('aperto');
}

// Chiudi modal cliccando overlay
document.getElementById('modalNota').addEventListener('click', function(e) {
    if (e.target === this) chiudiModal();
});

// ── Timbratura ─────────────────────────────────────────────────────────────
async function timbra(tipo) {
    chiudiModal();

    const nota = tipo === 'uscita'
        ? (document.getElementById('notaText').value ?? '').trim()
        : '';

    const btnId = tipo === 'entrata' ? 'btnEntrata' : 'btnUscita';
    const btn   = document.getElementById(btnId);
    const orig  = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Salvataggio…';

    const body = new URLSearchParams({
        action:      'timbra',
        csrf_token:  CSRF_TOKEN,
        tipo:        tipo,
        lat:         posLat ?? '',
        lng:         posLng ?? '',
        nota:        nota,
    });

    try {
        const res  = await fetch(AJAX_URL, { method: 'POST', body });
        const data = await res.json();

        if (data.ok) {
            mostraToast(data.msg, 'success');
            aggiornaUI(tipo === 'entrata');
            // Aggiorna tabella e statistiche
            setTimeout(() => {
                caricaPresenze();
            }, 600);
        } else {
            mostraToast(data.msg, 'error');
        }
    } catch (e) {
        mostraToast('Errore di rete. Riprova.', 'error');
    } finally {
        btn.innerHTML = orig;
        // I pulsanti vengono riabilitati da aggiornaUI()
    }
}

// ── Navigazione mese ───────────────────────────────────────────────────────
function cambiaMese(delta) {
    const [y, m] = meseCorrente.split('-').map(Number);
    const data   = new Date(y, m - 1 + delta, 1);
    meseCorrente = meseToString(data);
    caricaPresenze();
}

function setFiltro(utente, btn) {
    filtroUtente = utente;
    document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('attivo'));
    btn.classList.add('attivo');
    caricaPresenze();
}

// ── Carica presenze via AJAX ───────────────────────────────────────────────
async function caricaPresenze() {
    const tbody = document.getElementById('tabellaBody');
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>`;

    const [y, m] = meseCorrente.split('-').map(Number);
    const nomeMese = new Date(y, m - 1, 1).toLocaleDateString('it-IT', { month: 'long', year: 'numeric' });
    document.getElementById('labelMese').textContent = nomeMese.charAt(0).toUpperCase() + nomeMese.slice(1);

    try {
        const res  = await fetch(`${AJAX_URL}?action=lista&mese=${meseCorrente}&utente=${filtroUtente}`);
        const data = await res.json();

        if (!data.ok) {
            tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><p>${data.msg}</p></div></td></tr>`;
            return;
        }

        // ── Statistiche ──
        const utenteStats = filtroUtente === 'tutti' ? UTENTE : filtroUtente;
        const ore    = data.totali[utenteStats] ?? 0;
        const giorni = data.giorni[utenteStats] ?? 0;
        const media  = giorni > 0 ? (ore / giorni) : 0;

        document.getElementById('statGiorni').textContent = giorni;
        document.getElementById('statOre').textContent    = ore.toFixed(1) + 'h';
        document.getElementById('statMedia').textContent  = media.toFixed(1) + 'h';

        // ── Tabella ──
        if (!data.righe || data.righe.length === 0) {
            tbody.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>Nessuna presenza registrata in questo periodo.</p>
                    </div>
                </td></tr>`;
            return;
        }

        // Ordina dalla più recente
        const righe = [...data.righe].reverse();
        tbody.innerHTML = righe.map(r => {
            const dt     = new Date(r.timestamp);
            const data_s = dt.toLocaleDateString('it-IT', { weekday: 'short', day: '2-digit', month: '2-digit' });
            const ora_s  = dt.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            const u      = r.utente.toLowerCase();
            const nome   = r.utente.charAt(0).toUpperCase() + r.utente.slice(1);
            const isUscita = r.tipo === 'uscita';

            const oreLav = r.ore_lavorate
                ? `<span class="ore-lavorate">${r.ore_lavorate}</span>`
                : `<span style="color:#9499b7">–</span>`;

            const nota = r.nota
                ? `<span class="nota-cell" title="${r.nota}">${r.nota}</span>`
                : `<span style="color:#9499b7">–</span>`;

            const geo = (r.lat && r.lng && r.lat != 0)
                ? `<a href="https://www.google.com/maps?q=${r.lat},${r.lng}" target="_blank" rel="noopener" style="color:var(--secondary-color);font-size:12px;">📍 Mappa</a>`
                : `<span style="color:#9499b7">–</span>`;

            return `
            <tr>
                <td data-label="Data">${data_s}</td>
                <td data-label="Ora" style="font-weight:700;color:#3d4468;">${ora_s}</td>
                <td data-label="Utente"><span class="badge-utente ${u}">${nome}</span></td>
                <td data-label="Tipo">
                    <span class="badge-tipo ${r.tipo}">
                        ${isUscita ? '🔴' : '🟢'} ${r.tipo}
                    </span>
                </td>
                <td data-label="Ore lavorate">${oreLav}</td>
                <td data-label="Nota">${nota}</td>
                <td data-label="Posizione">${geo}</td>
            </tr>`;
        }).join('');

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><p>Errore di caricamento.</p></div></td></tr>`;
        console.error(e);
    }
}

// ── Export CSV ─────────────────────────────────────────────────────────────
function esportaCSV() {
    const url = `${AJAX_URL}?action=export&mese=${meseCorrente}&utente=${filtroUtente}`;
    window.location.href = url;
}

// ── Toast ──────────────────────────────────────────────────────────────────
function mostraToast(msg, tipo = 'info') {
    const container = document.getElementById('toastContainer');
    const toast     = document.createElement('div');
    const ico = tipo === 'success' ? '✅' : tipo === 'error' ? '❌' : 'ℹ️';
    toast.className = `toast ${tipo}`;
    toast.innerHTML = `<span>${ico}</span> ${msg}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
}

// ── Init ───────────────────────────────────────────────────────────────────
caricaStato();
caricaPresenze();
</script>
