<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Iblock\SectionPropertyTable;

/*

	public function renderWebComponent()
	{
		\Bitrix\Main\Page\Asset::getInstance()->addString(
			'<script>window.__CATALOG_SMART_FILTER__ = ' . \Bitrix\Main\Web\Json::encode($this->getData()) . ';</script>'
		);

		echo '<smart-filter ></smart-filter>';
	}

*/

$this->setFrameMode(true); ?>
<script></script>
<smart-filter></smart-filter>