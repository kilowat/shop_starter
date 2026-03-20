<?php

namespace Lib;

class Asset
{
    public static function getPath(string $src, bool|string $domainOption = true): string|false
    {
        $timestamp = filemtime($src);
        $versionedPath = '/' . $src . '?v=' . $timestamp;

        if ($domainOption === false) {
            return $versionedPath;
        }

        if ($domainOption === true) {
            $request = \Bitrix\Main\Context::getCurrent()->getRequest();
            $protocol = $request->isHttps() ? 'https://' : 'http://';
            $host = $request->getHttpHost();
            return $protocol . $host . $versionedPath;
        }

        return rtrim($domainOption, '/') . $versionedPath;
    }
}
