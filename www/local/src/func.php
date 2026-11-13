<?php

use App\Helper\AjaxHtmlResponse;
use App\Helper\Application;
use App\Helper\Asset;
use App\Helper\Image;
use App\Helper\Slot;
use App\Helper\File;
use App\Helper\Debug;
use App\Helper\DataStorage;

/**
 * Регистрация контроллера в роуте  в любом неймспейсе
 */
function resolveController(string $class, string $action): array
{
    static $map = [];
    static $registered = false;

    $class = ltrim($class, '\\');
    $alias = str_replace('\\', '_', $class);

    if (isset($map[$alias]) && $map[$alias] !== $class) {
        throw new LogicException("Коллизия алиаса {$alias}: {$map[$alias]} и {$class}");
    }
    $map[$alias] = $class;

    if (!$registered) {
        $registered = true;
        spl_autoload_register(static function (string $name) use (&$map): void {
            if (isset($map[$name])) {
                class_alias($map[$name], $name);
            }
        });
    }

    return [$alias, $action];
}

function routeUrl(string $name, $parameters = []): string
{
    return \Bitrix\Main\Application::getInstance()->getRouter()->route($name, $parameters);
}
/**
 * Получить инстанс сервиса по коду из сервис локатора
 */
function __sl(string $name)
{
    return \Bitrix\Main\DI\ServiceLocator::getInstance()->get($name);
}

/**
 * Ресайз изображения с конвертацией в WebP.
 * Аналог CFile::ResizeImageGet().
 */
function cFileWebp(
    array|int $file,
    array $size,
    int $resizeType = BX_RESIZE_IMAGE_PROPORTIONAL,
    bool $initSizes = false,
    array|bool $filters = false,
    bool $immediate = false,
    int|bool $quality = false
): array|false {
    return App\Helper\CFileWebp::ResizeImageGetWebp(
        $file,
        $size,
        $resizeType,
        $initSizes,
        $filters,
        $immediate,
        $quality
    );
}

/**
 * Получить путь к ассету с версией
 */
function srcAsset(string $src, bool|string $domain = false): string|false
{
    return Asset::src($src, $domain);
}

/**
 * Получить сконвертированое  в wapp статическое изображение
 */
function imgAsset(string $src, ?int $quality = null, bool|string $domain = false, ): string|false
{
    return Asset::img($src, $quality, $domain);
}
/**
 * Добавить к пути текущий протокол http или https 
 */
function protocolPrefix(string $path)
{
    return Asset::protocolPrefix($path);
}

/**
 * Добавить к пути текущий протолк+домен, второй аргмент построить пут на другой домен
 */
function domainPrefix(string $path, string $domain = '')
{
    return Asset::domainPrefix($path, $domain);
}

/**
 * Добавить JS
 */
function addAssetJs(array|string $paths): void
{
    Asset::addJs($paths);
}

/**
 * Добавить CSS
 */
function addAssetCss(array|string $paths): void
{
    Asset::addCss($paths);
}

/**
 * Получить изображение с ресайзом (src, webp, width, height, _src + поля файла)
 *
 * @param int|string   $id       ID файла
 * @param array|string $settings Пресет из image.presets (можно 'news.list') или массив настроек
 * @param bool|null    $webp     true/false — вкл/выкл webp, null — из конфига
 *
 * Ключи $settings:
 *   width, height  int    Размер в px (по умолчанию размер оригинала)
 *   resize_type    int    0 — вписать (ALT), 1 — вписать без обрезки (по умолчанию),
 *                         2 — точный размер с обрезкой (cover)
 *   quality        int    1–100 (по умолчанию из конфига)
 *   filters        array  [['name' => 'sharpen', 'precision' => 15]]
 *
 * Пример: imgArray($id, ['width' => 400, 'height' => 400, 'resize_type' => 2]);
 */
function imgArray(int|string $id, array|string $settings = [], ?bool $webp = null): array
{
    return Image::get($id, $settings, $webp);
}

/**
 * Получить <img>
 *
 * @param array|string|int $img      ID файла или готовый массив из imgArray()
 * @param array|string     $settings Пресет или массив настроек (см. imgArray)
 * @param array            $attrs    HTML-атрибуты: alt, class, loading и т.д.
 * @param bool|null        $webp     true/false — вкл/выкл webp
 *
 * Пример: echo imgTag($id, 'preview', attrs: ['alt' => 'Фото', 'loading' => 'lazy']);
 */
function imgTag(array|string|int $img, array|string $settings = [], array $attrs = [], ?bool $webp = null): string
{
    if (is_array($img))
        return Image::getTag($img, $attrs);

    return Image::getTagById($img, $settings, $attrs, $webp);
}

/**
 * Перевести по коду
 */
function __t(string|array|null $value = null, string|array|null $options = null, bool $strip_tag = false, ): mixed
{
    $loc = \Bitrix\Main\DI\ServiceLocator::getInstance()->get(App\Helper\Localization::class);

    return $loc($value, $options, $strip_tag);
}

/**
 * Код текущего языка
 */
function siteLang()
{
    if (\Bitrix\Main\DI\ServiceLocator::getInstance()->has(App\Helper\Localization::class))
        strtolower(__t()->getLang());
    return 'ru';
}

/**
 * Обьявить слот
 */
function startSlot(string $target)
{
    return Slot::start($target);
}

/**
 * Проставить свойства страницы
 */
function setPageProps(array $props)
{
    Application::setPageProps($props);
}

/**
 * Вывести head (мета + css/js)
 */
function showAssetHead(bool $xhtml = true): void
{
    Application::showHead($xhtml);
}

/**
 * Показать prefix+title, префикс из настроек .settings_extra.php -> site.title_prefix
 */
function showTitle(bool $strip_tags = true)
{
    Application::showTitle($strip_tags);
}

/**
 * Отложеный рендер свойства
 */
function showProp(string $code, ?string $defaultValue = null)
{
    Application::showProp($code, $defaultValue);
}
/**
 * Проставить свойства страницы, если передать массив то будет динамиический перевод например 
setPageTitle([
  'RU' => 'Регатты',
  'EN' => 'Reggats',
])
 */
function setPageTitle(string|array $title)
{
    Application::setPageTitle($title);
}

function isMainPage()
{
    return Application::isMainPage();
}

/**
 * Подключает файл из /local/include/ с учетом языкового префикса
 */
function incFile(string $file, array $params = [], bool $editable = false): void
{
    File::include($file, $params, $editable);
}
/**
 * Конфиг из settings.php / settgins_extra.php, пример 'site.domain'
 */
function cfg(string $path, mixed $default = null)
{
    return Application::config($path, $default);
}

/**
 * Дампим переменную
 * Чтобы включить в браузере ?debug_on=y / ?debug_off=y
 */
function __dvar(mixed $data, bool $die = false)
{
    Debug::dump($data, $die);
}
/**
 * Дампим переменную и стопаем выполнение
 * Чтобы включить в браузере ?debug_on=y / ?debug_off=y
 */
function __dd(mixed $data, bool $die = true)
{
    Debug::dump($data, $die);
}

/**
 * Обмен между компонентами https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2806
 * Если передано 2 аргумента — записывает, если 1 — читает.
 */
function varStore(string $key, mixed $value = null): mixed
{
    if (func_num_args() > 1) {
        DataStorage::set($key, $value);
        return null;
    }
    return DataStorage::get($key);
}
