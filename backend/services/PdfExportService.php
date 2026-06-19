<?php

declare(strict_types=1);

namespace backend\services;

use common\models\ApiaryStand;
use common\models\Batch;
use common\models\Colony;
use common\models\CompanyProfile;
use common\models\Inspection;
use common\models\Treatment;
use common\models\User;
use Mpdf\Mpdf;
use Yii;

/**
 * PdfExportService — all mPDF generation for the Compliance module.
 *
 * Controllers build the underlying query and hand the resulting models to this
 * service, which is solely responsible for turning them into print-ready PDF
 * binaries. Two documents are produced:
 *
 *   bestandsbuch() — landscape A4 treatment ledger faithfully reproducing the
 *                    official EU Reg. 2019/6 Art. 108(2) template.
 *   stockkarte()   — portrait A4 chronological colony record.
 *
 * Each public method returns the raw PDF bytes; streaming them as a download is
 * the controller's responsibility.
 */
class PdfExportService
{
    /** Light-blue header fill matching the official Bestandsbuch template. */
    private const HEADER_FILL = '#dbe5f1';

    /** @var array<int,string> resolved username cache keyed by user id */
    private array $usernameCache = [];

    // ── Bestandsbuch (landscape A4) ───────────────────────────────────────

    /**
     * Builds the Bestandsbuch treatment ledger PDF. A null $stand means the
     * export spans all stands; in that case each row shows its own stand and the
     * header carries the "all stands" identifier.
     *
     * @param Treatment[] $treatments treatments for the stand, chronological asc
     * @return string raw PDF bytes
     */
    public function bestandsbuch(
        ?ApiaryStand $stand,
        CompanyProfile $company,
        array $treatments,
        string $dateFrom,
        string $dateTo,
    ): string {
        $mpdf = $this->createMpdf('L');

        $keeperAddress = trim(sprintf(
            '%s, %s %s',
            $company->address,
            $company->postcode,
            $company->city,
        ), ', ');

        // Footer: the EU regulation legal reference, on every page.
        $mpdf->SetHTMLFooter(
            '<div style="text-align:center; font-size:7pt; color:#333; border-top:0.5pt solid #999; padding-top:2mm;">'
            . 'gem&auml;&szlig; Verordnung (EU) 2019/6 DES EUROP&Auml;ISCHEN PARLAMENTS UND DES RATES vom 11. Dezember 2018'
            . '</div>'
        );

        $html = $this->bestandsbuchHtml($stand, $company, $keeperAddress, $treatments, $dateFrom, $dateTo);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function bestandsbuchHtml(
        ?ApiaryStand $stand,
        CompanyProfile $company,
        string $keeperAddress,
        array $treatments,
        string $dateFrom,
        string $dateTo,
    ): string {
        $documentNumber = $stand !== null ? $stand->stand_code : 'Alle Stände — Landkreis Hof';

        // Top header block: Eigentümer / Halter (left) and Nummer (right),
        // reproducing the boxed layout of the official template.
        $header = '<table class="hdr" cellpadding="3" cellspacing="0">'
            . '<tr>'
            . '<td class="hdr-label">Eigent&uuml;mer / Halter<br>(Name; Anschrift)<br><span class="art">Art. 108 (2) f</span></td>'
            . '<td class="hdr-value">' . $this->e($company->keeper_name) . '<br>' . $this->e($keeperAddress) . '</td>'
            . '<td class="hdr-num"><strong>Nummer:</strong> ' . $this->e($documentNumber) . '</td>'
            . '</tr>'
            . '</table>';

        $title = '<div class="title">Bestandsbuch &uuml;ber die Anwendung von Arzneimitteln</div>';

        // Column definitions: [heading html, article ref, css width %].
        $columns = [
            ['Datum', 'Art. 108 (2) a', 7],
            ['Bezeichnung des Arzneimittels<br>(+ Charge)', 'Art. 108 (2) b', 16],
            ['Menge pro<br>Bienenvolk', 'Art. 108 (2) b', 7],
            ['Name und Anschrift<br>des Lieferanten', 'Art. 108 (2) c', 15],
            ['Belegnummer', 'Art. 108 (2) d', 7],
            ['Standort der Bienenv&ouml;lker<br>(Flurnummer oder Bezeichnung)', 'Art. 108 (2) e', 13],
            ['Nummern der<br>Bienenv&ouml;lker', 'Art. 108 (2) e', 8],
            ['ggf. Name und Anschrift<br>des Tierarztes', 'Art. 108 (2) g', 12],
            ['Wartezeit<br>(laut Packungsbeilage)', 'Art. 108 (2) h', 8],
            ['Behandlungsdauer<br>in Tagen', 'Art. 108 (2) i', 7],
        ];

        $colgroup = '';
        $headRow  = '';
        $artRow   = '';
        foreach ($columns as [$heading, $art, $width]) {
            $colgroup .= '<col style="width:' . $width . '%">';
            $headRow  .= '<th>' . $heading . '</th>';
            $artRow   .= '<td class="art-cell">' . $art . '</td>';
        }

        $bodyRows = '';
        foreach ($treatments as $t) {
            $bodyRows .= $this->bestandsbuchRow($t, $stand);
        }
        if ($bodyRows === '') {
            $bodyRows = '<tr><td colspan="' . count($columns)
                . '" class="empty">Keine Behandlungen im gew&auml;hlten Zeitraum.</td></tr>';
        }

        $table = '<table class="ledger" cellpadding="3" cellspacing="0">'
            . '<colgroup>' . $colgroup . '</colgroup>'
            . '<thead>'
            . '<tr class="head">' . $headRow . '</tr>'
            . '<tr class="art-row">' . $artRow . '</tr>'
            . '</thead>'
            . '<tbody>' . $bodyRows . '</tbody>'
            . '</table>';

        return $this->bestandsbuchCss() . $header . $title . $table;
    }

    private function bestandsbuchRow(Treatment $t, ?ApiaryStand $stand): string
    {
        $product = $this->e($t->product_name);
        if ($t->pharmaceutical_batch_number !== null && $t->pharmaceutical_batch_number !== '') {
            $product .= '<br><span class="charge">CH.-B: ' . $this->e($t->pharmaceutical_batch_number) . '</span>';
        }

        $supplier = $this->e($t->supplier_name);
        if ($t->supplier_address !== null && $t->supplier_address !== '') {
            $supplier .= '<br>' . $this->e($t->supplier_address);
        }

        // For an all-stands export each row reflects the treatment's own stand.
        $rowStand = $stand ?? $t->apiaryStand;
        if ($rowStand !== null) {
            $location = $this->e($rowStand->name) . '<br><span class="muted">' . $this->e($rowStand->stand_code);
            if ($rowStand->landkreis !== null && $rowStand->landkreis !== '') {
                $location .= ', ' . $this->e($rowStand->landkreis);
            }
            $location .= '</span>';
        } else {
            $location = '';
        }

        $wartezeit = sprintf(
            '%d Tage / bis %s',
            (int) $t->withdrawal_days,
            $this->germanDate($t->wartezeit_expiry),
        );

        $cells = [
            $this->germanDate($t->application_date),
            $product,
            $this->e($t->quantity_per_colony),
            $supplier,
            $this->e($t->receipt_number ?? ''),
            $location,
            $this->e($t->colony->colony_code ?? ''),
            $this->e($t->veterinarian ?? ''),
            $wartezeit,
            $this->e((string) $t->treatment_duration_days),
        ];

        return '<tr>' . implode('', array_map(static fn (string $c): string => '<td>' . $c . '</td>', $cells)) . '</tr>';
    }

    private function bestandsbuchCss(): string
    {
        $fill = self::HEADER_FILL;
        return <<<CSS
            <style>
                .hdr { width:100%; border-collapse:collapse; margin-bottom:4mm; }
                .hdr td { border:0.5pt solid #000; vertical-align:middle; font-size:8pt; }
                .hdr-label { width:22%; text-align:center; font-weight:bold; background:$fill; }
                .hdr-value { width:58%; }
                .hdr-num   { width:20%; }
                .hdr .art { font-weight:normal; font-size:6.5pt; }
                .title { text-align:center; font-size:15pt; font-weight:bold; margin:1mm 0 4mm 0; }
                .ledger { width:100%; border-collapse:collapse; }
                .ledger th, .ledger td { border:0.5pt solid #000; font-size:7pt; vertical-align:top; }
                .ledger tr.head th { background:$fill; text-align:center; font-weight:bold; font-size:7.5pt; }
                .ledger tr.art-row td { background:$fill; text-align:center; font-size:6pt; color:#222; }
                .ledger .charge { font-size:6.5pt; color:#333; }
                .ledger .muted  { font-size:6.5pt; color:#555; }
                .ledger .empty  { text-align:center; font-style:italic; color:#777; padding:6mm; }
            </style>
            CSS;
    }

    // ── Stockkarte (portrait A4) ──────────────────────────────────────────

    /**
     * Builds the Stockkarte chronological colony record PDF.
     *
     * @param array<int,array{type:string,date:string,record:object}> $entries
     *        chronological ascending list of inspection/treatment/harvest/feeding entries
     * @return string raw PDF bytes
     */
    public function stockkarte(Colony $colony, array $entries): string
    {
        $mpdf = $this->createMpdf('P');

        $mpdf->SetHTMLFooter(
            '<div style="text-align:center; font-size:7pt; color:#555; border-top:0.5pt solid #999; padding-top:2mm;">'
            . $this->e($colony->colony_code) . ' &middot; Export: ' . date('d.m.Y')
            . ' &middot; Digitale Stockkarte &mdash; Honigmanufaktur Lindenhof Apiary Management System'
            . '</div>'
        );

        $html = $this->stockkarteCss()
            . $this->stockkarteHeader($colony)
            . $this->stockkarteTable($entries);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Builds a single Stockkarte PDF covering every colony at a stand, each
     * colony starting on its own page with its own header block (US-CO-04).
     *
     * @param Colony[] $colonies
     * @return string raw PDF bytes
     */
    public function stockkarteForStand(ApiaryStand $stand, array $colonies): string
    {
        $mpdf = $this->createMpdf('P');

        $mpdf->SetHTMLFooter(
            '<div style="text-align:center; font-size:7pt; color:#555; border-top:0.5pt solid #999; padding-top:2mm;">'
            . $this->e($stand->stand_code) . ' &middot; Export: ' . date('d.m.Y')
            . ' &middot; Digitale Stockkarte &mdash; Honigmanufaktur Lindenhof Apiary Management System'
            . '</div>'
        );

        $html = $this->stockkarteCss();
        if ($colonies === []) {
            $html .= '<p class="muted">Diesem Stand sind derzeit keine Bienenv&ouml;lker zugeordnet.</p>';
        }

        $first = true;
        foreach ($colonies as $colony) {
            // getStockkarte() is newest-first; render chronologically.
            $entries = $colony->getStockkarte();
            usort($entries, static fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));

            if (!$first) {
                $html .= '<pagebreak />';
            }
            $html .= $this->stockkarteHeader($colony) . $this->stockkarteTable($entries);
            $first = false;
        }

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function stockkarteHeader(Colony $colony): string
    {
        $expiry = $colony->getLatestWartezeitExpiry();
        if ($expiry === null || $colony->isWithdrawalCleared()) {
            $wartezeit = '<span class="wz-ok">Keine aktive Wartezeit</span>';
        } else {
            $wartezeit = '<span class="wz-block">In Wartezeit bis ' . $this->germanDate($expiry) . '</span>';
        }

        $stand = $colony->apiaryStand;
        $standName = $stand !== null
            ? $this->e($stand->name) . ' (' . $this->e($stand->stand_code) . ')'
            : '&mdash;';

        return '<div class="sk-header">'
            . '<div class="sk-code">' . $this->e($colony->colony_code) . '</div>'
            . '<table class="sk-meta" cellpadding="2" cellspacing="0"><tr>'
            . '<td><div class="lbl">Stand</div>' . $standName . '</td>'
            . '<td><div class="lbl">K&ouml;niginnenjahr</div>' . $this->e((string) $colony->queen_year) . '</td>'
            . '<td><div class="lbl">Status</div>' . $this->e(ucfirst($colony->status)) . '</td>'
            . '<td><div class="lbl">Wartezeit</div>' . $wartezeit . '</td>'
            . '</tr></table>'
            . '</div>';
    }

    /**
     * @param array<int,array{type:string,date:string,record:object}> $entries
     */
    private function stockkarteTable(array $entries): string
    {
        $rows = '';
        foreach ($entries as $entry) {
            $type   = $entry['type'];
            $record = $entry['record'];
            $rows .= '<tr>'
                . '<td class="c-date">' . $this->germanDate((string) $entry['date']) . '</td>'
                . '<td class="c-type">' . $this->typeLabel($type) . '</td>'
                . '<td class="c-details">' . $this->describeEntry($type, $record) . '</td>'
                . '<td class="c-user">' . $this->e($this->resolveUsername($record->created_by ?? null)) . '</td>'
                . '</tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="empty">Keine Eintr&auml;ge vorhanden.</td></tr>';
        }

        return '<table class="sk-table" cellpadding="4" cellspacing="0">'
            . '<colgroup><col style="width:13%"><col style="width:14%"><col style="width:58%"><col style="width:15%"></colgroup>'
            . '<thead><tr>'
            . '<th>Datum</th><th>Typ</th><th>Details</th><th>Durchgef&uuml;hrt von</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    /**
     * Renders the small coloured event-type label.
     */
    private function typeLabel(string $type): string
    {
        [$text, $class] = match ($type) {
            'inspection' => ['Inspektion', 'lbl-insp'],
            'treatment'  => ['Behandlung', 'lbl-treat'],
            'harvest'    => ['Ernte', 'lbl-harvest'],
            'feeding'    => ['F&uuml;tterung', 'lbl-feed'],
            default      => [ucfirst($type), 'lbl-insp'],
        };
        return '<span class="type-label ' . $class . '">' . $text . '</span>';
    }

    /**
     * Writes out all relevant field values for a Stockkarte entry as labelled lines.
     */
    private function describeEntry(string $type, object $record): string
    {
        $pairs = match ($type) {
            'inspection' => [
                'Wetter'           => $record->weather,
                'Brutbild (1-5)'   => $record->brood_pattern_score,
                'K&ouml;nigin gesehen' => $record->queen_sighted ? 'ja' : 'nein',
                'Krankheitsanz.'   => $record->disease_indicators,
                'Notizen'          => $record->notes,
            ],
            'treatment' => [
                'Art'        => Treatment::typeLabels()[$record->treatment_type] ?? $record->treatment_type,
                'Produkt'    => $record->product_name,
                'Charge'     => $record->pharmaceutical_batch_number,
                'Menge'      => $record->quantity_per_colony,
                'Lieferant'  => $record->supplier_name,
                'Wartezeit'  => $this->treatmentWartezeit($record),
                'Bearbeiter' => $record->operator_name,
            ],
            'harvest' => [
                'Losnummer'   => $record->lot_number,
                'Sorte'       => $record->honey_variety,
                'Menge (kg)'  => $record->harvest_quantity_kg,
                'Status'      => $this->batchStatusLabel($record),
            ],
            'feeding' => [
                'Menge'   => $record->feeding_quantity,
                'Wetter'  => $record->weather,
                'Notizen' => $record->notes,
            ],
            default => [],
        };

        $lines = [];
        foreach ($pairs as $label => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = '<strong>' . $label . ':</strong> ' . $this->e((string) $value);
            }
        }
        return $lines === [] ? '<span class="muted">&mdash;</span>' : implode('<br>', $lines);
    }

    private function treatmentWartezeit(object $record): string
    {
        return sprintf(
            '%d Tage / bis %s',
            (int) $record->withdrawal_days,
            $this->germanDate($record->wartezeit_expiry),
        );
    }

    private function batchStatusLabel(object $record): string
    {
        return match ($record->status) {
            Batch::STATUS_PENDING_RELEASE => 'Freigabe ausstehend',
            Batch::STATUS_RELEASED        => 'Freigegeben',
            Batch::STATUS_REVIEW_REQUIRED => 'Pr&uuml;fung erforderlich',
            default                       => (string) $record->status,
        };
    }

    private function stockkarteCss(): string
    {
        return <<<CSS
            <style>
                .sk-header { border:0.6pt solid #333; border-radius:2mm; padding:4mm; margin-bottom:5mm; background:#fbfbf7; }
                .sk-code { font-size:24pt; font-weight:bold; color:#8a5a00; margin-bottom:2mm; }
                .sk-meta { width:100%; }
                .sk-meta td { font-size:9pt; vertical-align:top; padding-right:4mm; }
                .sk-meta .lbl { font-size:7pt; text-transform:uppercase; color:#888; letter-spacing:0.5pt; margin-bottom:0.5mm; }
                .wz-ok { color:#1a7a35; font-weight:bold; }
                .wz-block { color:#c0241c; font-weight:bold; }
                .sk-table { width:100%; border-collapse:collapse; }
                .sk-table th, .sk-table td { border:0.5pt solid #ccc; font-size:8.5pt; vertical-align:top; text-align:left; }
                .sk-table thead th { background:#f0ede4; font-weight:bold; }
                .sk-table .c-date { white-space:nowrap; }
                .sk-table .muted { color:#999; }
                .sk-table .empty { text-align:center; font-style:italic; color:#777; padding:6mm; }
                .type-label { display:inline-block; padding:1pt 4pt; border-radius:2pt; font-size:7.5pt; font-weight:bold; color:#fff; }
                .lbl-insp    { background:#1a7a35; }
                .lbl-treat   { background:#c0241c; }
                .lbl-harvest { background:#d18f00; }
                .lbl-feed    { background:#1763b6; }
            </style>
            CSS;
    }

    // ── Shared helpers ────────────────────────────────────────────────────

    private function createMpdf(string $orientation): Mpdf
    {
        $tempDir = Yii::getAlias('@backend/runtime/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        return new Mpdf([
            'mode'           => 'utf-8',
            'format'         => 'A4-' . $orientation, // A4-L (landscape) | A4-P (portrait)
            'orientation'    => $orientation,
            'margin_left'    => 8,
            'margin_right'   => 8,
            'margin_top'     => 10,
            'margin_bottom'  => 14,
            'margin_footer'  => 6,
            'tempDir'        => $tempDir,
        ]);
    }

    /**
     * Formats a Y-m-d date string as German DD.MM.YYYY. Empty/invalid → ''.
     */
    private function germanDate(?string $ymd): string
    {
        if ($ymd === null || $ymd === '' || $ymd === '0000-00-00') {
            return '';
        }
        $ts = strtotime($ymd);
        return $ts === false ? $this->e($ymd) : date('d.m.Y', $ts);
    }

    private function resolveUsername(?int $userId): string
    {
        if ($userId === null) {
            return '—';
        }
        if (!array_key_exists($userId, $this->usernameCache)) {
            $user = User::findOne($userId);
            $this->usernameCache[$userId] = $user->username ?? ('user #' . $userId);
        }
        return $this->usernameCache[$userId];
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
