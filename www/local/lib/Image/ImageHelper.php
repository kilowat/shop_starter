<?php

namespace Lib\Image;

class ImageHelper
{
    public static function get(
        int $fileId,
        array $settings,
        bool $generateWebp = true
    ): array {
        $file = \CFile::GetFileArray($fileId);
        if (!$file) {
            return [];
        }

        $resizes = ImageProcessor::build(
            $fileId,
            $settings,
            $generateWebp
        );

        $fileLower = array_combine(
            array_map('strtolower', array_keys($file)),
            array_values($file)
        );

        return [
            ...$fileLower,
            'resizes' => $resizes,
        ];
    }
}
