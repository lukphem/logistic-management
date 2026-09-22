<?php

namespace App\Services;

use App\Models\City;
use App\Models\Shipment;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * The bulk-upload counterpart to ShipmentCreationService — this is
 * what turns an uploaded file into shipments, but every actual
 * shipment it creates still goes through that same service, so a
 * bulk-created shipment is priced and validated identically to one
 * entered by hand. Two clean steps, matching the verify-then-confirm
 * shape used everywhere else in this app: validateRows() checks
 * everything and creates nothing; createShipments() only ever runs
 * on rows that already passed that check.
 */
class BulkShipmentImportService
{
    public function __construct(private ShipmentCreationService $creationService)
    {
    }

    /**
     * Reads the uploaded file (xlsx, xls, or csv — auto-detected) into
     * plain associative rows keyed by the template's own column names,
     * skipping the header row and any fully-blank trailing rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Shipments') ?? $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, false);
        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), array_shift($rows) ?? []);

        $parsed = [];
        foreach ($rows as $row) {
            if (collect($row)->every(fn ($v) => $v === null || trim((string) $v) === '')) {
                continue; // fully blank row - not a real entry
            }

            $parsed[] = array_combine($header, array_pad($row, count($header), null));
        }

        return $parsed;
    }

    private function normalizeHeader(string $header): string
    {
        // The template's own labels are "Receiver Name *" etc - this
        // reverses that back to receiver_name so an edited-but-still-
        // recognizable header (someone re-typed "Receiver name") still
        // matches, without demanding the exact template wording.
        $clean = strtolower(trim(preg_replace('/\s*\*\s*$/', '', $header)));

        return str_replace(' ', '_', $clean);
    }

    /**
     * Checks every row against the batch context and this app's own
     * shipment rules (required fields, the same address/description
     * length limits Increment 172 set, a resolvable destination city)
     * — without creating anything. Each row comes back tagged valid
     * or invalid, with a specific reason for every invalid one, and
     * (for valid rows) the fully-resolved data ready to hand straight
     * to createShipments() without re-checking any of it.
     *
     * @return array{valid: array, invalid: array}
     */
    public function validateRows(array $rows, array $batchContext): array
    {
        $valid = [];
        $invalid = [];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for zero-index, +1 for the header row
            $errors = [];

            $receiverName = trim((string) ($row['receiver_name'] ?? ''));
            $receiverPhone = trim((string) ($row['receiver_phone'] ?? ''));
            $destinationAddress = trim((string) ($row['destination_address'] ?? ''));
            $packageDescription = trim((string) ($row['package_description'] ?? ''));
            $stateName = trim((string) ($row['destination_state'] ?? ''));
            $cityName = trim((string) ($row['destination_city'] ?? ''));
            $quantity = $row['quantity'] ?? null;

            if ($receiverName === '') {
                $errors[] = 'Receiver name is required.';
            }
            if ($receiverPhone === '' || ! preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $receiverPhone)) {
                $errors[] = 'Receiver phone is required and must be a valid phone number.';
            }
            if ($destinationAddress === '') {
                $errors[] = 'Destination address is required.';
            } elseif (mb_strlen($destinationAddress) > 150) {
                $errors[] = 'Destination address is over the 150-character limit.';
            }
            if ($packageDescription === '') {
                $errors[] = 'Package description is required.';
            } elseif (mb_strlen($packageDescription) > 100) {
                $errors[] = 'Package description is over the 100-character limit.';
            }
            if (! is_numeric($quantity) || (int) $quantity < 1) {
                $errors[] = 'Quantity is required and must be at least 1.';
            }

            $city = null;
            if ($stateName === '' || $cityName === '') {
                $errors[] = 'Destination state and city are both required.';
            } else {
                $city = City::with('state')->whereHas('state', fn ($q) => $q->where('name', $stateName))->where('name', $cityName)->first();
                if (! $city) {
                    $errors[] = "\"{$cityName}\" in \"{$stateName}\" doesn't match a city in the system — pick from the dropdown provided in the template.";
                }
            }

            $specialInstructions = trim((string) ($row['special_instructions'] ?? ''));
            if (mb_strlen($specialInstructions) > 500) {
                $errors[] = 'Special instructions are over the 500-character limit.';
            }

