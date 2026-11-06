<?php
/**
 * Подготовка данных компонента под свой шаблон.
 * Без этого вызова компонент не рассчитывает цены, офферы и картинки для шаблона.
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var CatalogSectionComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

