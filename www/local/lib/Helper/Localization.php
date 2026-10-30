<?php

namespace Helper;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

class Localization
{
    protected array $data = [];
    /**
     * @property \Bitrix\Main\ORM\Data\DataManager $dataClass
     */
    protected string $dataClass = '';
    private int $hid = 0;

    protected static int $counter = 0;
    protected const CACHE_DIR = '/localization';
    protected const CACHE_TTL = 36000;
    protected const CACHE_TAG = 'hl_localization';

    public function __construct(
        protected array $languages = ['RU'],
        protected string $lang = 'RU',
        protected string $hlBlockCode = 'localization'
    ) {
        $this->languages = array_map('strtoupper', $this->languages);
        $this->lang = strtoupper($this->lang);
        $this->loadIblock();
    }

    /* ---------------------------------
     | STATIC CONSTRUCTORS
     |---------------------------------*/

    public static function fromParam(
        array $allowedLangs = ['RU', 'EN'],
        string $paramName = 'lang',
        string $hlBlockCode = 'localization'
    ): self {
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

        $lang = strtoupper(trim((string)$request->get($paramName)));

        $allowedLangs = array_map('strtoupper', $allowedLangs);

        if ($lang === 'DEBUG') {
            return new self($allowedLangs, 'DEBUG', $hlBlockCode);
        }

        $currentLang = in_array($lang, $allowedLangs, true)
            ? $lang
            : $allowedLangs[0];

        return new self($allowedLangs, $currentLang, $hlBlockCode);
    }

    public static function fromDomain(
        array $allowedLangs = ['RU', 'EN'],
        string $hlBlockCode = 'localization'
    ): self {
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

        $paramLang = strtoupper(trim((string)$request->get('lang')));

        if ($paramLang === 'DEBUG') {
            return new self($allowedLangs, 'DEBUG', $hlBlockCode);
        }

        $allowedLangs = array_map('strtoupper', $allowedLangs);

        if (in_array($paramLang, $allowedLangs, true)) {
            return new self($allowedLangs, $paramLang, $hlBlockCode);
        }

        $host = \Bitrix\Main\Context::getCurrent()
            ->getServer()
            ->getHttpHost();

        $host = explode(':', $host)[0];
        $parts = explode('.', $host);

        foreach ($parts as $part) {
            $part = strtoupper($part);

            if (in_array($part, $allowedLangs, true)) {
                return new self($allowedLangs, $part, $hlBlockCode);
            }
        }

        return new self($allowedLangs, $allowedLangs[0], $hlBlockCode);
    }

    public function getDataClass()
    {
        return $this->dataClass;
    }

    private function isDebug(): bool
    {
        if ($this->lang !== 'DEBUG') {
            return false;
        }

        return \Bitrix\Main\Engine\CurrentUser::get()->isAdmin();
    }

    /* --------------------------------- */

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function is(string $lang): bool
    {
        return $this->lang === strtoupper($lang);
    }

    protected function normalizeLang(?string $lang = null): string
    {
        return strtoupper($lang ?? $this->lang);
    }

    /* ---------------------------------
     | DATA SOURCE
     |---------------------------------*/

