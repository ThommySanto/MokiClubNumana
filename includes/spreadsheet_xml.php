<?php
/**
 * SpreadsheetXmlBuilder
 * ---------------------------------------------------------------------
 * Generatore minimale di fogli Excel in formato "SpreadsheetML" (il
 * formato XML introdotto con Office 2003, ancora pienamente supportato
 * da Excel, LibreOffice e Google Sheets).
 *
 * Perché questo formato e non un vero .xlsx?
 * Un .xlsx è tecnicamente un file ZIP con dentro vari XML: per generarlo
 * servirebbe l'estensione PHP "ZipArchive", che su alcuni hosting
 * economici (come questo) non è garantita. Il formato usato qui è un
 * singolo file di testo XML: nessuna dipendenza, funziona ovunque gira
 * PHP, e si apre in Excel con un doppio click esattamente come un file
 * normale, con tanto di colori, bordi, celle unite e impostazioni di
 * stampa (orientamento, adattamento a una pagina).
 * ---------------------------------------------------------------------
 */
class SpreadsheetXmlBuilder
{
    private array $styles = [];
    private array $rows = [];
    private array $currentRow = [];
    private ?int $currentRowHeight = null;

    /**
     * Registra uno stile riutilizzabile.
     * Opzioni supportate: bold, italic, size, color (#RRGGBB), fill (#RRGGBB),
     * align (Left|Center|Right), valign (Top|Center|Bottom), wrap (bool),
     * indent (int), border (bool), format (numero/valuta, es. '"€" #,##0.00').
     */
    public function addStyle(string $id, array $opts): void
    {
        $this->styles[$id] = $opts;
    }

    public function startRow(?int $height = null): void
    {
        $this->currentRow = [];
        $this->currentRowHeight = $height;
    }

    /**
     * @param string|int|float|null $value
     * @param string $type 'String' | 'Number'
     */
    public function addCell($value, ?string $styleId = null, string $type = 'String', int $mergeAcross = 0, ?string $formula = null): void
    {
        $this->currentRow[] = [
            'value'   => $value,
            'style'   => $styleId,
            'type'    => $type,
            'merge'   => $mergeAcross,
            'formula' => $formula,
        ];
    }

    public function endRow(): void
    {
        $this->rows[] = ['cells' => $this->currentRow, 'height' => $this->currentRowHeight];
        $this->currentRow = [];
        $this->currentRowHeight = null;
    }

