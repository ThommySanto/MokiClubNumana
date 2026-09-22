<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . '/../includes/eventi_functions.php';
require_once __DIR__ . '/../includes/spreadsheet_xml.php';

$eventoId = intval($_GET['evento_id'] ?? 0);
if ($eventoId <= 0) {
    http_response_code(400);
    die('Evento non specificato. Seleziona un evento dal filtro prima di esportare.');
}

$stmtE = $conn->prepare("SELECT * FROM eventi WHERE id = ?");
$stmtE->bind_param("i", $eventoId);
$stmtE->execute();
$evento = $stmtE->get_result()->fetch_assoc();
if (!$evento) {
    http_response_code(404);
    die('Evento non trovato.');
}

// ─── Prenotazioni dell'evento (tutte tranne quelle annullate) ───
$stmtP = $conn->prepare("SELECT * FROM prenotazioni WHERE evento_id = ? AND stato != 'annullata' ORDER BY created_at ASC");
$stmtP->bind_param("i", $eventoId);
$stmtP->execute();
$prenotazioni = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);

$prenById = [];
foreach ($prenotazioni as $p) {
    $prenById[$p['id']] = $p;
}

// ─── Partecipanti + tavola assegnata (stessa logica di admin/prenotazioni.php) ───
$partecipanti = [];
if (!empty($prenotazioni)) {
    $ids = array_map(fn($p) => (int) $p['id'], $prenotazioni);
    $idsStr = implode(',', $ids);
    $res = $conn->query(
        "SELECT pp.prenotazione_id, pp.nome, pp.cognome, pp.tipo_tariffa, pp.importo_tariffa,
                COALESCE(et.nome, 'Tavola propria') AS nome_tavola
         FROM prenotazione_partecipanti pp
         LEFT JOIN prenotazione_tavole pt ON pt.id = pp.prenotazione_tavola_id
         LEFT JOIN evento_tavole et ON et.id = pt.evento_tavola_id
         WHERE pp.prenotazione_id IN ($idsStr)
         ORDER BY nome_tavola ASC, pp.cognome ASC"
    );
    while ($row = $res->fetch_assoc()) {
        $partecipanti[] = $row;
    }
}

$gruppi = [];
foreach ($partecipanti as $pt) {
    $gruppi[$pt['nome_tavola']][$pt['prenotazione_id']][] = $pt;
}

$labelsTariffa = ['tesserato' => 'Tesserato', 'non_tesserato' => 'Non tesserato', 'tavola_propria' => 'Tavola propria'];

// ─── Palette ───
$BLU = '#1F4E78';
$AZZURRO = '#DCE6F1';
$GRIGIO = '#F2F2F2';
$BIANCO = '#FFFFFF';
$N_COLS = 10;
$MERGE_FULL = $N_COLS - 1;

$sheet = new SpreadsheetXmlBuilder();

