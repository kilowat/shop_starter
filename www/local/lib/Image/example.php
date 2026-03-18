<?php

// ============================================================
// 1. ПРОСТОЕ ИСПОЛЬЗОВАНИЕ — одна картинка с одним размером
// ============================================================

$fetch = CIBlockElement::GetByID(42)->Fetch();

$image = \Lib\Image\ImageHelper::get($fetch['DETAIL_PICTURE'], [
    'main' => [
        'width'  => 800,
        'height' => 600,
    ],
]);

echo \Lib\Image\Picture::show($image['resizes']['main'], [
    'class' => 'img-fluid',
    'alt'   => 'Основное изображение',
]);


// ============================================================
// 2. НЕСКОЛЬКО ПРЕСЕТОВ — превью + полный размер
// ============================================================

$image = \Lib\Image\ImageHelper::get($fetch['PREVIEW_PICTURE'], [
    'preview' => [
        'width'       => 400,
        'height'      => 300,
        'resize_type' => BX_RESIZE_IMAGE_PROPORTIONAL,
        'quality'     => 80,
    ],
    'full' => [
        'width'       => 1200,
        'height'      => 800,
        'resize_type' => BX_RESIZE_IMAGE_EXACT,
        'quality'     => 90,
    ],
]);

// Показываем превью
echo \Lib\Image\Picture::show($image['resizes']['preview'], [
    'class' => 'card-img-top',
    'loading' => 'lazy',
]);

// Показываем полный размер
echo \Lib\Image\Picture::show($image['resizes']['full'], [
    'class' => 'detail-img',
]);


// ============================================================
// 3. ВОДЯНОЙ ЗНАК через filters
// ============================================================

$image = \Lib\Image\ImageHelper::get($fetch['DETAIL_PICTURE'], [
    'watermarked' => [
        'width'       => 1000,
        'height'      => 700,
        'resize_type' => BX_RESIZE_IMAGE_PROPORTIONAL,
        'quality'     => 90,
        'filters'     => [
            'watermark' => [
                'position'   => 'bottomright', // topleft | topcenter | topright | middleleft | middlecenter | middleright | bottomleft | bottomcenter | bottomright
                'file'       => '/local/templates/.default/img/watermark.png',
                'alpha'      => 60, // прозрачность 0-100
            ],
        ],
    ],
]);

echo \Lib\Image\Picture::show($image['resizes']['watermarked'], [
    'class' => 'product-img',
    'alt'   => $fetch['NAME'],
]);


// ============================================================
// 4. ВОДЯНОЙ ЗНАК ТЕКСТОМ через filters
// ============================================================

$image = \Lib\Image\ImageHelper::get($fetch['DETAIL_PICTURE'], [
    'watermarked_text' => [
        'width'       => 1000,
        'height'      => 700,
        'resize_type' => BX_RESIZE_IMAGE_PROPORTIONAL,
        'quality'     => 85,
        'filters'     => [
            'watermark' => [
                'position'   => 'middlecenter',
                'text'       => '© MyCompany',
                'font'       => '/local/templates/.default/fonts/arial.ttf',
                'font_size'  => 24,
                'color'      => '#ffffff',
                'alpha'      => 50,
            ],
        ],
    ],
]);

echo \Lib\Image\Picture::show($image['resizes']['watermarked_text'], [
    'class' => 'detail-img',
]);


// ============================================================
// 5. В ЦИКЛЕ — список элементов инфоблока
// ============================================================

$res = CIBlockElement::GetList(
    ['SORT' => 'ASC'],
    ['IBLOCK_ID' => 5, 'ACTIVE' => 'Y'],
    false,
    ['nPageSize' => 12],
    ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
);

while ($item = $res->Fetch()) {
    $image = \Lib\Image\ImageHelper::get($item['PREVIEW_PICTURE'], [
        'card' => [
            'width'       => 400,
            'height'      => 300,
            'resize_type' => BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
            'quality'     => 85,
        ],
    ]);

    $pic = \Lib\Image\Picture::show($image['resizes']['card'], [
        'class'   => 'card-img',
        'alt'     => htmlspecialchars($item['NAME']),
        'loading' => 'lazy',
    ]);

    echo "<div class='card'>{$pic}<p>{$item['NAME']}</p></div>";
}


// ============================================================
// 6. showById — без предварительного get(), сокращённая запись
// ============================================================

echo \Lib\Image\Picture::showById(
    $fetch['DETAIL_PICTURE'],
    [
        'width'   => 800,
        'height'  => 600,
        'quality' => 90,
    ],
    ['class' => 'img-fluid', 'loading' => 'lazy']
);
