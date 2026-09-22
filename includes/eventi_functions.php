<?php
require_once dirname(__DIR__) . '/security_headers.php';

/**
 * Genera uno slug URL-friendly a partire da un titolo, garantendone
 * l'unicità nella tabella eventi (escludendo eventualmente un id).
 */
function eventoGeneraSlug(mysqli $conn, string $titolo, ?int $escludiId = null): string
{
    $base = strtolower(trim($titolo));
    $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'evento';
    }

    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM eventi WHERE slug = ? AND id != ? LIMIT 1");
        $escludi = $escludiId ?? 0;
        $stmt->bind_param("si", $slug, $escludi);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            return $slug;
        }
        $i++;
        $slug = $base . '-' . $i;
    }
}

/**
 * Genera un token sicuro e univoco per la gestione autonoma della prenotazione.
 */
function prenotazioneGeneraToken(mysqli $conn): string
{
    do {
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("SELECT id FROM prenotazioni WHERE token = ? LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
    } while ($res->num_rows > 0);

    return $token;
}

/**
 * Calcola i posti occupati per ciascuna evento_tavola di un evento.
 * Considera solo le prenotazioni in_attesa o confermata (non rifiutate/annullate).
 * Ritorna un array [evento_tavola_id => posti_occupati]
 */
function eventoPostiOccupatiPerTavola(mysqli $conn, int $eventoId): array
{
    $occupati = [];
    $stmt = $conn->prepare(
        "SELECT pt.evento_tavola_id, SUM(pt.posti_occupati) AS occupati
         FROM prenotazione_tavole pt
         INNER JOIN prenotazioni p ON p.id = pt.prenotazione_id
         INNER JOIN evento_tavole et ON et.id = pt.evento_tavola_id
         WHERE et.evento_id = ? AND p.stato IN ('in_attesa','confermata')
         GROUP BY pt.evento_tavola_id"
    );
    $stmt->bind_param("i", $eventoId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $occupati[(int) $row['evento_tavola_id']] = (int) $row['occupati'];
    }
    return $occupati;
}

/**
 * Ritorna l'elenco delle tavole attive di un evento con i posti disponibili calcolati.
 */
function eventoElencoTavoleConDisponibilita(mysqli $conn, int $eventoId): array
{
    $tavole = [];
    $stmt = $conn->prepare(
        "SELECT * FROM evento_tavole WHERE evento_id = ? AND attiva = 1 ORDER BY ordine ASC, id ASC"
    );
    $stmt->bind_param("i", $eventoId);
    $stmt->execute();
    $res = $stmt->get_result();

    $occupati = eventoPostiOccupatiPerTavola($conn, $eventoId);

    while ($row = $res->fetch_assoc()) {
        $id = (int) $row['id'];
        $occ = $occupati[$id] ?? 0;
        $row['posti_occupati'] = $occ;
        $row['posti_disponibili'] = max(0, (int) $row['capacita'] - $occ);
        $tavole[] = $row;
    }

    return $tavole;
}

/**
 * Verifica se una prenotazione può ancora essere modificata/cancellata dal cliente
 * in base ai giorni_limite_modifica dell'evento.
 */
function prenotazioneModificabileDalCliente(array $prenotazione, array $evento): bool
{
    if (in_array($prenotazione['stato'], ['annullata'], true)) {
        return false;
    }
    if (empty($evento['data_evento']) || $evento['data_evento'] === '0000-00-00') {
        return true;
    }

    $limiteGiorni = (int) ($evento['giorni_limite_modifica'] ?? 2);
    $dataLimite = new DateTime($evento['data_evento']);
    $dataLimite->modify("-{$limiteGiorni} days");
    $oggi = new DateTime('today');

    return $oggi <= $dataLimite;
}

/**
 * Conta le prenotazioni non ancora lette dall'admin (badge "nuove").
 */
function contaPrenotazioniNonLette(mysqli $conn): int
{
    $res = $conn->query("SELECT COUNT(*) AS totale FROM prenotazioni WHERE letta_admin = 0 AND stato != 'annullata'");
    if (!$res) {
        return 0;
    }
    return (int) ($res->fetch_assoc()['totale'] ?? 0);
}

/**
 * Salva fino a 3 foto caricate per una tavola (evento_tavole o tavole_modello)
 * nella cartella /assets/img/eventi/tavole/ e ritorna i percorsi relativi.
 * $existing è l'array delle foto già presenti (per non perderle se non sostituite).
 */
function tavolaGestisciUploadFoto(array $files, array $existing = []): array
{
    $risultato = [
        'foto1' => $existing['foto1'] ?? null,
        'foto2' => $existing['foto2'] ?? null,
        'foto3' => $existing['foto3'] ?? null,
    ];

    $baseDir = dirname(__DIR__) . '/assets/img/eventi/tavole/';
    if (!file_exists($baseDir)) {
        mkdir($baseDir, 0755, true);
    }

    $consentiti = ['jpg', 'jpeg', 'png', 'webp'];

    foreach (['foto1', 'foto2', 'foto3'] as $campo) {
        if (empty($files[$campo]['name'])) {
            continue;
        }
        if ((int) $files[$campo]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }

        $ext = strtolower(pathinfo($files[$campo]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $consentiti, true)) {
            continue;
        }

        $nomeFile = 'tavola_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target = $baseDir . $nomeFile;

        if (move_uploaded_file($files[$campo]['tmp_name'], $target)) {
            chmod($target, 0644);
            $risultato[$campo] = 'assets/img/eventi/tavole/' . $nomeFile;
        }
    }

    return $risultato;
}

/**
 * Salva l'immagine di copertina di un evento.
 */
function eventoGestisciUploadCopertina(array $file, ?string $existing = null): ?string
{
    if (empty($file['name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $existing;
    }

    $consentiti = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $consentiti, true)) {
        return $existing;
    }

    $baseDir = dirname(__DIR__) . '/assets/img/eventi/';
    if (!file_exists($baseDir)) {
        mkdir($baseDir, 0755, true);
    }

    $nomeFile = 'evento_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = $baseDir . $nomeFile;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        chmod($target, 0644);
        return 'assets/img/eventi/' . $nomeFile;
    }

    return $existing;
}

