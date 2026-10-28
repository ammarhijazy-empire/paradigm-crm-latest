<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Mpdf\Mpdf;

/** Server-side PDFs with mPDF (proper Arabic shaping and RTL). */
class Pdf
{
    public static function make(string $view, array $data, string $locale): string
    {
        $prev = App::getLocale();
        App::setLocale($locale);
        try {
            $html = view($view, $data + ['locale' => $locale])->render();
        } finally {
            App::setLocale($prev);
        }
        $tmp = storage_path('app/mpdf');
        is_dir($tmp) || mkdir($tmp, 0775, true);
        $pdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tmp, 'default_font' => 'dejavusans',
            'autoScriptToLang' => true, 'autoLangToFont' => true, 'autoArabic' => true,
            'margin_left' => 14, 'margin_right' => 14, 'margin_top' => 14, 'margin_bottom' => 16,
        ]);
        $pdf->SetDirectionality($locale === 'ar' ? 'rtl' : 'ltr');
        $pdf->SetTitle($data['title'] ?? 'Paradigm Cargo');
        $pdf->WriteHTML($html);
        return $pdf->Output('', 'S');
    }

    public static function render(string $view, array $data, string $locale, string $filename)
    {
        return response(self::make($view, $data, $locale), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
