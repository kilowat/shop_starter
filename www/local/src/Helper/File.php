<?php

namespace App\Helper;

class File
{
    public static function include(
        string $file,
        array $params = [],
        bool $editable = true
    ): void {
        global $APPLICATION;

        $base = $_SERVER['DOCUMENT_ROOT'] . '/local/include/';
        $lang = siteLang();

        $info = pathinfo($file);

        $dirname   = $info['dirname'] ?? '';
        $filename  = $info['filename'] ?? '';
        $extension = isset($info['extension'])
            ? '.' . $info['extension']
            : '';

        $prefix = $dirname && $dirname !== '.'
            ? $dirname . '/'
            : '';

        $candidates = [
            $prefix . $filename . '_' . $lang . $extension,
            $file,
        ];

        $path = null;

        foreach ($candidates as $candidate) {
            $full = $base . $candidate;

            if (is_file($full)) {
                $path = '/local/include/' . $candidate;
                break;
            }
        }

        if (!$path) {
            return;
        }

        $APPLICATION->IncludeComponent(
            'bitrix:main.include',
            '',
            [
                'AREA_FILE_SHOW' => 'file',
                'PATH'           => $path,
                'PARAMS'         => $params,
            ],
            false,
            [
                'HIDE_ICONS' => $editable ? 'N' : 'Y',
            ]
        );
    }
}