$sheet->addStyle('title', ['bold' => true, 'size' => 15, 'color' => $BIANCO, 'fill' => $BLU, 'align' => 'Center']);
$sheet->addStyle('subtitle', ['bold' => true, 'size' => 12, 'color' => $BLU, 'align' => 'Center']);
$sheet->addStyle('info', ['italic' => true, 'size' => 10, 'color' => '#595959', 'align' => 'Center']);
$sheet->addStyle('legend', ['italic' => true, 'size' => 9, 'color' => '#808080', 'align' => 'Left']);
$sheet->addStyle('header', ['bold' => true, 'size' => 10, 'color' => $BIANCO, 'fill' => $BLU, 'align' => 'Center', 'wrap' => true, 'border' => true]);
$sheet->addStyle('group', ['bold' => true, 'size' => 10.5, 'color' => $BLU, 'fill' => $AZZURRO, 'align' => 'Left', 'border' => true, 'indent' => 1]);
$sheet->addStyle('cell', ['size' => 10, 'align' => 'Left', 'border' => true]);
$sheet->addStyle('cellAlt', ['size' => 10, 'align' => 'Left', 'border' => true, 'fill' => $GRIGIO]);
$sheet->addStyle('cellCenter', ['size' => 10, 'align' => 'Center', 'border' => true]);
$sheet->addStyle('cellCenterAlt', ['size' => 10, 'align' => 'Center', 'border' => true, 'fill' => $GRIGIO]);
$sheet->addStyle('cellCheck', ['size' => 13, 'align' => 'Center', 'border' => true]);
$sheet->addStyle('cellCheckAlt', ['size' => 13, 'align' => 'Center', 'border' => true, 'fill' => $GRIGIO]);
$sheet->addStyle('cellCurrency', ['size' => 10, 'align' => 'Right', 'border' => true, 'format' => '"€" #,##0.00']);
$sheet->addStyle('cellCurrencyAlt', ['size' => 10, 'align' => 'Right', 'border' => true, 'fill' => $GRIGIO, 'format' => '"€" #,##0.00']);
$sheet->addStyle('totalLabel', ['bold' => true, 'size' => 11, 'color' => $BIANCO, 'fill' => $BLU, 'align' => 'Right', 'border' => true, 'indent' => 1]);
$sheet->addStyle('totalValue', ['bold' => true, 'size' => 11, 'color' => $BIANCO, 'fill' => $BLU, 'align' => 'Right', 'border' => true, 'format' => '"€" #,##0.00']);
$sheet->addStyle('totalBlank', ['fill' => $BLU, 'border' => true]);

// ─── Intestazione ───
$sheet->startRow(26);
$sheet->addCell('MOKI CLUB NUMANA — Lista Prenotazioni Evento', 'title', 'String', $MERGE_FULL);
$sheet->endRow();

$sheet->startRow();
$sheet->addCell($evento['titolo'], 'subtitle', 'String', $MERGE_FULL);
$sheet->endRow();

$dataFmt = formattaDataIt($evento['data_evento']);
$oraFmt = $evento['ora_evento'] ? substr($evento['ora_evento'], 0, 5) : '';
$infoParts = [];
if ($dataFmt) $infoParts[] = 'Data: ' . $dataFmt;
if ($evento['luogo']) $infoParts[] = 'Luogo: ' . $evento['luogo'];
if ($oraFmt) $infoParts[] = 'Orario ritrovo: ' . $oraFmt;
$infoParts[] = 'Generato il: ' . date('d/m/Y H:i');
$sheet->startRow();
$sheet->addCell(implode('   |   ', $infoParts), 'info', 'String', $MERGE_FULL);
$sheet->endRow();

$sheet->startRow();
$sheet->addCell('', null, 'String', $MERGE_FULL);
$sheet->endRow();

$sheet->startRow();
$sheet->addCell(
    'Legenda:  "Pagato" = Sì/No confermato dall\'admin sul sito  •  "N° Pagaia" e "Note" sono da compilare a mano il giorno dell\'evento',
    'legend', 'String', $MERGE_FULL
);
$sheet->endRow();

$sheet->startRow();
$sheet->addCell('', null, 'String', $MERGE_FULL);
$sheet->endRow();

// ─── Intestazione colonne ───
$headerLabels = ['#', 'Referente prenotazione', 'Modalità', 'Telefono', 'Email', 'Partecipanti (nome e tariffa)', 'Importo €', 'Pagato', 'N° Pagaia', 'Note (check-in)'];
$sheet->startRow(30);
foreach ($headerLabels as $lbl) {
    $sheet->addCell($lbl, 'header');
}
$sheet->endRow();
$freezeAfterRow = 7;

// ─── Righe dati, raggruppate per tavola assegnata ───
$n = 1;
$importoTotale = 0.0;
$totalePartecipanti = 0;

