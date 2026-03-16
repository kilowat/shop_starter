<?php
$preset = [

    'preview' => [
        'width' => 200,
        'height' => 200,
        'min_width' => 1024
    ],

    'medium' => [
        'width' => 400,
        'height' => 400,
        'min_width' => 600
    ],

    'original' => [

        'width' => 800,
        'height' => 800,

        'srcset' => [
            'width' => 200,
            'height' => 200
        ]

    ]

];

$imageData = Lib\Image\ImageHelper::get($fileId, $preset);
