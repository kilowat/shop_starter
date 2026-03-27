<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
array_walk_recursive($arResult['ITEMS'], function (&$v, $k) {
	if ($k === 'VALUE') {
		$v = html_entity_decode($v);
	}
});
