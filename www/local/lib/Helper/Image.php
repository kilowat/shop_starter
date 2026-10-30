<?php

namespace Helper;

use Bitrix\Main\Config\Configuration;
use CFile;

final class Image
{
    private const WEBP_DIR = '/upload/webp';

    private static array $fileCache = [];
    private static array $webpCache = [];

    /* ==========================================================
     * CONFIG
     * ========================================================== */

    private static function config(): array
    {
        return (array)(Configuration::getInstance()->get('image') ?? []);
    }

    private static function settings(): array
    {
        return (array)(self::config()['settings'] ?? []);
    }

    public static function quality(): int
    {
        return (int)(self::settings()['quality'] ?? 80);
    }

    public static function webp(): bool
    {
        return (bool)(self::settings()['webp'] ?? true);
    }

    public static function maxSize(): int
    {
        return (int)(self::settings()['max_size'] ?? 5 * 1024 * 1024);
    }

    /* ==========================================================
     * PRESETS
     * ========================================================== */

    public static function preset(string $key): array
    {
        $presets = self::config()['presets'] ?? [];

        foreach (explode('.', $key) as $part) {
            if (!is_array($presets) || !array_key_exists($part, $presets)) {
                return [];
            }

            $presets = $presets[$part];
        }

        return is_array($presets) ? $presets : [];
    }

    private static function resolve(array|string $settings): array
    {
        return is_string($settings)
            ? self::preset($settings)
            : $settings;
    }

    /* ==========================================================
     * PUBLIC API
     * ========================================================== */

    public static function get(
        int $fileId,
        array|string $settings,
        ?bool $generateWebp = null
    ): array {
        $generateWebp ??= self::webp();

        $file = self::file($fileId);
 
        if (!$file) {
            return [];
        }
 
        $settings = self::resolve($settings);

        $resize = self::build($file, $settings, $generateWebp);
        $resize['_src'] = $file['SRC'];
        $resize['src'] = $resize['webp'] ?  $resize['webp'] :  $resize['src'];

        return [
            ...array_change_key_case($file, CASE_LOWER),
            ...$resize,
        ];
    }

    public static function getTag(array $image, array $attrs = []): string
    {

        $src = self::webp() ? $image['webp'] : $image['src'];

        $attrs = self::attrs(array_merge([
            'src'    => $src,
            'width'  => $image['width'] ?? null,
            'height' => $image['height'] ?? null,
        ], $attrs));

        return "<img {$attrs}>";
    }

    public static function getPicture(array $image, array $attrs = []): string
    {
        $imgTag = self::getTag($image, $attrs);

        if (empty($image['webp']) || $image['webp'] === $image['src']) {
            return $imgTag;
        }

        $source = self::attrs([
            'srcset' => $image['webp'],
            'type'   => 'image/webp',
        ]);

        return "<picture><source {$source}>{$imgTag}</picture>";
    }

    public static function getTagById(
        int $fileId,
        array|string $settings,
        array $attrs = [],
        ?bool $generateWebp = null
    ): string {
        return self::getTag(
            self::get($fileId, $settings, $generateWebp),
            $attrs
        );
    }

    public static function getPictureById(
        int $fileId,
        array|string $settings,
        array $attrs = [],
        ?bool $generateWebp = null
    ): string {
        return self::getPicture(
            self::get($fileId, $settings, $generateWebp),
            $attrs
        );
    }

    /* ==========================================================
     * BUILD
     * ========================================================== */

    private static function build(
        array $file,
        array $settings,
        bool $generateWebp
    ): array {
        $quality = (int)($settings['quality'] ?? self::quality());
    
        $resize = CFile::ResizeImageGet(
            $file['ID'],
            [
                'width'  => $settings['width'] ?? $file['WIDTH'],
                'height' => $settings['height'] ?? $file['HEIGHT'],
            ],
            self::resizeType($settings['resize_type'] ?? null),
            true,
            $settings['filters'] ?? [],
            false,
            $quality
        );

        if (!$resize) {
            $resize = [
                'src'    => $file['SRC'],
                'width'  => $file['WIDTH'],
                'height' => $file['HEIGHT'],
            ];
        }

        if ($generateWebp) {
            $resize['webp'] = self::toWebpFile(
                $resize['src'],
                $quality,
                $settings
            );
        }
        return $resize;
    }

