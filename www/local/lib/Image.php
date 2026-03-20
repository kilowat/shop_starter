<?php

namespace Lib;

use CFile;

class Image
{
    public static function get(
        int $fileId,
        array $settings,
        bool $generateWebp = true
    ): array {
        $file = CFile::GetFileArray($fileId);

        if (!$file) {
            return [];
        }

        $resizes = self::build($fileId, $settings, $generateWebp);

        $fileLower = array_combine(
            array_map('strtolower', array_keys($file)),
            array_values($file)
        );

        return [
            ...$fileLower,
            'resizes' => $resizes,
        ];
    }

    private static function build(
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
                $resize["webp"] = self::convertToWebp(
                    $resize["src"],
                    $quality
                );
            }

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

    public static function show(
        array $imageArr,
        array $imgAttributes = []
    ): string {

        $src = $imageArr["webp"] ?? $imageArr["src"];

        $attrs = self::buildAttributes(array_merge([
            'src' => $src,
            'width' => $imageArr["width"] ?? null,
            'height' => $imageArr["height"] ?? null,
            'alt' => $imageArr["alt"] ?? '',
        ], $imgAttributes));

        return "<img {$attrs}>";
    }

    public static function showById(
        int $fileId,
        array $config,
        array $attributes = [],
        bool $generateWebp = true
    ): string {

        $image = self::get(
            $fileId,
            ['default' => $config],
            $generateWebp
        );

        return self::show(
            $image['resizes']['default'] ?? [],
            array_merge(
                ['alt' => $image['alt'] ?? ''],
                $attributes
            )
        );
    }

    private static function buildAttributes(array $attributes): string
    {
        $result = [];

        foreach ($attributes as $key => $value) {

            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $result[] = $key;
                continue;
            }

            $result[] = sprintf(
                '%s="%s"',
                $key,
                htmlspecialchars((string) $value)
            );
        }

        return implode(' ', $result);
    }
}
