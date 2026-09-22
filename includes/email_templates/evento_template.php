<?php
require_once dirname(dirname(__DIR__)) . '/security_headers.php';

function eventoEmailWrapper(string $titoloInterno, string $corpoHtml): string
{
    return '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;background:#e6f7ff;font-family:Arial,sans-serif;">

<div style="max-width:600px;margin:30px auto;background:white;border-radius:10px;overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,0.1);">

<div style="background:#0096c7;padding:20px;text-align:center;">
<img src="https://mokiclub.infinityfreeapp.com/assets/img/moki.jpg" style="width:150px;height:150px;border-radius:50%;object-fit:cover;">
</div>

<div style="padding:30px;">
' . $corpoHtml . '
</div>

<div style="background:#f0f8ff;padding:16px;text-align:center;font-size:12px;color:#888;">
MOKI CLUB NUMANA &middot; #MOKICLUBNUMANA_11
</div>

</div>
</body>
</html>
';
}

/**
 * Genera l'HTML dell'elenco tavole/partecipanti con tariffa e importo,
 * più il totale complessivo. Usato sia nell'email cliente che in quella staff.
 */
function eventoEmailRiepilogoPrezzi(array $tavole): array
{
    $labelsTariffa = ['tesserato' => 'Tesserato', 'non_tesserato' => 'Non tesserato', 'tavola_propria' => 'Tavola propria'];
    $elenco = '';
    $totale = 0.0;
    foreach ($tavole as $t) {
        $importo = (float) ($t['importo_tariffa'] ?? 0);
        $totale += $importo;
        $tariffaLabel = $labelsTariffa[$t['tipo_tariffa'] ?? ''] ?? ($t['tipo_tariffa'] ?? '');
        $elenco .= '<li>' . htmlspecialchars($t['nome_tavola']) . ' — ' . htmlspecialchars($t['nome'] . ' ' . $t['cognome'])
            . ' — ' . htmlspecialchars($tariffaLabel) . ' — <strong>' . number_format($importo, 2, ',', '') . '€</strong></li>';
    }
    return ['html' => $elenco, 'totale' => $totale];
}

/**
 * Restituisce l'HTML descrittivo del metodo di pagamento scelto (solo informativo).
 */
function eventoEmailMetodoPagamentoHtml(?string $metodoPagamento): string
{
    if ($metodoPagamento === 'bonifico') {
        return '<p style="font-size:15px;color:#333;"><strong>Metodo di pagamento scelto:</strong> Bonifico bancario<br>IBAN: <strong>IT44K0538737471000004261922</strong></p>';
    }
    if ($metodoPagamento === 'contanti_carta') {
        return '<p style="font-size:15px;color:#333;"><strong>Metodo di pagamento scelto:</strong> Contanti/Carta al Moki Club Numana, prima dell\'evento</p>';
    }
    return '';
}

/**
 * Email inviata subito dopo l'invio della richiesta: prenotazione in attesa di approvazione.
 */
function templateEventoInAttesa(string $nome, array $evento, array $tavole, string $linkGestione, ?string $metodoPagamento = null): string
{
    $riepilogo = eventoEmailRiepilogoPrezzi($tavole);

    $corpo = '
<h2 style="color:#0077b6;">Ciao ' . htmlspecialchars($nome) . ',</h2>
<p style="font-size:16px;color:#333;">
Abbiamo ricevuto la tua richiesta di prenotazione per l\'evento <strong>' . htmlspecialchars($evento['titolo']) . '</strong>' .
(!empty($evento['data_evento']) ? ' del <strong>' . htmlspecialchars(formattaDataIt($evento['data_evento'])) . '</strong>' : '') . '.
</p>
<p style="font-size:16px;color:#333;">La tua richiesta è al momento <strong style="color:#e67e22;">in attesa di approvazione</strong> da parte dello staff. Ti invieremo una nuova email non appena verrà confermata.</p>
<p style="font-size:15px;color:#333;"><strong>Riepilogo tariffe:</strong></p>
<ul style="font-size:15px;color:#333;">' . $riepilogo['html'] . '</ul>
<p style="font-size:16px;color:#333;text-align:right;border-top:1px solid #eee;padding-top:10px;"><strong>Totale: ' . number_format($riepilogo['totale'], 2, ',', '') . '€</strong></p>
' . eventoEmailMetodoPagamentoHtml($metodoPagamento) . '
<p style="text-align:center;margin-top:24px;">
<a href="' . htmlspecialchars($linkGestione) . '" style="background:#0096c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Gestisci la tua prenotazione</a>
</p>
<p style="font-size:13px;color:#888;margin-top:20px;">Con il link qui sopra potrai consultare o annullare la tua prenotazione in autonomia.</p>
';

    return eventoEmailWrapper('in attesa', $corpo);
}

