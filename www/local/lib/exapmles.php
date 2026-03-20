<?php
// ============================================================
// \Lib\Image
// ============================================================

$fetch = CIBlockElement::GetByID(42)->Fetch();

$imageRes = \Lib\Image::get($fetch['DETAIL_PICTURE'], [
    'main' => [

        // 🔹 Размеры
        'width' => 800,
        'height' => 600,

        // 🔹 Тип ресайза (битрикс)
        // BX_RESIZE_IMAGE_EXACT
        // BX_RESIZE_IMAGE_PROPORTIONAL
        // BX_RESIZE_IMAGE_PROPORTIONAL_ALT
        'resize_type' => BX_RESIZE_IMAGE_PROPORTIONAL_ALT,

        // 🔹 Качество JPEG/PNG (битрикс)
        'quality' => 85,

        // 🔹 Фильтры (битрикс)
        'filters' => [

            // ===== ВОДЯНОЙ ЗНАК (картинка) =====
            'watermark' => [
                'name' => 'watermark', // обязательно
                'position' => 'bottomright', // позиции:
                // topleft | topcenter | topright
                // middleleft | middlecenter | middleright
                // bottomleft | bottomcenter | bottomright

                'type' => 'image', // image | text
                'file' => '/local/templates/.default/img/watermark.png',
                'alpha' => 60, // прозрачность (0-100)

                // отступы
                'coefficient' => 1, // масштаб (опционально)
            ],

            // ===== ВОДЯНОЙ ЗНАК (текст) =====
            /*
            'watermark' => [
                'name'      => 'watermark',
                'type'      => 'text',
                'text'      => '© MyCompany',
                'position'  => 'middlecenter',

                'font'      => '/local/templates/.default/fonts/arial.ttf',
                'font_size' => 24,

                'color'     => '#ffffff',
                'alpha'     => 50,
            ],
            */

            // ===== МОЖНО ДОБАВИТЬ НЕСКОЛЬКО ФИЛЬТРОВ =====
            /*
            [
                'name' => 'sharpen', // например резкость
            ]
            */

        ],

        // 🔹 Если НЕ указать width/height — возьмутся оригинальные
        // 'width' => $file['WIDTH'],
        // 'height' => $file['HEIGHT'],

        // 🔹 WebP (управляется третьим аргументом get())
        // тут не задаётся — включается через:
        // Helper::get(..., true)

    ],
]);

echo \Lib\Image::show($imageRes['resizes']['main'], [
    'class' => 'img-fluid',
    'alt' => 'Основное изображение',
    'loading' => 'lazy',
]);

// ============================================================
// НЕСКОЛЬКО ПРЕСЕТОВ
// ============================================================

$imageRes = \Lib\Image::get($fetch['PREVIEW_PICTURE'], [
    'preview' => [
        'width' => 400,
        'height' => 300,
        'quality' => 80,
    ],
    'full' => [
        'width' => 1200,
        'height' => 800,
        'quality' => 90,
    ],
]);

echo \Lib\Image::show($imageRes['resizes']['preview'], [
    'class' => 'card-img-top',
    'loading' => 'lazy',
]);

echo \Lib\Image::show($imageRes['resizes']['full'], [
    'class' => 'detail-img',
]);


// ============================================================
//  ВОДЯНОЙ ЗНАК (картинка)
// ============================================================

$imageRes = \Lib\Image::get($fetch['DETAIL_PICTURE'], [
    'watermarked' => [
        'width' => 1000,
        'height' => 700,
        'filters' => [
            'watermark' => [
                'position' => 'bottomright',
                'file' => '/local/templates/.default/img/watermark.png',
                'alpha' => 60,
            ],
        ],
    ],
]);

echo \Lib\Image::show($imageRes['resizes']['watermarked'], [
    'class' => 'product-img',
    'alt' => $fetch['NAME'],
]);


// ============================================================
// ВОДЯНОЙ ЗНАК (текст)
// ============================================================

$imageRes = \Lib\Image::get($fetch['DETAIL_PICTURE'], [
    'watermarked_text' => [
        'width' => 1000,
        'height' => 700,
        'filters' => [
            'watermark' => [
                'position' => 'middlecenter',
                'text' => '© MyCompany',
                'font' => '/local/templates/.default/fonts/arial.ttf',
                'font_size' => 24,
                'color' => '#ffffff',
                'alpha' => 50,
            ],
        ],
    ],
]);

echo \Lib\Image::show($imageRes['resizes']['watermarked_text'], [
    'class' => 'detail-img',
]);


// ============================================================
//  В ЦИКЛЕ
// ============================================================

$res = CIBlockElement::GetList(
    ['SORT' => 'ASC'],
    ['IBLOCK_ID' => 5, 'ACTIVE' => 'Y'],
    false,
    ['nPageSize' => 12],
    ['ID', 'NAME', 'PREVIEW_PICTURE']
);

while ($item = $res->Fetch()) {

    $imageRes = \Lib\Image::get($item['PREVIEW_PICTURE'], [
        'card' => [
            'width' => 400,
            'height' => 300,
        ],
    ]);

    echo "<div class='card'>";

    echo \Lib\Image::show($imageRes['resizes']['card'], [
        'class' => 'card-img',
        'alt' => htmlspecialchars($item['NAME']),
        'loading' => 'lazy',
    ]);

    echo "<p>{$item['NAME']}</p>";

    echo "</div>";
}


// ============================================================
// showById — короткий вариант
// ============================================================

echo \Lib\Image::showById(
    $fetch['DETAIL_PICTURE'],
    [
        'width' => 800,
        'height' => 600,
    ],
    [
        'class' => 'img-fluid',
        'loading' => 'lazy',
    ]
);


// ============================================================
//  Без WebP
// ============================================================

$imageRes = \Lib\Image::get(
    $fetch['DETAIL_PICTURE'],
    [
        'main' => [
            'width' => 800,
            'height' => 600,
        ],
    ],
    false
);

echo \Lib\Image::show($imageRes['resizes']['main']);
