<?php

namespace App\Services;

use LaraZeus\QrCode\Facades\QrCode;

class QrCodeBrandingService
{
    public const LOGO_RELATIVE_PATH = 'images/logo-poliwangi.png';

    private const SIZE = 300;

    private const MARGIN = 3;

    /** Persentase area QR yang ditempati kotak logo (termasuk padding putih). */
    private const LOGO_BOX_RATIO = 0.22;

    public function generateBrandedSvg(string $content): string
    {
        $svg = (string) QrCode::format('svg')
            ->size(self::SIZE)
            ->margin(self::MARGIN)
            ->errorCorrection('H')
            ->generate($content);

        return $this->embedLogo($svg);
    }

    public function writeBrandedSvg(string $content, string $absolutePath): void
    {
        file_put_contents($absolutePath, $this->generateBrandedSvg($content));
    }

    private function embedLogo(string $svg): string
    {
        $logoPath = public_path(self::LOGO_RELATIVE_PATH);

        if (! is_file($logoPath)) {
            return $svg;
        }

        $size = self::SIZE;
        $boxSize = (int) round($size * self::LOGO_BOX_RATIO);
        $padding = (int) round($boxSize * 0.1);
        $logoSize = $boxSize - ($padding * 2);
        $boxX = (int) (($size - $boxSize) / 2);
        $boxY = (int) (($size - $boxSize) / 2);
        $logoX = $boxX + $padding;
        $logoY = $boxY + $padding;
        $radius = (int) round($boxSize * 0.12);

        $base64 = base64_encode((string) file_get_contents($logoPath));

        $overlay = sprintf(
            '<g id="brand-logo"><rect x="%d" y="%d" width="%d" height="%d" rx="%d" fill="#ffffff"/>'
            .'<image href="data:image/png;base64,%s" x="%d" y="%d" width="%d" height="%d" preserveAspectRatio="xMidYMid meet"/></g>',
            $boxX,
            $boxY,
            $boxSize,
            $boxSize,
            $radius,
            $base64,
            $logoX,
            $logoY,
            $logoSize,
            $logoSize,
        );

        return str_replace('</svg>', $overlay.'</svg>', $svg);
    }
}
