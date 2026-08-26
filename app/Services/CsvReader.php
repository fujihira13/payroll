<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class CsvReader
{
    /** @return array<int, array<string, string>> */
    public function rows(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('CSVファイルを読み込めませんでした。');
        }

        $encoding = mb_detect_encoding($contents, ['UTF-8', 'SJIS-win', 'CP932'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contents = mb_convert_encoding($contents, 'UTF-8', $encoding);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $header = fgetcsv($stream);
        if (! $header) {
            throw new RuntimeException('CSVの見出し行がありません。');
        }
        $header = array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), $header);

        $rows = [];
        while (($values = fgetcsv($stream)) !== false) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_pad($values, count($header), '');
            $rows[] = array_map('trim', array_combine($header, array_slice($values, 0, count($header))));
        }
        fclose($stream);

        return $rows;
    }
}
