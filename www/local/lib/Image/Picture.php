<?php

namespace Lib\Image;

class Picture
{
    public static function show(
        array $imageArr,
        array $imgAttributes = []
    ): string {


        $src = $imageArr["webp"] ?? $imageArr["src"];

        $attrs = self::buildAttributes(array_merge([
            'src' => $src,
            'width' => $imageArr["width"] ?? null,
            'height' => $imageArr["height"] ?? null,
            'alt' => $image["alt"] ?? '',
        ], $imgAttributes));

        return "<img {$attrs}>";
    }

    public static function showById(
        int $fileId,
        array $config,
        array $attributes = [],
        bool $generateWebp = true
    ): string {
        $image = ImageHelper::get($fileId, ['default' => $config], $generateWebp);

        return self::show($image['resizes']['default'], array_merge(
            ['alt' => $image['alt'] ?? ''],
            $attributes
        ));
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
                htmlspecialchars((string)$value)
            );
        }

        return implode(' ', $result);
    }
}
