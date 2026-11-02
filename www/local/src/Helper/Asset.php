<?php

namespace App\Helper;

use Bitrix\Main\Page\Asset as BitrixAsset;
use App\Helper\Image;

class Asset
{
    private static array $resolvedPaths = [];
    private static array $srcCache     = [];
    private static array $imgCache     = [];

    public static function resolvePath(string $src): string
    {
        if (isset(self::$resolvedPaths[$src])) {
            return self::$resolvedPaths[$src];
        }

        /** @var \CMain $APPLICATION */
        global $APPLICATION;

        $templatePath = $APPLICATION->GetTemplatePath('');

        return self::$resolvedPaths[$src] = $templatePath . ltrim($src, '/');
    }

    public static function protocolPrefix(string $path)
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $protocol = $request->isHttps() ? 'https://' : 'http://';
        return $protocol . $path;
    }

    public static function domainPrefix(string $path, string $domain = ''): string
    {
        if (!$domain) {
            $domain = self::protocolPrefix($path);
        }

        return rtrim($domain, '/') . '/' . ltrim($path, '/');
    }

    public static function src(string $src, bool|string $domainOption = false): string|false
    {
        // Проверяем, является ли ссылка SVG-спрайтом
        if (strpos($src, '#') !== false) {
            $parts = explode('#', $src, 2);
            $svgPath = $parts[0];

            $relativePath = self::resolvePath($svgPath);
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

            if (!file_exists($absolutePath)) {
                return false;
            }

            $finalPath = $relativePath . '?v=' . filemtime($absolutePath) . '#' . $parts[1];

            self::$srcCache[$src] = $finalPath;

            if ($domainOption === false) {
                return $finalPath;
            }

            return self::domainPrefix(
                $finalPath,
                is_string($domainOption) ? $domainOption : ''
            );
        }

        if (isset(self::$srcCache[$src])) {
            $finalPath = self::$srcCache[$src];
        } else {
            $relativePath = self::resolvePath($src);
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

            if (!file_exists($absolutePath)) {
                return self::$srcCache[$src] = false;
            }

            $finalPath = $relativePath . '?v=' . filemtime($absolutePath);

            self::$srcCache[$src] = $finalPath;
        }

        if ($finalPath === false || $domainOption === false) {
            return $finalPath;
        }

        return self::domainPrefix(
            $finalPath,
            is_string($domainOption) ? $domainOption : ''
        );
    }

    /**
     * Публикует изображение шаблона с конвертацией в webp.
     * Image конвертирует в память → Asset пишет финальный файл.
     * Если webp невозможен — публикует оригинал.
     */
    public static function img(string $src, ?int $quality = null, bool|string $domain = false): string|false
    {
        $quality ??= Image::quality();
        $useWebp = Image::webp();

        $cacheKey = $src . '|' . $quality . '|' . (int)$useWebp;

        if (isset(self::$imgCache[$cacheKey])) {
            $finalPath = self::$imgCache[$cacheKey];
        } else {
            $relativePath = self::resolvePath($src);
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

            if (!file_exists($absolutePath)) {
                $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $src;
                $relativePath = $src;
            }
            if (!file_exists($absolutePath)) {

                return self::$imgCache[$cacheKey] = false;
            }

            $webFile = $useWebp
                ? Image::toWebpFile($relativePath, $quality, [])
                : false;

            $finalPath = $webFile ?: $relativePath;

            self::$imgCache[$cacheKey] = $finalPath;
        }

        if ($domain === false) {
            return $finalPath;
        }

        return self::domainPrefix(
            $finalPath,
            is_string($domain) ? $domain : ''
        );
    }

    public static function addJs(array|string $paths): void
    {
        $asset = BitrixAsset::getInstance();

        foreach ((array)$paths as $path) {
            $asset->addJs(self::resolvePath($path));
        }
    }

    public static function addCss(array|string $paths): void
    {
        $asset = BitrixAsset::getInstance();

        foreach ((array)$paths as $path) {
            $asset->addCss(self::resolvePath($path));
        }
    }
}
