<?php
/**
 * Каталог: HTML-страница + AJAX.
 *
 * AJAX (POST + ajax_action) обслуживается до header.php: подключаем ядро
 * и отдаём только запрошенный блок — list (HTML списка) или filter (счётчики).
 * Настройки компонентов живут в /local/include/components/.
 */

use Bitrix\Main\Web\Json;

$includeDir = $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$ajaxAction = $isPost && is_string($_POST['ajax_action'] ?? null) ? $_POST['ajax_action'] : '';

// ---------- AJAX: какой блок просят — тот и отдаём ----------
if ($ajaxAction !== '') {
	define('NO_KEEP_STATISTIC', true);
	define('NO_AGENT_CHECK', true);

	// Ядро без шапки и подвала: в ответ попадает только блок.
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

	$data = null;

	if (!check_bitrix_sessid()) {
		http_response_code(403);
	} elseif ($ajaxAction === 'list') {
		// Фильтр и сортировка нужны списку ради данных — их HTML выбрасываем.
		ob_start();
		include $includeDir . 'catalog.filter.php';
		include $includeDir . 'catalog.sort.php';
		ob_end_clean();

		ob_start();
		include $includeDir . 'catalog.section.php';
		$data = ['html' => (string) ob_get_clean()];
	} elseif ($ajaxAction === 'filter') {
		ob_start();
		$catalogFilter = include $includeDir . 'catalog.filter.php';
		ob_end_clean();

		$data = ['result' => array_intersect_key($catalogFilter, ['COUNTS' => 1, 'TOTAL' => 1])];
	} else {
		http_response_code(400);
	}

	header('Content-Type: application/json; charset=utf-8');
	echo Json::encode(
		$data === null ? ['status' => 'error'] : ['status' => 'success', 'data' => $data],
		JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
	);
	die();
}

// ---------- HTML ----------
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

addAssetCss([
	'js/nouislider/nouislider.min.css',
]);

addAssetJs([
	'js/nouislider/nouislider.min.js',
	'js/signa/dist/signa.min.js',
	'js/catalog/catalog.js',
	'js/catalog/filter.js',
	'js/catalog/sort.js',
]);

?>
<div class="container">
	<div class="row g-4">

		<aside class="col-12 col-lg-3">
			<div class="catalog-sidebar">
				<?php include $includeDir . 'catalog.filter.php'; ?>
			</div>
		</aside>

		<div class="col-12 col-lg-9" id="catalog-results">
			<?php include $includeDir . 'catalog.sort.php'; ?>
			<br>
			<div id="catalog-list" data-pagination-list>
				<?php include $includeDir . 'catalog.section.php'; ?>
			</div>
		</div>

	</div>
</div>
<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
