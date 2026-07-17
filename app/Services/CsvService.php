<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvService
{
    /**
     * Streams a CSV download — used for every "Export" button. $rows is
     * an array of associative arrays; $headers defines column order and
     * the header row text.
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Parses an uploaded CSV into an array of associative arrays, keyed
     * by the header row — so a re-uploaded file that was exported from
     * this same screen (or hand-built matching the same columns) maps
     * straight onto named fields, in any column order.
     *
     * @return array<int, array<string, string>>
     */
    public function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => trim(strtolower($h)), $header);

        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === 1 && trim($line[0]) === '') {
                continue; // skip blank trailing lines
            }

            $rows[] = array_combine($header, array_pad($line, count($header), null));
        }

        fclose($handle);

        return $rows;
    }
}
