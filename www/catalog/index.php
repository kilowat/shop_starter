<?php
/**
 * Страница каталога: весь AJAX обслуживает компонент shop:catalog.
 */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->IncludeComponent(
	"shop:catalog",
	".default",
	[
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => "2",
		"FILTER_NAME" => "arrFilter",
		"PRICE_CODE" => [
			0 => "BASE",
		],
		"PAGE_ELEMENT_COUNT" => "12",
		"CACHE_TIME" => "3600",
		"PAGINATION_TEMPLATE" => "lazy",
		"SORT_FIELDS" => [
			[
				'CODE' => 'default',
				'NAME' => 'По популярности',
				'FIELD' => 'sort',
				'ORDER' => 'asc',
			],
			[
				'CODE' => 'new',
				'NAME' => 'Сначала новые',
				'FIELD' => 'id',
				'ORDER' => 'desc',
			],
			[
				'CODE' => 'price_asc',
				'NAME' => 'Сначала дешевле',
				'FIELD' => 'catalog_PRICE_1',
				'ORDER' => 'asc',
			],
			[
				'CODE' => 'price_desc',
				'NAME' => 'Сначала дороже',
				'FIELD' => 'catalog_PRICE_1',
				'ORDER' => 'desc',
			],
			[
				'CODE' => 'name_asc',
				'NAME' => 'По названию А—Я',
				'FIELD' => 'name',
				'ORDER' => 'asc',
			],
			[
				'CODE' => 'name_desc',
				'NAME' => 'По названию Я—А',
				'FIELD' => 'name',
				'ORDER' => 'desc',
			],
			[
				'CODE' => 'views',
				'NAME' => 'По просмотрам',
				'FIELD' => 'show_counter',
				'ORDER' => 'desc',
			],
		],
		"SORT_DEFAULT" => "default",
		"SORT_PARAM_NAME" => "sort",
		"SORT_TITLE" => "Сортировка",
		"COMPONENT_TEMPLATE" => ".default",
		"CACHE_TYPE" => "A"
	],
	false
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');