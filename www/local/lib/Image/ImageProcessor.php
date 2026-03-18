<?php

namespace Lib\Image;

use CFile;

class ImageProcessor
{
    public static function build(
        int $fileId,
        array $config,
        bool $generateWebp = true
    ): array {

        $file = CFile::GetFileArray($fileId);

        if (!$file) {
            return [];
        }

        $result = [];

        foreach ($config as $name => $settings) {

            // 🔥 защита от кривых значений
            if (!is_string($name) || !is_array($settings)) {
                continue;
            }

            $resizeParams = [
                'width' => $settings['width'] ?? $file['WIDTH'],
                'height' => $settings['height'] ?? $file['HEIGHT'],
            ];

            $resizeType = $settings['resize_type']
                ?? BX_RESIZE_IMAGE_PROPORTIONAL;

            $filters = $settings['filters'] ?? [];

            $quality = $settings['quality'] ?? 90;

            $resize = CFile::ResizeImageGet(
                $fileId,
                $resizeParams,
                $resizeType,
                true,
                $filters,
                false,
                $quality
            );

            if (!$resize) {
                $resize = [
                    "src" => $file["SRC"],
                    "width" => $file["WIDTH"],
                    "height" => $file["HEIGHT"]
                ];
            }

            // WebP
            if ($generateWebp) {

                $webpQuality = $quality;

                $resize["webp"] = self::convertToWebp(
                    $resize["src"],
                    $webpQuality
                );
            }

            // 🔥 добавляем ТОЛЬКО по имени
            $result[$name] = $resize;
        }

        return $result;
    }

    private static function convertToWebp(
        string $src,
        int $quality = 90
    ): string {

        $path = $_SERVER["DOCUMENT_ROOT"] . $src;

        $webpPath = preg_replace(
            '/\.(jpg|jpeg|png)$/i',
            '.webp',
            $path
        );

        if (!file_exists($webpPath)) {

            $img = imagecreatefromstring(
                file_get_contents($path)
            );

            if (!$img) {
                return $src;
            }

            imagewebp($img, $webpPath, $quality);

            unset($img);
        }

        return str_replace(
            $_SERVER["DOCUMENT_ROOT"],
            "",
            $webpPath
        );
    }
}