            $isCod = strtolower(trim((string) ($row['is_cod'] ?? ''))) === 'yes';

            if ($errors) {
                $invalid[] = ['row' => $rowNumber, 'receiver_name' => $receiverName ?: null, 'errors' => $errors];

                continue;
            }

            $valid[] = [
                'row' => $rowNumber,
                // Display-only fields the preview table needs but
                // ShipmentCreationService has no use for (it only
                // needs the resolved destination_city_id) — kept
                // separate from 'data' rather than adding noise to
                // what actually gets passed through to create the
                // shipment.
                'display' => [
                    'destination_city_name' => $city->name,
                    'destination_state_name' => $city->state->name,
                ],
                'data' => [
                    ...$batchContext,
                    'receiver_name' => $receiverName,
                    'receiver_phone' => $receiverPhone,
                    'receiver_alternate_phone' => trim((string) ($row['receiver_alternate_phone'] ?? '')) ?: null,
                    'receiver_email' => trim((string) ($row['receiver_email'] ?? '')) ?: null,
                    'destination_address' => $destinationAddress,
                    'destination_city_id' => $city->id,
                    'destination_district_id' => null,
                    'package_description' => $packageDescription,
                    'quantity' => (int) $quantity,
                    'weight_kg' => is_numeric($row['weight_kg'] ?? null) ? (float) $row['weight_kg'] : null,
                    'length_cm' => is_numeric($row['length_cm'] ?? null) ? (float) $row['length_cm'] : null,
                    'width_cm' => is_numeric($row['width_cm'] ?? null) ? (float) $row['width_cm'] : null,
                    'height_cm' => is_numeric($row['height_cm'] ?? null) ? (float) $row['height_cm'] : null,
                    'is_cod' => $isCod,
                    'cod_amount' => $isCod && is_numeric($row['cod_amount'] ?? null) ? (float) $row['cod_amount'] : 0,
                    'special_instructions' => $specialInstructions ?: null,
                ],
            ];
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    /**
     * Persists a validateRows() result onto a batch as real rows —
     * this is what makes rows survive past the request that uploaded
     * them, so a second upload adds to what's already pending rather
     * than replacing it, and any individual row can be reviewed and
     * deleted before the batch is ever confirmed.
     */
    public function persistRows(\App\Models\BulkShipmentBatch $batch, array $validationResult): void
    {
        foreach ($validationResult['valid'] as $entry) {
            $batch->rows()->create([
                'source_row_number' => $entry['row'],
                'status' => 'valid',
                'receiver_name' => $entry['data']['receiver_name'],
                'row_data' => $entry['data'],
                'display_data' => $entry['display'],
            ]);
        }

        foreach ($validationResult['invalid'] as $entry) {
            $batch->rows()->create([
                'source_row_number' => $entry['row'],
                'status' => 'invalid',
                'receiver_name' => $entry['receiver_name'],
                'errors' => $entry['errors'],
            ]);
        }
    }

    /**
     * Only ever called with rows already sitting as 'valid' pending
     * rows on the batch. Each row is created independently through
     * the same ShipmentCreationService the web form uses, so one
     * row's pricing failure (a genuinely unpriceable route, say)
     * doesn't block the rest of the batch — same per-row success/
     * error separation as every scan-confirmation flow already in
     * this app. A row that succeeds is deleted immediately after —
     * it's a real shipment now, not a pending row anymore; a row
     * that fails is left in place so it still shows up for review
     * (and, if the person chooses, deletion) rather than silently
     * vanishing.
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\BulkShipmentBatchRow> $validRows
     * @return array{created: array, failed: array}
     */
    public function createShipments(\Illuminate\Support\Collection $validRows): array
    {
        $created = [];
        $failed = [];

        foreach ($validRows as $row) {
            try {
                $shipment = $this->creationService->createShipment($row->row_data);
                $created[] = ['row' => $row->source_row_number, 'id' => $shipment->id, 'tracking_number' => $shipment->tracking_number, 'receiver_name' => $shipment->receiver_name];
                $row->delete();
            } catch (\RuntimeException $e) {
                $failed[] = ['row' => $row->source_row_number, 'receiver_name' => $row->receiver_name, 'error' => $e->getMessage()];
            }
        }

        return ['created' => $created, 'failed' => $failed];
    }
}
