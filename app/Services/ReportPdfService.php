<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class ReportPdfService
{
    private const PAGE_WIDTH = 595.28;

    private const PAGE_HEIGHT = 841.89;

    private const MARGIN = 36;

    private array $pages = [];

    private array $current = [];

    private float $y = 0;

    private int $pageNumber = 0;

    public function render(array $payload): string
    {
        $this->pages = [];
        $this->newPage($payload);
        $this->header($payload);
        $this->filters($payload['filters'] ?? []);
        $this->summary($payload['summary'] ?? []);
        $this->analysis($payload['analysis'] ?? []);

        foreach (($payload['chart_data'] ?? []) as $table) {
            $this->table($table['title'], $table['headers'], $table['rows'], 18);
        }

        foreach (($payload['tables'] ?? []) as $table) {
            $this->table($table['title'], $table['headers'], $table['rows'], 24);
        }

        $this->commitPage();

        return $this->buildPdf();
    }

    private function newPage(array $payload): void
    {
        if ($this->current) {
            $this->commitPage();
        }

        $this->pageNumber++;
        $this->current = [];
        $this->y = self::PAGE_HEIGHT - self::MARGIN;
        $this->footer($payload);
    }

    private function commitPage(): void
    {
        if ($this->current) {
            $this->pages[] = implode("\n", $this->current);
            $this->current = [];
        }
    }

    private function header(array $payload): void
    {
        $this->rect(0, self::PAGE_HEIGHT - 92, self::PAGE_WIDTH, 92, [10, 61, 98]);
        $this->text(self::MARGIN, self::PAGE_HEIGHT - 42, 'THERMO PREDICT', 20, true, [255, 255, 255]);
        $this->text(self::MARGIN, self::PAGE_HEIGHT - 66, $payload['title'], 13, true, [255, 255, 255]);
        $this->text(340, self::PAGE_HEIGHT - 44, 'Empresa', 8, false, [202, 213, 225]);
        $this->text(340, self::PAGE_HEIGHT - 58, $payload['empresa'] ?? '', 10, true, [255, 255, 255]);

        $this->y = self::PAGE_HEIGHT - 118;
        $this->text(self::MARGIN, $this->y, $payload['subtitle'] ?? '', 10, false, [71, 85, 105]);
        $this->y -= 20;
    }

    private function filters(array $filters): void
    {
        $this->sectionTitle('Filtros aplicados');
        $x = self::MARGIN;

        foreach ($filters as $filter) {
            $label = $filter['label'].': '.$filter['value'];
            $width = min(180, max(92, strlen($this->normalize($label)) * 4.4 + 18));

            if ($x + $width > self::PAGE_WIDTH - self::MARGIN) {
                $x = self::MARGIN;
                $this->y -= 26;
            }

            $this->rect($x, $this->y - 14, $width, 20, [232, 240, 247]);
            $this->text($x + 8, $this->y - 8, $label, 8, false, [10, 61, 98]);
            $x += $width + 8;
        }

        $this->y -= 34;
    }

    private function summary(array $summary): void
    {
        $this->sectionTitle('Resumo de indicadores');
        $columns = 4;
        $gap = 10;
        $width = (self::PAGE_WIDTH - (self::MARGIN * 2) - ($gap * ($columns - 1))) / $columns;
        $height = 48;

        foreach (array_values($summary) as $index => $item) {
            if ($index > 0 && $index % $columns === 0) {
                $this->y -= $height + 10;
            }

            $x = self::MARGIN + (($index % $columns) * ($width + $gap));
            $this->rect($x, $this->y - $height, $width, $height, [248, 250, 252], [226, 232, 240]);
            $this->text($x + 10, $this->y - 17, $item['label'], 8, true, [10, 61, 98]);
            $this->text($x + 10, $this->y - 36, $item['value'], 14, true, [30, 95, 138]);
        }

        $this->y -= $height + 26;
    }

    private function analysis(array $rows): void
    {
        if (! $rows) {
            return;
        }

        $this->table(
            'Analise Preditiva',
            ['Equipamento', 'Sensor', 'Status', 'Score', 'Tendencia', 'Prev. 2h', 'Prev. 4h', 'Recomendacao'],
            array_slice($rows, 0, 12),
            28
        );
    }

    private function table(string $title, array $headers, array $rows, int $rowHeight): void
    {
        $this->sectionTitle($title);

        if (! $rows) {
            $this->text(self::MARGIN, $this->y, 'Nenhum dado disponivel para este bloco.', 9, false, [100, 116, 139]);
            $this->y -= 24;

            return;
        }

        $width = self::PAGE_WIDTH - (self::MARGIN * 2);
        $columnWidth = $width / max(1, count($headers));

        $this->ensureSpace($rowHeight + 28);
        $this->rect(self::MARGIN, $this->y - 18, $width, 22, [10, 61, 98]);

        foreach ($headers as $index => $header) {
            $this->text(self::MARGIN + ($index * $columnWidth) + 4, $this->y - 10, $header, 7, true, [255, 255, 255]);
        }

        $this->y -= 22;

        foreach (array_slice($rows, 0, 80) as $rowIndex => $row) {
            $this->ensureSpace($rowHeight + 18);
            $fill = $rowIndex % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $this->rect(self::MARGIN, $this->y - $rowHeight + 4, $width, $rowHeight, $fill, [226, 232, 240]);

            foreach ($headers as $index => $header) {
                $value = (string) ($row[$index] ?? '');
                $lines = $this->wrap($value, $columnWidth - 8, 7);
                foreach (array_slice($lines, 0, 3) as $lineIndex => $line) {
                    $this->text(self::MARGIN + ($index * $columnWidth) + 4, $this->y - 8 - ($lineIndex * 8), $line, 7, false, [51, 65, 85]);
                }
            }

            $this->y -= $rowHeight;
        }

        $this->y -= 14;
    }

    private function sectionTitle(string $title): void
    {
        $this->ensureSpace(36);
        $this->text(self::MARGIN, $this->y, $title, 12, true, [10, 61, 98]);
        $this->line(self::MARGIN, $this->y - 7, self::PAGE_WIDTH - self::MARGIN, $this->y - 7, [226, 232, 240]);
        $this->y -= 22;
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->y - $needed < 62) {
            $this->newPage(['title' => '', 'empresa' => '']);
            $this->y = self::PAGE_HEIGHT - 72;
        }
    }

    private function footer(array $payload): void
    {
        $issuedAt = Carbon::now()->format('d/m/Y H:i');
        $this->line(self::MARGIN, 44, self::PAGE_WIDTH - self::MARGIN, 44, [226, 232, 240]);
        $this->text(self::MARGIN, 28, 'Emitido em '.$issuedAt, 8, false, [100, 116, 139]);
        $this->text(self::PAGE_WIDTH - 92, 28, 'Pagina '.$this->pageNumber, 8, false, [100, 116, 139]);
    }

    private function rect(float $x, float $y, float $w, float $h, array $fill, ?array $stroke = null): void
    {
        $this->current[] = $this->color($fill, 'rg').' '.$this->num($x).' '.$this->num($y).' '.$this->num($w).' '.$this->num($h).' re f';

        if ($stroke) {
            $this->current[] = $this->color($stroke, 'RG').' '.$this->num($x).' '.$this->num($y).' '.$this->num($w).' '.$this->num($h).' re S';
        }
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $color): void
    {
        $this->current[] = $this->color($color, 'RG').' '.$this->num($x1).' '.$this->num($y1).' m '.$this->num($x2).' '.$this->num($y2).' l S';
    }

    private function text(float $x, float $y, string $text, int $size, bool $bold = false, array $color = [15, 23, 42]): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->current[] = 'BT '.$this->color($color, 'rg').' /'.$font.' '.$size.' Tf '.$this->num($x).' '.$this->num($y).' Td ('.$this->escape($text).') Tj ET';
    }

    private function wrap(string $text, float $width, int $size): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';
        $limit = max(8, (int) floor($width / max(3.2, $size * .48)));

        foreach ($words as $word) {
            $test = trim($line.' '.$word);
            if (strlen($this->normalize($test)) > $limit && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $test;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }

    private function buildPdf(): string
    {
        $objects = [];
        $pagesKids = [];
        $fontNormalId = 3;
        $fontBoldId = 4;
        $nextId = 5;

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[$fontNormalId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->pages as $stream) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $objects[$contentId] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 '.$fontNormalId.' 0 R /F2 '.$fontBoldId.' 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $pagesKids[] = $pageId.' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pagesKids).'] /Count '.count($pagesKids).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function color(array $color, string $operator): string
    {
        return implode(' ', array_map(fn ($value) => $this->num(((int) $value) / 255), $color)).' '.$operator;
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function escape(string $text): string
    {
        $text = $this->normalize($text);
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);

        return $text;
    }

    private function normalize(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
            if ($converted !== false) {
                return $converted;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }
}
