<?php

namespace App\Services;

class ReportCsvService
{
    public function render(array $sections): string
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($sections as $section) {
            fputcsv($handle, [$section['title']], ';');
            fputcsv($handle, $section['headers'], ';');

            foreach ($section['rows'] as $row) {
                fputcsv($handle, array_map(fn ($value) => $this->clean($value), $row), ';');
            }

            fputcsv($handle, [], ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function clean(mixed $value): string
    {
        return trim((string) $value);
    }
}
