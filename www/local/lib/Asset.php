<?php

namespace Lib;

class Asset
{
    public static function path(string $src, bool|string $domainOption = false): string|false
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
