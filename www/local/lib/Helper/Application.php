<?php

namespace Helper;

class Application
{

    public static function showHead(bool $bXhtmlStyle = true): void
    {
        global $APPLICATION;

        $APPLICATION->ShowHead($bXhtmlStyle);
        $APPLICATION->ShowMeta("og:site_name");
        $APPLICATION->ShowMeta("og:url");
        $APPLICATION->ShowMeta("og:title");
        $APPLICATION->ShowMeta("og:description");
        $APPLICATION->ShowMeta("og:image");
    }

    public static function showTitle(bool $strip_tags = true)
    {
        global $APPLICATION;

        $prefixCfg = cfg('site.title_prefix');
        $prefixStr = is_callable($prefixCfg) ? $prefixCfg() : $prefixCfg;

        $suffixCfg = cfg('site.title_suffix');
        $suffixStr = is_callable($suffixCfg) ? $suffixCfg() : $suffixCfg;

        if ($strip_tags) {
            echo $prefixStr;
        }

        $APPLICATION->ShowTitle($strip_tags);

        if ($strip_tags) {
            echo $suffixStr;
        }
    }

    public static function getTitle()
    {
        global $APPLICATION;

        return $APPLICATION->GetTitle(false);
    }


    public static function getPageProperty(string $code, bool $strip_tags = false)
    {
        global $APPLICATION;
        $value = $APPLICATION->GetPageProperty($code);

        return $strip_tags ? strip_tags($value) : $value;
    }

    public static function setPageProps(array $props): void
    {
        global $APPLICATION;

        foreach ($props as $key => $value) {

            $finalValue = match (true) {
                is_callable($value) => $value(),
                is_array($value)    => __t($value),
                default             => $value,
            };

            if ($key  == 'title') {
                $APPLICATION->SetTitle($finalValue);
            }

            $APPLICATION->SetPageProperty($key, $finalValue);
        }
    }

    public static function showProp(string $code, ?string $defaultValue = null)
    {
        global $APPLICATION;

        $APPLICATION->ShowProperty($code, $defaultValue);
    }

    public static function setPageTitle(string|array $title, ?array $options = null)
    {
        global $APPLICATION;

        if (is_array($title)) {
            $title = __t($title);
        }

        $APPLICATION->SetTitle($title, $options);
    }

    public static function isMainPage()
    {
        return $GLOBALS['APPLICATION']->GetCurPage(false) === '/';
    }

    public static function config(string $path, mixed $default = null)
    {
        $parts = explode('.', $path);
        $section = array_shift($parts);

        $data = \Bitrix\Main\Config\Configuration::getInstance()->get($section);

        if (!$data) return $default;

        foreach ($parts as $part) {
            if (isset($data[$part])) {
                $data = $data[$part];
            } else {
                return $default;
            }
        }

        return $data;
    }
}
