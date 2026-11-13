<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$sidebar = startSlot('sidebar');
incFile('components/catalog_filter.php');
$sidebar->end();

incFile('components/catalog_sort.php');
incFile('components/catalog_section.php');

?>


<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>