<?php

namespace Lib\Image;

class ImageHelper
{
    public static function get(
        int $fileId,
        array $config,
        bool $generateWebp = true
    ): array {

        $file = \CFile::GetFileArray($fileId);

        return [

            "id" => $file["ID"],

            "alt" => $file["DESCRIPTION"],

            "description" => $file["DESCRIPTION"],

            "meta" => $file,

            "sizes" => ImageProcessor::build(
                $fileId,
                $config,
                $generateWebp
            )

        ];
    }
}
