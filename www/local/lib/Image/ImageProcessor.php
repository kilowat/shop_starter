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

        if (!$file)
            return [];

        $result = [];

        foreach ($config as $name => $settings) {

            $resize = null;

            if (isset($settings['width'])) {

                $resize = CFile::ResizeImageGet(
                    $fileId,
                    [
                        'width' => $settings['width'],
                        'height' => $settings['height']
                    ],
                    BX_RESIZE_IMAGE_PROPORTIONAL,
                    true
                );
            }

            if (!$resize) {

                $resize = [
                    "src" => $file["SRC"],
                    "width" => $file["WIDTH"],
                    "height" => $file["HEIGHT"]
                ];
            }

            /**
             * WebP generation
             */

            if (
                $generateWebp
                && !empty($settings["webp"])
            ) {

                $resize["webp"] =
                    self::convertToWebp($resize["src"]);
            }

            $result[$name] = $resize;
        }

        return $result;
    }

    private static function convertToWebp(string $src): string
    {

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

            imagewebp($img, $webpPath, 85);

            unset($img);
        }

        return str_replace(
            $_SERVER["DOCUMENT_ROOT"],
            "",
            $webpPath
        );
    }
}
