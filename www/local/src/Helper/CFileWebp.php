<?php

namespace App\Helper;

use Bitrix\Main\Application;
use Bitrix\Main\File\Image;
use Bitrix\Main\File\Image\Mask;
use Bitrix\Main\File\Image\Rectangle;
use CFile;

/**
 * Наследник CFile с поддержкой ресайза в WebP.
 *
 * Аналог CFile::ResizeImageGet(), но результат сохраняется в webp.
 * Если webp-файл уже существует — ресайз не выполняется.
 * Если ресайз не удался — возвращается результат родительского ResizeImageGet().
 */
class CFileWebp extends CFile
{
	/** Каталог для webp-кэша (внутри upload_dir). */
	protected const WEBP_CACHE_DIR = 'resize_cache_webp';

	/**
	 * Аналог CFile::ResizeImageGet(), но с сохранением в WebP.
	 *
	 * @param array|int  $file       ID файла или массив из CFile::GetFileArray()
	 * @param array      $arSize     ['width' => int, 'height' => int]
	 * @param int        $resizeType BX_RESIZE_IMAGE_*
	 * @param bool       $bInitSizes Совместимость с CFile::ResizeImageGet()
	 * @param array|bool $arFilters  Фильтры (sharpen и т.п.)
	 * @param bool       $bImmediate Совместимость с CFile::ResizeImageGet()
	 * @param int|bool   $jpgQuality Качество (0–100). Для webp используется как quality.
	 *
	 * @return array|false Массив ['src','width','height','size'] или false
	 */
	public static function ResizeImageGetWebp(
		$file,
		$arSize,
		$resizeType = \BX_RESIZE_IMAGE_PROPORTIONAL,
		$bInitSizes = false,
		$arFilters = false,
		$bImmediate = false,
		$jpgQuality = false
	) {
		// Если GD не умеет webp — сразу отдаём результат родителя
		if (!function_exists('imagewebp')) {
			return parent::ResizeImageGet($file, $arSize, $resizeType, $bInitSizes, $arFilters, $bImmediate, $jpgQuality);
		}

		if (!is_array($file) && (int)$file > 0) {
			$file = static::GetFileArray($file);
		}

		if (!is_array($file) || empty($file['FILE_NAME'])) {
			return false;
		}

		// Нормализация типа ресайза — как в родителе
		if ($resizeType !== \BX_RESIZE_IMAGE_EXACT && $resizeType !== \BX_RESIZE_IMAGE_PROPORTIONAL_ALT) {
			$resizeType = \BX_RESIZE_IMAGE_PROPORTIONAL;
		}

		if (!is_array($arSize)) {
			$arSize = [];
		}
		$arSize['width']  = isset($arSize['width'])  ? (int)$arSize['width']  : 0;
		$arSize['height'] = isset($arSize['height']) ? (int)$arSize['height'] : 0;

		$docRoot       = Application::getDocumentRoot();
		$uploadDirName = \COption::GetOptionString('main', 'upload_dir', 'upload');

		// Исходный файл
		$imageFile = '/' . $uploadDirName . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];

		// Если исходник меньше запрошенных размеров — отдаём результат родителя (оригинальный SRC)
		if (
			($arSize['width'] <= 0 || $arSize['width'] >= $file['WIDTH'])
			&& ($arSize['height'] <= 0 || $arSize['height'] >= $file['HEIGHT'])
		) {
			return parent::ResizeImageGet($file, $arSize, $resizeType, $bInitSizes, $arFilters, $bImmediate, $jpgQuality);
		}

		// Проверяем, что исходник — поддерживаемый образ
		$sourceInfo = (new Image($docRoot . $imageFile))->getInfo();
		if ($sourceInfo === null || !$sourceInfo->isSupported()) {
			return parent::ResizeImageGet($file, $arSize, $resizeType, $bInitSizes, $arFilters, $bImmediate, $jpgQuality);
		}

		// Формируем путь кэша. Имя файла — с расширением .webp.
		// Учитываем фильтры и качество — чтобы разные настройки не перетирали друг друга.
		$filtersKey = is_array($arFilters) ? md5(serialize($arFilters)) : '';
		$qualityKey = ($jpgQuality === false) ? '' : (string)(int)$jpgQuality;

		$cacheFileName = preg_replace('/\.[^.]+$/', '', $file['FILE_NAME']) . '.webp';

