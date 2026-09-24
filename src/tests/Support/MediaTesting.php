<?php

namespace Tests\Support;

use GdImage;

/** Raw file bytes for exercising the media pipeline without real uploads. */
final class MediaTesting
{
    public static function pngBytes(int $width = 400, int $height = 300): string
    {
        ob_start();
        imagepng(self::canvas($width, $height));

        return (string) ob_get_clean();
    }

    public static function jpegBytes(int $width = 400, int $height = 300): string
    {
        ob_start();
        imagejpeg(self::canvas($width, $height), null, 90);

        return (string) ob_get_clean();
    }

    public static function gifBytes(int $width = 400, int $height = 300): string
    {
        ob_start();
        imagegif(self::canvas($width, $height));

        return (string) ob_get_clean();
    }

    public static function svgBytes(): string
    {
        return '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">'
            .'<script>alert(1)</script></svg>';
    }

    public static function textBytes(): string
    {
        return "this is not an image, it is plain text pretending to be one\n";
    }

    private static function canvas(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 120, 80, 200));
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), 40, 40, imagecolorallocate($image, 255, 210, 77));

        return $image;
    }
}
