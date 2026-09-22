<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

// ── Utenti autorizzati ────────────────────────────────────────────────────────
$utenti_autorizzati = ['thomas', 'riccardo', 'carlo', 'admin'];
$utente_corrente    = strtolower(trim($_SESSION['admin'] ?? ''));

if (!in_array($utente_corrente, $utenti_autorizzati, true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Accesso non autorizzato.']);
    exit();
}

// ── Posizione sede di lavoro ──────────────────────────────────────────────────
// Moki Club Numana – Piazzale di Porto Molo Nord, 60026 Numana AN
define('LAVORO_LAT',    43.511467);
define('LAVORO_LNG',    13.625128);
define('LAVORO_RAGGIO', 300);       // metri di tolleranza

// ── Path CSV ─────────────────────────────────────────────────────────────────
$csv_dir  = dirname(__DIR__) . '/data/presenze/';
$csv_file = $csv_dir . 'presenze.csv';

if (!is_dir($csv_dir)) {
    mkdir($csv_dir, 0755, true);
}

// ── Intestazione CSV se non esiste ───────────────────────────────────────────
if (!file_exists($csv_file)) {
    $fp = fopen($csv_file, 'w');
    fputcsv($fp, ['id', 'utente', 'tipo', 'timestamp', 'lat', 'lng', 'nota']);
    fclose($fp);
}

// ── Helper: leggi tutte le righe ─────────────────────────────────────────────
function leggi_presenze(string $file): array {
    $righe = [];
    if (!file_exists($file)) return $righe;
    $fp = fopen($file, 'r');
    $header = fgetcsv($fp); // salta intestazione
    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) >= 7) {
            $righe[] = [
                'id'        => $row[0],
                'utente'    => $row[1],
                'tipo'      => $row[2],
                'timestamp' => $row[3],
                'lat'       => $row[4],
                'lng'       => $row[5],
                'nota'      => $row[6],
            ];
        }
    }
    fclose($fp);
    return $righe;
}

// ── Helper: scrivi una riga ───────────────────────────────────────────────────
function scrivi_riga(string $file, array $dati): void {
    $fp = fopen($file, 'a');
    fputcsv($fp, $dati);
    fclose($fp);
}

