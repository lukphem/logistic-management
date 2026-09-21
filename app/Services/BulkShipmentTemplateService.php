<?php

namespace App\Services;

use App\Models\State;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Generates the downloadable bulk-upload template — one row per
 * shipment, with the destination State and City columns backed by
 * real dropdowns rather than free text. Built fresh from whatever
 * states/cities currently exist in the database every time it's
 * downloaded, so a newly added city shows up automatically without
 * this service needing to change.
 *
 * Cascading works the standard Excel way: every state gets its own
 * named range holding just its cities, and the City column's data
 * validation formula looks up the right one with INDIRECT() based on
 * whatever's in that row's own State cell — so picking "Lagos"
 * narrows the City dropdown to only Lagos towns, "Abuja" to only
 * Abuja's, and so on.
 */
class BulkShipmentTemplateService
{
    public const ROW_LIMIT = 1000;

    public const COLUMNS = [
        'receiver_name', 'receiver_phone', 'receiver_alternate_phone', 'receiver_email',
        'destination_address', 'destination_state', 'destination_city',
        'package_description', 'quantity', 'weight_kg', 'length_cm', 'width_cm', 'height_cm',
        'is_cod', 'cod_amount', 'special_instructions',
    ];

    public const REQUIRED_COLUMNS = [
        'receiver_name', 'receiver_phone', 'destination_address',
        'destination_state', 'destination_city', 'package_description', 'quantity',
    ];

    public function generate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $this->buildShipmentsSheet($spreadsheet);
        $this->buildLookupSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function save(Spreadsheet $spreadsheet, string $path): void
    {
        (new Xlsx($spreadsheet))->save($path);
    }

    private function buildShipmentsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Shipments');

        foreach (array_values(self::COLUMNS) as $i => $column) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $label = ucwords(str_replace('_', ' ', $column)) . (in_array($column, self::REQUIRED_COLUMNS, true) ? ' *' : '');
            $sheet->setCellValue("{$letter}1", $label);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $stateColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search('destination_state', self::COLUMNS, true) + 1);
        $cityColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search('destination_city', self::COLUMNS, true) + 1);
        $codColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search('is_cod', self::COLUMNS, true) + 1);

        for ($row = 2; $row <= self::ROW_LIMIT + 1; $row++) {
            $stateValidation = $sheet->getCell("{$stateColLetter}{$row}")->getDataValidation();
            $stateValidation->setType(DataValidation::TYPE_LIST);
            $stateValidation->setErrorStyle(DataValidation::STYLE_STOP);
            $stateValidation->setAllowBlank(true);
            $stateValidation->setShowDropDown(true);
            $stateValidation->setShowErrorMessage(true);
            $stateValidation->setErrorTitle('Invalid state');
            $stateValidation->setError('Pick a state from the dropdown list.');
            $stateValidation->setFormula1('=StateList');

            // The cascading part: this row's City dropdown pulls from
            // the named range matching this row's own State cell —
            // SUBSTITUTE handles state names with spaces, since a
            // named range can't contain one.
            $cityValidation = $sheet->getCell("{$cityColLetter}{$row}")->getDataValidation();
            $cityValidation->setType(DataValidation::TYPE_LIST);
            $cityValidation->setErrorStyle(DataValidation::STYLE_STOP);
            $cityValidation->setAllowBlank(true);
            $cityValidation->setShowDropDown(true);
            $cityValidation->setShowErrorMessage(true);
            $cityValidation->setErrorTitle('Invalid city');
            $cityValidation->setError('Pick a state first, then a city from that state\'s list.');
            $cityValidation->setFormula1("=INDIRECT(SUBSTITUTE({$stateColLetter}{$row},\" \",\"_\"))");

            $codValidation = $sheet->getCell("{$codColLetter}{$row}")->getDataValidation();
            $codValidation->setType(DataValidation::TYPE_LIST);
            $codValidation->setErrorStyle(DataValidation::STYLE_STOP);
            $codValidation->setAllowBlank(true);
            $codValidation->setShowDropDown(true);
            $codValidation->setFormula1('"Yes,No"');
        }
    }

    /**
     * A hidden sheet holding the raw lookup data: every state's name
     * in column A (the source for the State dropdown itself, as the
     * named range "StateList"), and one further column per state
     * listing just that state's cities (each its own named range,
     * named after the state).
     */
    private function buildLookupSheet(Spreadsheet $spreadsheet): void
    {
        $lookup = $spreadsheet->createSheet();
        $lookup->setTitle('Lookup');

        $states = State::with('cities')->orderBy('name')->get();

        foreach ($states as $i => $state) {
            $lookup->setCellValue([1, $i + 1], $state->name);
        }

        if ($states->isNotEmpty()) {
            $spreadsheet->addNamedRange(new NamedRange('StateList', $lookup, "\$A\$1:\$A\${$states->count()}"));
        }

        foreach ($states as $colIndex => $state) {
            $col = $colIndex + 2; // column B onward, one per state
            $cities = $state->cities->sortBy('name')->values();

            foreach ($cities as $rowIndex => $city) {
                $lookup->setCellValue([$col, $rowIndex + 1], $city->name);
            }

            if ($cities->isNotEmpty()) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                // Named ranges can't contain spaces or most punctuation
                // - state names are sanitized the same way the City
                // column's INDIRECT(SUBSTITUTE(...)) formula expects.
                $rangeName = preg_replace('/[^A-Za-z0-9_]/', '_', $state->name);
                $spreadsheet->addNamedRange(new NamedRange($rangeName, $lookup, "\${$colLetter}\$1:\${$colLetter}\${$cities->count()}"));
            }
        }

        $lookup->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
    }
}
