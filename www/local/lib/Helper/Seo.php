<?php

namespace Helper;

use Helper\Image;

class Seo
{
    public const OG_SETTINGS = [
        'default' => [
            'title_max'       => 60,
            'description_max' => 160,
            'image' => [
                'width'  => 1200,
                'height' => 630,
            ],
        ],

        'twitter' => [
            'card' => 'summary_large_image',
        ],
    ];

    /**
     * OG из элемента (override)
     */
    public static function fromElement(array $arResult): void
    {
        global $APPLICATION;

        $title = $arResult["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"]
            ?? $arResult["NAME"]
            ?? null;

        $descr = $arResult["IPROPERTY_VALUES"]["ELEMENT_META_DESCRIPTION"]
            ?? $arResult["PREVIEW_TEXT"]
            ?? null;

        $title = self::limit($title, self::OG_SETTINGS['default']['title_max']);
        $descr = self::limit($descr, self::OG_SETTINGS['default']['description_max']);

        if ($title) {
            $APPLICATION->SetPageProperty('og:title', $title);
        }

        if ($descr) {
            $APPLICATION->SetPageProperty('og:description', strip_tags($descr));
        }

        if (!empty($arResult["DETAIL_PICTURE"])) {

            $img = Image::get(
                $arResult["DETAIL_PICTURE"],
                self::OG_SETTINGS['default']['image'],
                false
            );

            if (!empty($img['src'])) {
                $APPLICATION->SetPageProperty(
                    'og:image',
                    self::absolute($img['src'])
                );
            }
        }

        // twitter card
        if (!$APPLICATION->GetPageProperty('twitter:card')) {
            $APPLICATION->SetPageProperty(
                'twitter:card',
                self::OG_SETTINGS['twitter']['card']
            );
        }
    }

    /**
     * Событие: дефолты OG + canonical
     */
    public static function onEpilog(): void
    {
        global $APPLICATION;

        // canonical
        if (!$APPLICATION->GetPageProperty('canonical')) {
            $curPage = explode('?', $APPLICATION->GetCurUri(false))[0];

            if (!empty($_REQUEST['PAGEN_1']) && (int)$_REQUEST['PAGEN_1'] > 1) {
                $curPage .= '?PAGEN_1=' . (int)$_REQUEST['PAGEN_1'];
            }

            $APPLICATION->SetPageProperty(
                'canonical',
                self::absolute($curPage)
            );
        }

        // og:url — всегда равен canonical
        if (!$APPLICATION->GetPageProperty('og:url')) {
            $curPage = explode('?', $APPLICATION->GetCurUri(false))[0];
            
            if (!empty($_REQUEST['PAGEN_1']) && (int)$_REQUEST['PAGEN_1'] > 1) {
                $curPage .= '?PAGEN_1=' . (int)$_REQUEST['PAGEN_1'];
            }

            $APPLICATION->SetPageProperty(
                'og:url',
                self::absolute($curPage)
            );
        }

        // og:site_name
        if (!$APPLICATION->GetPageProperty('og:site_name')) {
            $APPLICATION->SetPageProperty(
                'og:site_name',
                \Bitrix\Main\Config\Option::get('main', 'site_name', $_SERVER['SERVER_NAME'])
            );
        }

        // og:title
        if (!$APPLICATION->GetPageProperty('og:title')) {
            $title = self::limit(
                $APPLICATION->GetTitle(),
                self::OG_SETTINGS['default']['title_max']
            );

            if ($title) {
                $APPLICATION->SetPageProperty('og:title', $title);
            }
        }

        // og:description — фолбэк на keywords если description тоже пустой
        if (!$APPLICATION->GetPageProperty('og:description')) {
            $descr = $APPLICATION->GetPageProperty('description')
                ?: $APPLICATION->GetPageProperty('keywords');

            $descr = self::limit($descr, self::OG_SETTINGS['default']['description_max']);

            if ($descr) {
                $APPLICATION->SetPageProperty(
                    'og:description',
                    strip_tags($descr)
                );
            }
        }

        // twitter card всегда
        if (!$APPLICATION->GetPageProperty('twitter:card')) {
            $APPLICATION->SetPageProperty(
                'twitter:card',
                self::OG_SETTINGS['twitter']['card']
            );
        }
    }

    /**
     * Регистрация события
     */
    public static function register(): void
    {
        \Bitrix\Main\EventManager::getInstance()->addEventHandler(
            'main',
            'OnEpilog',
            [self::class, 'onEpilog']
        );
    }

    /**
     * OG картинка (без webp)
     */
    public static function ogImage(
        int $fileId,
        array|string $settings = 'og.default'
    ): ?string {
        $settings = is_string($settings)
            ? Image::preset($settings)
            : $settings;

        if (!$settings) {
            $settings = self::OG_SETTINGS['default']['image'];
        }

        $img = Image::get($fileId, $settings, false);

        return !empty($img['src'])
            ? self::absolute($img['src'])
            : null;
    }

    protected static function limit(?string $text, int $max): ?string
    {
        if (!$text) return null;

        $text = trim(strip_tags($text));

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 3) . '...';
    }

    protected static function absolute(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return 'https://' . $_SERVER["SERVER_NAME"] . $path;
    }
}