foreach ($gruppi as $nomeTavola => $prenotazioniTavola) {
    // Ordina le prenotazioni di questa tavola per data di creazione
    uksort($prenotazioniTavola, function ($a, $b) use ($prenById) {
        return strcmp($prenById[$a]['created_at'] ?? '', $prenById[$b]['created_at'] ?? '');
    });

    $countPersone = 0;
    foreach ($prenotazioniTavola as $persone) {
        $countPersone += count($persone);
    }

    $sheet->startRow(20);
    $label = '🏄  ' . $nomeTavola . '   —   ' . $countPersone . ' partecipante' . ($countPersone !== 1 ? 'i' : '');
    $sheet->addCell($label, 'group', 'String', $MERGE_FULL);
    $sheet->endRow();

    foreach ($prenotazioniTavola as $prenotazioneId => $persone) {
        $pren = $prenById[$prenotazioneId] ?? null;
        if (!$pren) {
            continue;
        }

        $alt = ($n % 2 === 0);
        $sCell = $alt ? 'cellAlt' : 'cell';
        $sCenter = $alt ? 'cellCenterAlt' : 'cellCenter';
        $sCheck = $alt ? 'cellCheckAlt' : 'cellCheck';
        $sCurrency = $alt ? 'cellCurrencyAlt' : 'cellCurrency';

        $partecipantiStr = implode('; ', array_map(function ($pt) use ($labelsTariffa) {
            $tariffaLbl = $labelsTariffa[$pt['tipo_tariffa']] ?? $pt['tipo_tariffa'];
            return trim($pt['nome'] . ' ' . $pt['cognome']) . ' (' . $tariffaLbl . ')';
        }, $persone));

        $importoRiga = 0.0;
        foreach ($persone as $pt) {
            $importoRiga += (float) $pt['importo_tariffa'];
        }

        $sheet->startRow();
        $sheet->addCell($n, $sCenter, 'Number');
        $sheet->addCell(trim($pren['referente_nome'] . ' ' . $pren['referente_cognome']), $sCell);
        $sheet->addCell($pren['modalita'] === 'gruppo' ? 'Gruppo' : 'Singola', $sCenter);
        $sheet->addCell($pren['referente_telefono'], $sCell);
        $sheet->addCell($pren['referente_email'], $sCell);
        $sheet->addCell($partecipantiStr, $sCell);
        $sheet->addCell(round($importoRiga, 2), $sCurrency, 'Number');
        $sheet->addCell(!empty($pren['pagato']) ? '☑ Sì' : '☐ No', $sCheck);
        $sheet->addCell('', $sCenter);
        $sheet->addCell('', $sCell);
        $sheet->endRow();

        $importoTotale += $importoRiga;
        $totalePartecipanti += count($persone);
        $n++;
    }
}

if ($totalePartecipanti === 0) {
    $sheet->startRow();
    $sheet->addCell('Nessuna prenotazione per questo evento.', 'cell', 'String', $MERGE_FULL);
    $sheet->endRow();
}

// ─── Riga totale ───
$sheet->startRow(22);
$sheet->addCell('TOTALE — ' . $totalePartecipanti . ' partecipanti', 'totalLabel', 'String', 5);
$sheet->addCell(round($importoTotale, 2), 'totalValue', 'Number');
$sheet->addCell('', 'totalBlank');
$sheet->addCell('', 'totalBlank');
$sheet->addCell('', 'totalBlank');
$sheet->endRow();

// ─── Larghezza colonne (punti) — l'adattamento a 1 pagina scala comunque tutto in stampa ───
$colWidths = [30, 140, 70, 90, 160, 280, 70, 60, 70, 130];

$xml = $sheet->render('Prenotazioni', $colWidths, $freezeAfterRow, 'Landscape');

$slug = preg_replace('/[^a-z0-9\-]+/i', '_', $evento['slug'] ?: ('evento_' . $eventoId));
$filename = 'prenotazioni_' . $slug . '_' . date('Y-m-d') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo $xml;
exit;