/**
 * Formatta una data in stile italiano (gg/mm/aaaa), gestendo NULL / date vuote.
 */
function formattaDataIt(?string $data): string
{
    if (empty($data) || $data === '0000-00-00') {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : '';
}

/**
 * Verifica se una persona è iscritta nel DB tramite email o telefono.
 * Basta che UNA delle due corrispondenze (email OPPURE telefono) sia già
 * presente in un iscritto per considerare la persona tesserata: nome e
 * cognome non vengono più usati come filtro di ricerca.
 * Ritorna array con (id, nome, cognome, email, telefono) oppure null se non trovato.
 */
function verificaIscritto(mysqli $conn, string $nome, string $cognome, ?string $email = null, ?string $telefono = null): ?array
{
    $email = $email !== null ? strtolower(trim($email)) : '';
    $telefono = $telefono !== null ? trim($telefono) : '';

    // Senza almeno un dato di contatto non c'è nulla da cercare.
    if ($email === '' && $telefono === '') {
        return null;
    }

    $condizioni = [];
    $params = [];
    $types = "";

    if ($email !== '') {
        $condizioni[] = "LOWER(email) = ?";
        $params[] = $email;
        $types .= "s";
    }
    if ($telefono !== '') {
        $condizioni[] = "telefono = ?";
        $params[] = $telefono;
        $types .= "s";
    }

    $sql = "SELECT id_modulo, nome, cognome, email, telefono FROM iscritti WHERE (" . implode(" OR ", $condizioni) . ") LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Salva (crea o aggiorna) le tariffe di un evento in un'unica chiamata.
 * $datiTariffe = ['tesserato' => ['importo' => 10.0, 'descrizione' => '...'], ...]
 */
/**
 * Salva le tariffe di un evento.
 * Se modalita è 'semplice', salva solo il tipo 'quota_unica'.
 * Se modalita è 'dettagliata', salva tesserato, non_tesserato, tavola_propria.
 */
function eventoSalvaTariffe(mysqli $conn, int $eventoId, array $datiTariffe, string $modalita = 'dettagliata'): bool
{
    $ok = true;

    // Determina quali tipi di tariffa salvare in base alla modalità
    if ($modalita === 'semplice') {
        $tipiDaSalvare = ['quota_unica'];
        $tipiDaEliminare = ['tesserato', 'non_tesserato', 'tavola_propria'];
    } else {
        $tipiDaSalvare = ['tesserato', 'non_tesserato', 'tavola_propria'];
        $tipiDaEliminare = ['quota_unica'];
    }

    if (!empty($tipiDaEliminare)) {
        $placeholders = implode(',', array_fill(0, count($tipiDaEliminare), '?'));
        $types = str_repeat('s', count($tipiDaEliminare));
        $params = [$eventoId];
        foreach ($tipiDaEliminare as $tipo) {
            $params[] = $tipo;
        }

        $stmtDelete = $conn->prepare(
            "DELETE FROM evento_tariffe WHERE evento_id = ? AND tipo_tariffa IN ($placeholders)"
        );
        if ($stmtDelete === false) {
            return false;
        }

        $stmtDelete->bind_param('i' . $types, ...$params);
        if (!$stmtDelete->execute()) {
            $ok = false;
        }
        $stmtDelete->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO evento_tariffe (evento_id, tipo_tariffa, importo_euro, descrizione) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE importo_euro = VALUES(importo_euro), descrizione = VALUES(descrizione)"
    );
    if ($stmt === false) {
        return false;
    }

    foreach ($tipiDaSalvare as $tipo) {
        if (!isset($datiTariffe[$tipo])) {
            continue;
        }
        $importo = (float) $datiTariffe[$tipo]['importo'];
        $descrizione = trim((string) $datiTariffe[$tipo]['descrizione']);
        $stmt->bind_param("isds", $eventoId, $tipo, $importo, $descrizione);
        if (!$stmt->execute()) {
            $ok = false;
        }
    }

    $stmt->close();
    return $ok;
}

/**
 * Ricava le tariffe di un evento, con fallback a tariffe di default.
 * Ritorna array associativo per tipo_tariffa => ['importo' => float, 'descrizione' => string]
 * La struttura dipende dalla modalità_tariffe dell'evento.
 */
function eventoOttieniTariffeComplete(mysqli $conn, int $eventoId, ?string $modalita = null): array
{
    // Se non viene passata la modalità, recuperala dal DB
    if ($modalita === null && $eventoId > 0) {
        $stmt = $conn->prepare("SELECT modalita_tariffe FROM eventi WHERE id = ?");
        $stmt->bind_param("i", $eventoId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $modalita = $res['modalita_tariffe'] ?? 'dettagliata';
    }
    $modalita = $modalita ?? 'dettagliata';
    
    // Tariffe di default in base alla modalità
    if ($modalita === 'semplice') {
        $tariffe = [
            'quota_unica' => ['importo' => 25.00, 'descrizione' => 'Quota di partecipazione all\'evento'],
        ];
    } else {
        $tariffe = [
            'tesserato' => ['importo' => 20.00, 'descrizione' => 'Quota partecipazione evento (tesserati)'],
            'non_tesserato' => ['importo' => 25.00, 'descrizione' => 'Tesseramento + quota partecipazione evento e noleggio tavola'],
            'tavola_propria' => ['importo' => 15.00, 'descrizione' => 'Tesseramento + quota partecipazione con tavola propria'],
        ];
    }

    if ($eventoId > 0) {
        $stmt = $conn->prepare("SELECT tipo_tariffa, importo_euro, descrizione FROM evento_tariffe WHERE evento_id = ? ORDER BY id DESC");
        $stmt->bind_param("i", $eventoId);
        $stmt->execute();
        $res = $stmt->get_result();

        $tariffeSalvate = [];
        while ($row = $res->fetch_assoc()) {
            $tipo = $row['tipo_tariffa'] ?? null;
            if ($tipo === null || !isset($tariffe[$tipo])) {
                continue;
            }

            if (!isset($tariffeSalvate[$tipo])) {
                $tariffeSalvate[$tipo] = true;
                $tariffe[$tipo]['importo'] = (float) $row['importo_euro'];
                if (!empty($row['descrizione'])) {
                    $tariffe[$tipo]['descrizione'] = $row['descrizione'];
                }
            }
        }
    }

    return $tariffe;
}

/**
 * Ricava le tariffe di un evento, con fallback a tariffe di default.
 * Ritorna array: ['tesserato' => 20.00, 'non_tesserato' => 25.00, 'tavola_propria' => 15.00]
 * (usato dove serve solo l'importo, es. calcolo prezzi prenotazione)
 */
function eventoOttieniTariffe(mysqli $conn, int $eventoId): array
{
    $complete = eventoOttieniTariffeComplete($conn, $eventoId);
    $tariffe = [];
    foreach ($complete as $tipo => $dati) {
        $tariffe[$tipo] = $dati['importo'];
    }
    return $tariffe;
}

/**
 * Determina il tipo di tariffa per una persona (verifica DB, fallback a non_tesserato).
 * Se l'evento ha modalita 'semplice', ritorna sempre 'quota_unica'.
 */
function determinaTipoTariffa(mysqli $conn, int $eventoId, string $nome, string $cognome, ?string $email = null, ?string $telefono = null, bool $tavolaPropria = false): array
{
    // Se l'evento ha modalità semplice, niente tavola propria
    $stmt = $conn->prepare("SELECT modalita_tariffe FROM eventi WHERE id = ?");
    $stmt->bind_param("i", $eventoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $modalita = $res['modalita_tariffe'] ?? 'dettagliata';

    if ($modalita === 'semplice') {
        if ($tavolaPropria) {
            return ['tipo' => 'quota_unica', 'iscritto_id' => null, 'iscritto' => null];
        }
        return ['tipo' => 'quota_unica', 'iscritto_id' => null, 'iscritto' => null];
    }

    // Modalità dettagliata: logica originale
    // Controlla SEMPRE prima l'iscrizione nel DB, a prescindere dalla tavola propria:
    // un tesserato con tavola propria deve comunque pagare la tariffa "tesserato".
    $iscritto = verificaIscritto($conn, $nome, $cognome, $email, $telefono);
    if ($iscritto) {
        return ['tipo' => 'tesserato', 'iscritto_id' => (int) $iscritto['id_modulo'], 'iscritto' => $iscritto];
    }

    // Non tesserato: se ha portato la tavola propria, si applica la tariffa dedicata
    // "tavola_propria" (es. 20€), altrimenti la tariffa "non_tesserato" piena (es. 25€).
    if ($tavolaPropria) {
        return ['tipo' => 'tavola_propria', 'iscritto_id' => null, 'iscritto' => null];
    }

    return ['tipo' => 'non_tesserato', 'iscritto_id' => null, 'iscritto' => null];
}

/**
 * Calcola il totale da pagare per una prenotazione dato un array di partecipanti
 * con tipo_tariffa già determinato.
 */
function calcolaTotaleTariffe(array $partecipanti): float
{
    $totale = 0.0;
    foreach ($partecipanti as $p) {
        $totale += (float) ($p['importo_tariffa'] ?? 0.0);
    }
    return $totale;
}