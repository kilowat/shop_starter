<?php

namespace App\Helper;

use Bitrix\Main\EventManager;

class PagePropertyFilter
{
    protected static array $filters = [];

    public static function register(array $filters): void
    {
        static::$filters = $filters;

        EventManager::getInstance()->addEventHandler(
            "main",
            "OnEpilog",
            [static::class, 'handle']
        );
    }

    public static function handle(): void
    {
        global $APPLICATION;
        $sSectionName = "";
        $sPath = $_SERVER["DOCUMENT_ROOT"] . $APPLICATION->GetCurDir() . ".section.php";
        if (file_exists($sPath)) {
            include($sPath);
        }
        foreach (static::$filters as $code => $callback) {
            if ($code === 'set_title') {
                $current = $APPLICATION->GetTitle(false) ?? $sSectionName;

                $result = is_callable($callback) ? $callback($current) : $callback;

                if ($result) {
                    $APPLICATION->SetTitle($result);
                }
                continue;
            }

            $current = $APPLICATION->GetPageProperty($code)
                ?: $APPLICATION->GetDirProperty($code);

            $result = is_callable($callback)
                ? $callback($current)
                : $callback;

            $APPLICATION->SetDirProperty($code, $result);
            $APPLICATION->SetPageProperty($code, $result);
        }
    }
}
