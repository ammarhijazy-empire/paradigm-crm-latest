<?php

namespace App\Ai;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * Turns stored files into something the AI can read: plain text for PDFs,
 * spreadsheets, chats; base64 image parts for images and scanned PDFs.
 */
class TextExtractor
{
    public const MAX_CHARS = 60000;

    /** @return array{text: ?string, image: ?string} image is a data: URL */
    public function extract(string $absPath, string $mime, string $name): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        try {
            if ($ext === 'pdf' || $mime === 'application/pdf') {
                $text = trim((new PdfParser)->parseFile($absPath)->getText());
                if (mb_strlen($text) > 40) {
                    return ['text' => mb_substr($text, 0, self::MAX_CHARS), 'image' => null];
                }
                return ['text' => null, 'image' => 'data:application/pdf;base64,'.base64_encode(file_get_contents($absPath))]; // scanned PDF
            }
            if (in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
                $sheet = IOFactory::load($absPath);
                $out = [];
                foreach ($sheet->getAllSheets() as $ws) {
                    foreach ($ws->toArray(null, true, true, false) as $row) {
                        $line = trim(implode(' | ', array_filter(array_map('strval', $row), fn ($v) => $v !== '')));
                        if ($line !== '') {
                            $out[] = $line;
                        }
                    }
                }
                return ['text' => mb_substr(implode("\n", $out), 0, self::MAX_CHARS), 'image' => null];
            }
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || str_starts_with($mime, 'image/')) {
                return ['text' => null, 'image' => 'data:'.($mime ?: 'image/jpeg').';base64,'.base64_encode(file_get_contents($absPath))];
            }
            if ($ext === 'zip') {
                return ['text' => $this->whatsappZip($absPath), 'image' => null];
            }
            if (in_array($ext, ['txt', 'eml', 'html', 'htm'], true) || str_starts_with($mime, 'text/')) {
                return ['text' => mb_substr(strip_tags((string) file_get_contents($absPath)), 0, self::MAX_CHARS), 'image' => null];
            }
        } catch (\Throwable $e) {
            return ['text' => '[could not read '.$name.': '.$e->getMessage().']', 'image' => null];
        }
        return ['text' => null, 'image' => null];
    }

    /** WhatsApp "export chat" zip: the _chat.txt inside. Audio files are listed but not read here. */
    public function whatsappZip(string $absPath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($absPath) !== true) {
            return '';
        }
        $text = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = $zip->getNameIndex($i);
            if (preg_match('/\.txt$/i', $n)) {
                $text .= $zip->getFromIndex($i)."\n";
            }
        }
        $zip->close();
        return mb_substr($text, 0, self::MAX_CHARS);
    }

    /** Audio files inside a WhatsApp zip, extracted to a temp folder. */
    public function zipAudio(string $absPath): array
    {
        $zip = new ZipArchive;
        $out = [];
        if ($zip->open($absPath) !== true) {
            return $out;
        }
        $dir = sys_get_temp_dir().'/wa-'.uniqid();
        @mkdir($dir);
        for ($i = 0; $i < $zip->numFiles && count($out) < 10; $i++) {
            $n = $zip->getNameIndex($i);
            if (preg_match('/\.(opus|ogg|m4a|mp3|aac|wav)$/i', $n)) {
                $target = $dir.'/'.basename($n);
                file_put_contents($target, $zip->getFromIndex($i));
                $out[] = ['path' => $target, 'name' => basename($n)];
            }
        }
        $zip->close();
        return $out;
    }
}