		$cacheDir = '/' . $uploadDirName . '/' . self::WEBP_CACHE_DIR . '/' . $file['SUBDIR']
			. '/' . $arSize['width'] . '_' . $arSize['height'] . '_' . $resizeType
			. ($filtersKey !== '' ? '_' . $filtersKey : '')
			. ($qualityKey !== '' ? '_q' . $qualityKey : '');

		$cacheImageFile = $cacheDir . '/' . $cacheFileName;

		$io = \CBXVirtualIo::GetInstance();
		$physicalCache = $io->GetPhysicalName($docRoot . $cacheImageFile);

		// Если файл уже существует — не трогаем его
		if (file_exists($physicalCache)) {
			return static::buildWebpResult($physicalCache, $cacheImageFile);
		}

		// Пробуем создать webp
		$result = static::createWebpResize(
			$docRoot . $imageFile,
			$docRoot . $cacheImageFile,
			$arSize,
			(int)$resizeType,
			$jpgQuality,
			$arFilters
		);

		if (!$result) {
			// Не получилось — отдаём битриксовый результат
			return parent::ResizeImageGet($file, $arSize, $resizeType, $bInitSizes, $arFilters, $bImmediate, $jpgQuality);
		}

		// Учёт дискового пространства (как в родителе)
		if ((int)\COption::GetOptionString('main', 'disk_space') > 0) {
			$size = (int)filesize($physicalCache);
			if ($size > 0) {
				\CDiskQuota::updateDiskQuota('file', $size, 'insert');
			}
		}

		return static::buildWebpResult($physicalCache, $cacheImageFile);
	}

	/**
	 * Собирает массив результата в формате CFile::ResizeImageGet().
	 */
	protected static function buildWebpResult(string $physicalFile, string $src): array
	{
		$info = (new Image($physicalFile))->getInfo();

		return [
			'src'    => $src,
			'width'  => $info ? $info->getWidth() : 0,
			'height' => $info ? $info->getHeight() : 0,
			'size'   => (int)filesize($physicalFile),
		];
	}

	/**
	 * Создаёт ресайзнутый webp-файл.
	 *
	 * @return bool true при успехе
	 */
	protected static function createWebpResize(
		string $sourceFile,
		string $destinationFile,
		array $arSize,
		int $resizeType,
		$quality,
		$arFilters
	): bool {
		if (!file_exists($sourceFile)) {
			return false;
		}

		// Готовим каталог
		\CheckDirPath($destinationFile);

		$image = new Image($sourceFile);
		if (!$image->load()) {
			return false;
		}

		$destination = new Rectangle($arSize['width'], $arSize['height']);
		$sourceRect  = clone $image->getDimensions();

		// Если ресайз нужен — делаем. Если размеры совпадают, просто пересохраним в webp.
		if ($sourceRect->resize($destination, $resizeType)) {
			if (!$image->resize($sourceRect, $destination)) {
				$image->clear();
				return false;
			}
		}

		// Фильтры (sharpen и т.п.)
		if (is_array($arFilters)) {
			foreach ($arFilters as $arFilter) {
				if (($arFilter['name'] ?? '') === 'sharpen' && ($arFilter['precision'] ?? 0) > 0) {
					$image->filter(Mask::createSharpen((int)$arFilter['precision']));
				}
			}
		}

		// Определяем качество
		if ($quality === false) {
			$quality = \COption::GetOptionString('main', 'image_resize_quality');
		}
		$quality = (int)$quality;
		if ($quality <= 0 || $quality > 100) {
			$quality = 85;
		}

		// Сохраняем как WebP
		$formatConst = Image::class . '::FORMAT_WEBP';
		if (defined($formatConst)) {
			$saved = $image->saveAs($destinationFile, $quality, constant($formatConst));
		} else {
			// Fallback: сохраняем через GD напрямую
			$saved = static::saveGdAsWebp($image, $destinationFile, $quality);
		}

		$image->clear();

		if (!$saved || !file_exists($destinationFile)) {
			// Если что-то пошло не так — удаляем битый файл
			if (file_exists($destinationFile)) {
				@unlink($destinationFile);
			}
			return false;
		}

		return true;
	}

	/**
	 * Резервное сохранение в webp через GD-ресурс (если FORMAT_WEBP недоступен).
	 */
	protected static function saveGdAsWebp(Image $image, string $destinationFile, int $quality): bool
	{
		if (!method_exists($image, 'getEngine')) {
			return false;
		}

		$engine = $image->getEngine();
		if (!$engine || !method_exists($engine, 'getResource')) {
			return false;
		}

		$resource = $engine->getResource();
		if (!$resource) {
			return false;
		}

		return imagewebp($resource, $destinationFile, $quality);
	}
}