// ── Helper: distanza Haversine in metri ──────────────────────────────────────
function distanza_metri(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R   = 6371000; // raggio Terra in metri
    $ph1 = deg2rad($lat1);
    $ph2 = deg2rad($lat2);
    $Df  = deg2rad($lat2 - $lat1);
    $Dl  = deg2rad($lng2 - $lng1);
    $a   = sin($Df/2)**2 + cos($ph1)*cos($ph2)*sin($Dl/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}

// ── Routing ──────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── 1. Timbra (entrata o uscita) ─────────────────────────────────────────
    case 'timbra':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'Metodo non consentito.']);
            exit();
        }

        if (!app_validate_csrf($_POST['csrf_token'] ?? null)) {
            echo json_encode(['ok' => false, 'msg' => 'Token CSRF non valido.']);
            exit();
        }

        $tipo = $_POST['tipo'] ?? '';
        if (!in_array($tipo, ['entrata', 'uscita'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Tipo non valido.']);
            exit();
        }

        $lat  = floatval($_POST['lat'] ?? 0);
        $lng  = floatval($_POST['lng'] ?? 0);
        $nota = htmlspecialchars(trim($_POST['nota'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ts   = date('Y-m-d H:i:s');
        $id   = uniqid('p', true);

        // Controllo posizione: se le coordinate sono disponibili, verifica il raggio
        if ($lat !== 0.0 && $lng !== 0.0) {
            $distanza = distanza_metri($lat, $lng, LAVORO_LAT, LAVORO_LNG);
            if ($distanza > LAVORO_RAGGIO) {
                $dist_fmt = round($distanza);
                echo json_encode([
                    'ok'  => false,
                    'msg' => "Sei troppo lontano dalla sede ({$dist_fmt}m). Devi essere entro " . LAVORO_RAGGIO . "m dal Moki Club per timbrare.",
                ]);
                exit();
            }
        }

        // Controllo turno aperto: non si può timbrare entrata due volte di fila
        $righe = leggi_presenze($csv_file);
        $ultime = array_filter($righe, fn($r) => $r['utente'] === $utente_corrente);
        $ultima = !empty($ultime) ? end($ultime) : null;

        if ($tipo === 'entrata' && $ultima && $ultima['tipo'] === 'entrata') {
            echo json_encode(['ok' => false, 'msg' => 'Hai già timbrato l\'entrata. Timbra prima l\'uscita.']);
            exit();
        }
        if ($tipo === 'uscita' && (!$ultima || $ultima['tipo'] === 'uscita')) {
            echo json_encode(['ok' => false, 'msg' => 'Non hai un turno aperto. Timbra prima l\'entrata.']);
            exit();
        }

        // Calcola ore lavorate se è uscita
        $ore_lavorate = null;
        if ($tipo === 'uscita' && $ultima) {
            $inizio = new DateTime($ultima['timestamp']);
            $fine   = new DateTime($ts);
            $diff   = $inizio->diff($fine);
            $ore_lavorate = $diff->h + round($diff->i / 60, 2);
        }

        scrivi_riga($csv_file, [$id, $utente_corrente, $tipo, $ts, $lat, $lng, $nota]);

        echo json_encode([
            'ok'           => true,
            'msg'          => $tipo === 'entrata'
                ? 'Entrata registrata alle ' . date('H:i', strtotime($ts)) . '!'
                : 'Uscita registrata alle ' . date('H:i', strtotime($ts)) . '!',
            'tipo'         => $tipo,
            'timestamp'    => $ts,
            'ore_lavorate' => $ore_lavorate,
        ]);
        break;

    // ── 2. Stato turno corrente ───────────────────────────────────────────────
    case 'stato':
        $righe = leggi_presenze($csv_file);
        $ultime = array_filter($righe, fn($r) => $r['utente'] === $utente_corrente);
        $ultima = !empty($ultime) ? end($ultime) : null;

        $turno_aperto = ($ultima && $ultima['tipo'] === 'entrata');

        echo json_encode([
            'ok'           => true,
            'turno_aperto' => $turno_aperto,
            'ultima'       => $ultima,
        ]);
        break;

    // ── 3. Lista presenze per mese ───────────────────────────────────────────
    case 'lista':
        $mese  = $_GET['mese']   ?? date('Y-m');   // es. "2025-06"
        $filtro = $_GET['utente'] ?? 'tutti';

        // Valida formato mese
        if (!preg_match('/^\d{4}-\d{2}$/', $mese)) {
            echo json_encode(['ok' => false, 'msg' => 'Formato mese non valido.']);
            exit();
        }

        $righe = leggi_presenze($csv_file);

        // Filtra per mese
        $righe = array_filter($righe, fn($r) => str_starts_with($r['timestamp'], $mese));

        // Filtra per utente
        if ($filtro !== 'tutti') {
            $filtro = strtolower(trim($filtro));
            $righe  = array_filter($righe, fn($r) => $r['utente'] === $filtro);
        }

        // Costruisci righe con ore calcolate
        $righe_out = [];
        $entrate   = []; // utente => riga entrata pendente

        foreach ($righe as $r) {
            if ($r['tipo'] === 'entrata') {
                $entrate[$r['utente']] = $r;
            } elseif ($r['tipo'] === 'uscita' && isset($entrate[$r['utente']])) {
                $e    = $entrate[$r['utente']];
                $diff = (new DateTime($r['timestamp']))->diff(new DateTime($e['timestamp']));
                $h    = $diff->h + $diff->days * 24;
                $m    = $diff->i;
                $r['ore_lavorate']   = sprintf('%d:%02d', $h, $m);
                $r['ore_float']      = round($h + $m / 60, 2);
                $r['ts_entrata']     = $e['timestamp'];
                unset($entrate[$r['utente']]);
            }
            $righe_out[] = $r;
        }

        // Totale ore per utente (solo uscite complete)
        $totali = [];
        foreach ($righe_out as $r) {
            if (isset($r['ore_float'])) {
                $u = $r['utente'];
                $totali[$u] = ($totali[$u] ?? 0) + $r['ore_float'];
            }
        }

        // Conta giorni lavorati per utente
        $giorni = [];
        foreach ($righe_out as $r) {
            if ($r['tipo'] === 'uscita' && isset($r['ts_entrata'])) {
                $giorno = substr($r['timestamp'], 0, 10);
                $u      = $r['utente'];
                $giorni[$u][$giorno] = true;
            }
        }
        $giorni_count = [];
        foreach ($giorni as $u => $g) $giorni_count[$u] = count($g);

        echo json_encode([
            'ok'     => true,
            'righe'  => array_values($righe_out),
            'totali' => $totali,
            'giorni' => $giorni_count,
        ]);
        break;

    // ── 4. Export CSV del mese ───────────────────────────────────────────────
    case 'export':
        $mese   = $_GET['mese']   ?? date('Y-m');
        $filtro = $_GET['utente'] ?? 'tutti';

        if (!preg_match('/^\d{4}-\d{2}$/', $mese)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Formato mese non valido.']);
            exit();
        }

        $righe = leggi_presenze($csv_file);
        $righe = array_filter($righe, fn($r) => str_starts_with($r['timestamp'], $mese));
        if ($filtro !== 'tutti') {
            $filtro = strtolower(trim($filtro));
            $righe  = array_filter($righe, fn($r) => $r['utente'] === $filtro);
        }

        $filename = 'presenze_' . $mese . ($filtro !== 'tutti' ? '_' . $filtro : '') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $fp = fopen('php://output', 'w');
        // BOM per Excel italiano
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['Utente', 'Tipo', 'Data e Ora', 'Latitudine', 'Longitudine', 'Nota'], ';');
        foreach ($righe as $r) {
            fputcsv($fp, [
                ucfirst($r['utente']),
                ucfirst($r['tipo']),
                $r['timestamp'],
                $r['lat'],
                $r['lng'],
                $r['nota'],
            ], ';');
        }
        fclose($fp);
        exit();

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Azione non riconosciuta.']);
}