    private function loadIblock()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new \RuntimeException('HL module not loaded');
        }

        $hlblock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => $this->hlBlockCode]
        ])->fetch();

        if (!$hlblock) {
            throw new \RuntimeException(
                "HL block not found: {$this->hlBlockCode}"
            );
        }

        $this->hid = $hlblock['ID'];

        $entity = HighloadBlockTable::compileEntity($hlblock);

        $this->dataClass = $entity->getDataClass();
    }

    public function list(?array $codes = null, ?string $lang = null): array
    {
        if ($this->isDebug()) {
            $result = [];
            $data = $this->all();

            if ($codes !== null) {
                $codes = array_map('strtoupper', $codes);
                $data = array_intersect_key($data, array_flip($codes));
            }

            foreach ($data as $code => $_) {
                $result[$code] = "[{$code}]";
            }

            return $result;
        }

        $lang = $this->normalizeLang($lang);

        $result = [];
        $data = $this->all();

        if ($codes !== null) {
            $codes = array_map('strtoupper', $codes);
            $data = array_intersect_key($data, array_flip($codes));
        }

        foreach ($data as $code => $item) {
            $value = $this->resolveItem($item, $lang);

            if ($value === '') {
                $value = $code;
            }

            $result[$code] = $value;
        }

        return $result;
    }

    public function all(): array
    {
        if ($this->data) {
            return $this->data;
        }

        $cache = Cache::createInstance();

        if ($cache->initCache(
            self::CACHE_TTL,
            $this->cacheKey(),
            self::CACHE_DIR
        )) {
            return $this->data = $cache->getVars();
        }

        if ($cache->startDataCache()) {

            $taggedCache = Application::getInstance()->getTaggedCache();

            $taggedCache->startTagCache(self::CACHE_DIR);
            $taggedCache->registerTag(self::CACHE_TAG);

            $rows = $this->dataClass::getList()->fetchAll();

            foreach ($rows as $item) {
                $this->data[$item['UF_CODE']] = $item;
            }

            $taggedCache->endTagCache();
            $cache->endDataCache($this->data);
        }

        return $this->data;
    }

    public function mark(string $code): string
    {
        if (!$this->isDebug()) {
            return '';
        }

        $item = $this->all()[$code] ?? [];

        return $this->getDebugTag($code, $item);
    }

    /**
     * Получить перевод по коду.
     *
     * Второй параметр $options может быть:
     *   - string  — код языка ('EN', 'RU', ...), поведение как раньше
     *   - array   — плейсхолдеры ['#NAME#' => 'Иван'] или ['NAME' => 'Иван']
     *               Паттерн замены: #KEY# -> value
     *
     * @param string           $code      Код строки в HL-блоке
     * @param string|array|null $options  Язык (string) или плейсхолдеры (array)
     * @param bool             $strip_tag Очистить HTML-теги и заэскейпить в режиме дебага, чтобы верстка не ломалась наппример если вызов в двойных ковычка аттрибута тэга
     */
    public function get(string $code, string|array|null $options = null, bool $strip_tag = false): string
    {
        if (empty($code)) return '';

        [$lang, $placeholders] = $this->parseOptions($options);

        $item = $this->all()[$code] ?? null;

        if ($this->isDebug() && !$strip_tag) {
            if ($this->isCyrillic($code)) {
                return $code;
            }
            return $this->getDebugTag($code, $item);
        }

        if (!$item) return $code;

        $result = $this->resolveItem($item, $lang);
        if ($result === '') return $code;

        $clearedResult =  strip_tags($result);

        $result = $this->applyPlaceholders($clearedResult, $placeholders);

        return $result;
    }

    /**
     * Разобрать второй параметр метода get() / __invoke().
     *
     * @return array{0: ?string, 1: array}  [lang, placeholders]
     */
    protected function parseOptions(string|array|null $options): array
    {
        if (is_array($options)) {
            return [null, $options];
        }

        // строка — это язык
        return [$options, []];
    }

    /**
     * Заменить плейсхолдеры #KEY# в строке.
     * Ключи можно передавать как с решётками (#NAME#), так и без (NAME).
     */
    protected function applyPlaceholders(string $text, array $placeholders): string
    {
        $search  = ['#BR#'];
        $replace = ['<br>'];

        foreach ($placeholders as $key => $value) {
            $normalizedKey = strtoupper(trim((string)$key, '#'));
            $search[]  = "#{$normalizedKey}#";
            $replace[] = (string)$value;
        }

        return str_replace($search, $replace, $text);
    }

    protected function isCyrillic(string $value): bool
    {
        return (bool) preg_match('/[\x{0400}-\x{04FF}]/u', $value);
    }

    public function getDebugTag(string $code, $item = [])
    {
        $id = 't-' . $code . '-' . (++self::$counter);

        return sprintf(
            '<t-word id="%s" data-UF_CODE="%s" data-hid="%s" data-itemid="%s">%s</t-word>',
            $id,
            $code,
            $this->hid,
            $item["ID"] ?? '',
            $code
        );
    }

    public function translate(array $map, ?string $lang = null): mixed
    {
        $lang = $this->normalizeLang($lang);

        if (isset($map[$lang])) {
            return $this->value($map[$lang]);
        }

        foreach ($this->languages as $fallback) {
            if (isset($map[$fallback])) {
                return $this->value($map[$fallback]);
            }
        }

        if (!empty($map)) {
            return $this->value(reset($map));
        }

        return '';
    }

    public function dataId(string $code)
    {
        $item = $this->all()[$code] ?? null;
        if (!$item) return '';

        return $this->hid . '-' . $item['ID'];
    }

    public function registerTag()
    {
        Application::getInstance()
            ->getTaggedCache()
            ->registerTag(self::CACHE_TAG);
    }

    protected function resolveItem(array $item, ?string $lang = null): string
    {
        $langCode = $this->normalizeLang($lang);

        $key = "UF_FILE_{$langCode}";
        if (!empty($item[$key])) {
            $path = \CFile::GetPath($item[$key]);
            if (!empty($path)) {
                return $path;
            }
        }

        $key = "UF_NAME_{$langCode}";
        if (isset($item[$key]) && $item[$key] !== '') {
            return $item[$key];
        }

        $key = "UF_VALUE";
        if (isset($item[$key]) && $item[$key] !== '') {
            return $item[$key];
        }

        return $item['UF_CODE'];
    }

    protected function value(mixed $value): mixed
    {
        return $value instanceof \Closure
            ? $value()
            : $value;
    }

    public function __toString()
    {
        return '';
    }

    /**
     * Вызов объекта как функции.
     *
     * Варианты использования:
     *
     *   $loc('CODE')                          — перевод на текущий язык
     *   $loc('CODE', 'EN')                    — перевод на конкретный язык
     *   $loc('CODE', ['#NAME#' => 'Иван'])    — перевод + плейсхолдеры
     *   $loc(['RU' => 'Привет', 'EN' => 'Hi']) — inline-перевод (translate)
     *   $loc(['RU' => '...'], 'EN')            — inline-перевод на конкретный язык
     *   $loc(null)                             — вернуть сам объект
     *
     * @param string|array|null  $value     Код строки, inline-карта переводов или null
     * @param string|array|null  $options   Язык (string) или плейсхолдеры (array)
     * @param bool               $strip_tag Очистить HTML-теги
     */
    public function __invoke(
        string|array|null $value = null,
        string|array|null $options = null,
        bool $strip_tag = false,
    ): mixed {
        if ($value === null) {
            return $this;
        }

        if (empty($value)) return '';

        // Inline-карта переводов: ['RU' => '...', 'EN' => '...']
        // $options здесь может быть только строкой-языком (массив плейсхолдеров
        // к translate() не применяется — нет смысла)
        if (is_array($value)) {
            $lang = is_string($options) ? $options : null;
            return $this->translate($value, $lang);
        }

        return $this->get($value, $options, $strip_tag);
    }

    protected function cacheKey(): string
    {
        return "localization_{$this->hlBlockCode}";
    }

    public function clearCache(): void
    {
        Application::getInstance()
            ->getTaggedCache()
            ->clearByTag(self::CACHE_TAG);
    }

    /* ---------------------------------
     | EVENTS
     |---------------------------------*/

    public static function register(): void
    {
        $instance = ServiceLocator::getInstance()
            ->get(self::class);

        $hlName = $instance->hlBlockCode;

        foreach (['OnAfterAdd', 'OnAfterUpdate', 'OnAfterDelete'] as $event) {
            EventManager::getInstance()
                ->addEventHandler(
                    '',
                    $hlName . $event,
                    static fn() => $instance->clearCache()
                );
        }

        EventManager::getInstance()->addEventHandler(
            'main',
            'OnProlog',
            function () {
                if (
                    !defined('ADMIN_SECTION')
                    && $GLOBALS['USER']->IsAdmin()
                    && !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->isAjaxRequest()
                ) {
                    \Helper\Localization\Admin::load();
                }
            }
        );
    }
}