/**
 * Email inviata quando l'admin conferma la prenotazione.
 */
function templateEventoConfermata(string $nome, array $evento, string $linkGestione): string
{
    $corpo = '
<h2 style="color:#0077b6;">Ciao ' . htmlspecialchars($nome) . ',</h2>
<p style="font-size:16px;color:#333;">
Ottime notizie! La tua prenotazione per l\'evento <strong>' . htmlspecialchars($evento['titolo']) . '</strong>' .
(!empty($evento['data_evento']) ? ' del <strong>' . htmlspecialchars(formattaDataIt($evento['data_evento'])) . '</strong>' : '') . '
è stata <strong style="color:#2ecc71;">confermata</strong>! 🌊
</p>
<p style="font-size:16px;color:#333;">Ti aspettiamo! Per qualsiasi info non esitare a contattarci.</p>
<p style="text-align:center;margin-top:24px;">
<a href="' . htmlspecialchars($linkGestione) . '" style="background:#0096c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Vedi la tua prenotazione</a>
</p>
<p style="font-size:18px;margin-top:24px;text-align:center;">🌊🐙🤙🏽</p>
';

    return eventoEmailWrapper('confermata', $corpo);
}

/**
 * Email inviata quando l'admin rifiuta la prenotazione.
 */
function templateEventoRifiutata(string $nome, array $evento): string
{
    $corpo = '
<h2 style="color:#0077b6;">Ciao ' . htmlspecialchars($nome) . ',</h2>
<p style="font-size:16px;color:#333;">
Ci dispiace, purtroppo la tua richiesta di prenotazione per l\'evento <strong>' . htmlspecialchars($evento['titolo']) . '</strong> non può essere confermata (posti esauriti o altro motivo organizzativo).
</p>
<p style="font-size:16px;color:#333;">Ti invitiamo a contattarci per maggiori informazioni o per valutare le prossime uscite in programma.</p>
';

    return eventoEmailWrapper('rifiutata', $corpo);
}

/**
 * Email di notifica interna allo staff quando arriva una nuova prenotazione.
 */
function templateEventoNotificaAdmin(array $evento, array $prenotazione, array $tavole): string
{
    $riepilogo = eventoEmailRiepilogoPrezzi($tavole);

    $corpo = '
<h2 style="color:#0077b6;">Nuova prenotazione ricevuta</h2>
<p style="font-size:15px;color:#333;"><strong>Evento:</strong> ' . htmlspecialchars($evento['titolo']) . '</p>
<p style="font-size:15px;color:#333;"><strong>Referente:</strong> ' . htmlspecialchars($prenotazione['referente_nome'] . ' ' . $prenotazione['referente_cognome']) . '</p>
<p style="font-size:15px;color:#333;"><strong>Telefono:</strong> ' . htmlspecialchars($prenotazione['referente_telefono']) . '</p>
<p style="font-size:15px;color:#333;"><strong>Email:</strong> ' . htmlspecialchars($prenotazione['referente_email']) . '</p>
' . eventoEmailMetodoPagamentoHtml($prenotazione['metodo_pagamento'] ?? null) . '
<p style="font-size:15px;color:#333;"><strong>Tariffe / Tavole:</strong></p>
<ul style="font-size:15px;color:#333;">' . $riepilogo['html'] . '</ul>
<p style="font-size:16px;color:#333;text-align:right;border-top:1px solid #eee;padding-top:10px;"><strong>Totale: ' . number_format($riepilogo['totale'], 2, ',', '') . '€</strong></p>
<p style="text-align:center;margin-top:20px;">
<a href="https://mokiclub.infinityfreeapp.com/admin/prenotazioni.php" style="background:#0096c7;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Vai al pannello admin</a>
</p>
';

    return eventoEmailWrapper('notifica-admin', $corpo);
}
