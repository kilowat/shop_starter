<?php

namespace Helper\Localization;

use Bitrix\Main\Page\Asset;

class Admin
{
    public static function load(): void
    {

        $asset = Asset::getInstance();
        $base  = self::basePath();

        $asset->addCss($base . '/edit.css');
        $asset->addJs($base  . '/edit.js');
    }

    private static function basePath(): string
    {
        return '/local/lib/Helper/Localization';
    }
}