    private function esc($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function renderStyles(): string
    {
        $out = "<Styles>\n";
        $out .= '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="10"/></Style>' . "\n";

        foreach ($this->styles as $id => $o) {
            $out .= '<Style ss:ID="' . $this->esc($id) . '">';

            $fontAttrs = ' ss:FontName="Calibri" ss:Size="' . (float) ($o['size'] ?? 10) . '"';
            if (!empty($o['bold'])) $fontAttrs .= ' ss:Bold="1"';
            if (!empty($o['italic'])) $fontAttrs .= ' ss:Italic="1"';
            if (!empty($o['color'])) $fontAttrs .= ' ss:Color="' . $this->esc($o['color']) . '"';
            $out .= '<Font' . $fontAttrs . '/>';

            $h = $o['align'] ?? null;
            $v = $o['valign'] ?? 'Center';
            $wrap = !empty($o['wrap']) ? ' ss:WrapText="1"' : '';
            $indent = isset($o['indent']) ? ' ss:Indent="' . (int) $o['indent'] . '"' : '';
            $out .= '<Alignment' . ($h ? ' ss:Horizontal="' . $this->esc($h) . '"' : '') . ' ss:Vertical="' . $this->esc($v) . '"' . $wrap . $indent . '/>';

            if (!empty($o['fill'])) {
                $out .= '<Interior ss:Color="' . $this->esc($o['fill']) . '" ss:Pattern="Solid"/>';
            }

            if (!empty($o['border'])) {
                $out .= '<Borders>';
                foreach (['Left', 'Top', 'Right', 'Bottom'] as $pos) {
                    $out .= '<Border ss:Position="' . $pos . '" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B7B7B7"/>';
                }
                $out .= '</Borders>';
            }

            if (!empty($o['format'])) {
                $out .= '<NumberFormat ss:Format="' . $this->esc($o['format']) . '"/>';
            }

            $out .= "</Style>\n";
        }

        $out .= "</Styles>\n";
        return $out;
    }

    private function renderRows(): string
    {
        $out = '';
        foreach ($this->rows as $row) {
            $h = $row['height'] ? ' ss:Height="' . (int) $row['height'] . '"' : '';
            $out .= '<Row' . $h . '>' . "\n";
            foreach ($row['cells'] as $cell) {
                $attrs = '';
                if ($cell['style']) $attrs .= ' ss:StyleID="' . $this->esc($cell['style']) . '"';
                if ($cell['merge'] > 0) $attrs .= ' ss:MergeAcross="' . (int) $cell['merge'] . '"';
                if ($cell['formula']) $attrs .= ' ss:Formula="' . $this->esc($cell['formula']) . '"';

                $out .= '<Cell' . $attrs . '>';
                if ($cell['value'] !== null && $cell['value'] !== '') {
                    $out .= '<Data ss:Type="' . $this->esc($cell['type']) . '">' . $this->esc($cell['value']) . '</Data>';
                } elseif ($cell['formula']) {
                    $out .= '<Data ss:Type="Number">0</Data>';
                }
                $out .= '</Cell>' . "\n";
            }
            $out .= "</Row>\n";
        }
        return $out;
    }

    /**
     * Genera l'XML completo del workbook.
     * @param array $colWidths larghezza colonne (punti)
     * @param int $freezeAfterRow blocca le prime N righe (0 = nessun blocco)
     * @param string $orientation 'Landscape' | 'Portrait'
     */
    public function render(string $sheetName, array $colWidths, int $freezeAfterRow = 0, string $orientation = 'Landscape'): string
    {
        $cols = '';
        foreach ($colWidths as $w) {
            $cols .= '<Column ss:Width="' . (float) $w . '"/>' . "\n";
        }

        $freeze = '';
        if ($freezeAfterRow > 0) {
            $freeze = '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>' . (int) $freezeAfterRow . '</SplitHorizontal>'
                . '<TopRowBottomPane>' . (int) $freezeAfterRow . '</TopRowBottomPane><ActivePane>2</ActivePane>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

        $xml .= '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Title>' . $this->esc($sheetName) . '</Title></DocumentProperties>' . "\n";
        $xml .= '<ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"><WindowHeight>9000</WindowHeight><WindowWidth>15000</WindowWidth><ProtectStructure>False</ProtectStructure><ProtectWindows>False</ProtectWindows></ExcelWorkbook>' . "\n";

        $xml .= $this->renderStyles();

        $xml .= '<Worksheet ss:Name="' . $this->esc($sheetName) . '">' . "\n";
        $xml .= '<Table ss:DefaultRowHeight="18">' . "\n";
        $xml .= $cols;
        $xml .= $this->renderRows();
        $xml .= "</Table>\n";

        $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<PageSetup><Layout x:Orientation="' . $this->esc($orientation) . '"/><PageMargins x:Bottom="0.5" x:Left="0.4" x:Right="0.4" x:Top="0.5"/></PageSetup>' . "\n";
        $xml .= "<FitToPage/>\n";
        $xml .= '<Print><PaperSizeIndex>9</PaperSizeIndex><FitWidth>1</FitWidth><FitHeight>1</FitHeight><ValidPrinterInfo/></Print>' . "\n";
        $xml .= "<Selected/>\n";
        $xml .= $freeze . "\n";
        $xml .= "</WorksheetOptions>\n";
        $xml .= "</Worksheet>\n";
        $xml .= '</Workbook>';

        return $xml;
    }
}
