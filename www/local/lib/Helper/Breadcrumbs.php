<?php

declare(strict_types=1);

namespace Helper;

use Bitrix\Iblock\Model\Section;
use Bitrix\Main\Loader;

class Breadcrumbs
{
    /**
     * Используем уже готовый PATH из arResult
     */
    public static function prepare(\CBitrixComponentTemplate $template, ?callable $callback = null): void
    {
        $component = $template->getComponent();
        $arResult = $component->arResult;

        $iblockId = (int)($arResult['IBLOCK_ID'] ?? $component->arParams['IBLOCK_ID'] ?? 0);
        $sectionId = (int)($arResult['IBLOCK_SECTION_ID'] ?? $arResult['ID'] ?? 0);

        if (!$iblockId || !$sectionId) return;

        $path = $arResult['SECTION']['PATH'] ?? [];

        if (empty($path) && Loader::includeModule('iblock')) {
            $nav = \CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME', 'SECTION_PAGE_URL']);
            while ($arNav = $nav->GetNext()) {
                $path[] = $arNav;
            }
        }

        if (empty($path)) return;

        $sectionIds = array_column($path, 'ID');
        $ufData = [];

        $sectionEntity = Section::compileEntityByIblock($iblockId);
        $rsUf = $sectionEntity::getList([
            'select' => ['ID', 'UF_*'],
            'filter' => ['@ID' => $sectionIds]
        ]);
        while ($row = $rsUf->fetch()) {
            $ufData[$row['ID']] = $row;
        }

        $breadcrumbs = [];
        foreach ($path as $section) {
            $id = $section['ID'];
            $fullData = array_merge($section, $ufData[$id] ?? []);

            $item = [
                'ID'   => $id,
                'NAME' => $fullData['NAME'],
                'URL'  => $fullData['SECTION_PAGE_URL'] ?? $fullData['~SECTION_PAGE_URL'],
                'SECTION' => $fullData
            ];

            if ($callback) {
                $item = $callback($item);
            }

            if ($item) {
                $breadcrumbs[] = $item;
            }
        }

        $component->arResult['CACHED_BREADCRUMBS'] = $breadcrumbs;
        $component->setResultCacheKeys(['CACHED_BREADCRUMBS']);
    }

    /**
     * Применяем в component_epilog.php
     */
    public static function apply(\CBitrixComponent $component): void
    {
        global $APPLICATION;

        $breadcrumbs = $component->arResult['CACHED_BREADCRUMBS'] ?? [];
        if (empty($breadcrumbs)) return;

        foreach ($breadcrumbs as $item) {
            $APPLICATION->AddChainItem($item['NAME'], $item['URL']);
        }

        $lastItem = end($breadcrumbs);
        $APPLICATION->SetTitle($lastItem['NAME']);
    }

    /**
     * Кастомный рендер в компонентах, через рендерим через буффер с заменой по плейсходеру
     */
    public static function render(
        array $breadcrumbs,
        string $currentName = '',
        string $currentUrl = ''
    ): string {
        $items = [];

        // 1. Главная
        $items[] = [
            'NAME' => __t('HOME'),
            'URL'  => SITE_DIR,
        ];

        $requestUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); 
        $parts       = array_filter(explode('/', trim($requestUri, '/')));
        $topDir      = reset($parts);
        $sectionFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $topDir . '/.section.php';

        if ($topDir && file_exists($sectionFile)) {
            $sSectionName = null;
            include $sectionFile;

            if (!empty($sSectionName)) {
                $items[] = [
                    'NAME' => __t($sSectionName, strip_tag: true),
                    'URL'  => '/' . $topDir . '/',
                ];
            }
        }

        foreach ($breadcrumbs as $crumb) {
            if (!empty($crumb['NAME'])) {
                $items[] = [
                    'NAME' => $crumb['NAME'],
                    'URL'  => $crumb['URL'] ?? '',
                ];
            }
        }

        if (!empty($currentName)) {
            $last = end($items);
            if ($last['NAME'] !== $currentName) {
                $items[] = [
                    'NAME' => $currentName,
                    'URL'  => $currentUrl,
                ];
            }
        }

        if (empty($items)) return '';

        $itemSize  = count($items);
        $strReturn = '<div class="bread-crumbs" itemscope itemtype="http://schema.org">';
        $strReturn .= '<div class="bread-crumbs__list">';

        foreach ($items as $index => $item) {
            $title  = strip_tags($item['NAME']);
            $link   = $item['URL'];
            $isLast = ($index === $itemSize - 1);

            if ($index > 0) {
                $strReturn .= '<span>/</span>';
            }

            $strReturn .= '<div class="bread-crumbs__item" id="bx_breadcrumb_' . $index . '" itemprop="itemListElement" itemscope itemtype="http://schema.org">';

            if ($link && !$isLast) {
                $strReturn .= '<a href="' . $link . '" title="' . $title . '" itemprop="item">';
                $strReturn .= '<span itemprop="name">' . $title . '</span>';
                $strReturn .= '</a>';
            } else {
                $strReturn .= '<span itemprop="name">' . $title . '</span>';
            }

            $strReturn .= '<meta itemprop="position" content="' . ($index + 1) . '" />';
            $strReturn .= '</div>';
        }

        $strReturn .= '</div></div>';

        return $strReturn;
    }
}