    /* ==========================================================
     * FILE
     * ========================================================== */

    private static function file(int $id): array|false
    {
        if (!array_key_exists($id, self::$fileCache)) {
            self::$fileCache[$id] = CFile::GetFileArray($id) ?: false;
        }

        return self::$fileCache[$id];
    }

    private static function hash(array $settings, int $quality): string
    {
        return substr(
            hash('xxh3', json_encode([$settings, $quality], JSON_UNESCAPED_UNICODE)),
            0,
            10
        );
    }

    /* ==========================================================
     * WEBP
     * ========================================================== */

    public static function toWebp(
        string $src,
        ?int $quality = null
    ): string|false {
        $quality ??= self::quality();

        $cacheKey = $src . ':' . $quality;

        if (array_key_exists($cacheKey, self::$webpCache)) {
            return self::$webpCache[$cacheKey];
        }

        if (!function_exists('imagewebp')) {
            return self::$webpCache[$cacheKey] = false;
        }

        $path = $_SERVER['DOCUMENT_ROOT'] . $src;

        if (!is_file($path)) {
            return self::$webpCache[$cacheKey] = false;
        }

        if (filesize($path) > self::maxSize()) {
            return self::$webpCache[$cacheKey] = false;
        }

        $info = getimagesize($path);

        if (!$info) {
            return self::$webpCache[$cacheKey] = false;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            default        => null,
        };

        if (!$image) {
            return self::$webpCache[$cacheKey] = false;
        }

        if ($info[2] === IMAGETYPE_PNG) {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'webp_');

        if (!$tmp) {
            imagedestroy($image);
            return self::$webpCache[$cacheKey] = false;
        }

        imagewebp($image, $tmp, $quality);
        imagedestroy($image);

        $data = file_get_contents($tmp);
        @unlink($tmp);

        return self::$webpCache[$cacheKey] = $data ?: false;
    }

    public static function toWebpFile(
        string $src,
        int $quality,
        array $settings
    ): string {
        $hash = self::hash($settings, $quality);

        $relative = preg_replace('#^/upload/#', '', $src);
        $relative = ltrim($relative, '/');
        $base = preg_replace('/\.[^.]+$/', '', $relative);

        $webp = self::WEBP_DIR . '/' . $base . '_' . $hash . '.webp';
        $full = $_SERVER['DOCUMENT_ROOT'] . $webp;

        if (is_file($full)) {
            return $webp;
        }

        $data = self::toWebp($src, $quality);

        if (!$data) {
            return $src;
        }

        $dir = dirname($full);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $src;
        }

        file_put_contents($full, $data, LOCK_EX);

        return is_file($full) ? $webp : $src;
    }

    /* ==========================================================
     * HELPERS
     * ========================================================== */

    private static function attrs(array $attrs): string
    {
        $result = [];

        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $result[] = $value === true
                ? $key
                : sprintf(
                    '%s="%s"',
                    $key,
                    htmlspecialchars((string)$value, ENT_QUOTES)
                );
        }

        return implode(' ', $result);
    }

    private static function resizeType(int|string|null $type): int
    {
        if ($type === null || $type === '') {
            return BX_RESIZE_IMAGE_PROPORTIONAL;
        }

        if (is_string($type)) {
            $type = trim($type);
            $type = ctype_digit($type) ? (int)$type : strtoupper($type);
        }

        return match ($type) {
            'EXACT',
            'BX_RESIZE_IMAGE_EXACT',
            2 => BX_RESIZE_IMAGE_EXACT,

            'PROPORTIONAL_ALT',
            'BX_RESIZE_IMAGE_PROPORTIONAL_ALT',
            0 => BX_RESIZE_IMAGE_PROPORTIONAL_ALT,

            'PROPORTIONAL',
            'BX_RESIZE_IMAGE_PROPORTIONAL',
            1 => BX_RESIZE_IMAGE_PROPORTIONAL,

            default => throw new \InvalidArgumentException("Unknown resize_type: {$type}"),
        };
    }
}
