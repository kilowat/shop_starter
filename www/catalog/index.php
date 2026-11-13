<?php

if (($_GET['ajax'] ?? '') === 'y') {
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
	incFile('components/catalog_filter.php');
	die();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$sidebar = startSlot('sidebar');
incFile('components/catalog_filter.php');
$sidebar->end();

incFile('components/catalog_sort.php');
incFile('components/catalog_section.php');
?><?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>