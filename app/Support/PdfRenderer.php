<?php

namespace App\Support;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

class PdfRenderer
{
    public function download(string $view, array $data, string $filename): Response
    {
        $html = View::make($view, $data)->render();
        $mpdf = $this->mpdf();
        $mpdf->SetDirectionality(app()->getLocale() === 'ar' ? 'rtl' : 'ltr');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function mpdf(): Mpdf
    {
        $tempDir = storage_path('framework/cache/mpdf');
        File::ensureDirectoryExists($tempDir, 0755);

        if (! is_dir($tempDir) || ! is_writable($tempDir)) {
            throw new RuntimeException('mPDF temp directory is not writable.');
        }

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $fontData['dejavusans']['useOTL'] = 0xFF;
        $fontData['dejavusans']['useKashida'] = 75;

        return new Mpdf([
            'mode' => 'utf-8',
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoArabic' => true,
            'tempDir' => $tempDir,
        ]);
    }
}
