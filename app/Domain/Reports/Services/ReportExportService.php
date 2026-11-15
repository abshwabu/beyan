<?php

namespace App\Domain\Reports\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Stream report data as an Excel-compatible CSV file.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function exportCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename).'_'.now()->format('Ymd_His').'.csv';

        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$safeFilename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 Byte Order Mark (BOM) so Microsoft Excel opens Arabic/Amharic characters properly
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            fputcsv($handle, $headers);

            // Write rows
            foreach ($rows as $row) {
                // Ensure values are flattened array matching header count
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, 200, $responseHeaders);
    }
}
