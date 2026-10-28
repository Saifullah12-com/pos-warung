<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function svg(string $text, int $size = 240): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($text);

        // Buang deklarasi XML di baris pertama supaya SVG bisa ditempel langsung di halaman HTML
        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
    }
}
