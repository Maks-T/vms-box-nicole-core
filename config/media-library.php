<?php

declare(strict_types=1);
use Nicole\Box\Core\Models\Media;
use Nicole\Box\Core\Support\Media\NicolePathGenerator;

return [

    'media_model' => Media::class,

    /*
   | Default disk for catalog images.
   */
    'disk_name' => env('MEDIA_DISK', 'public'),

    /*
   | Max file size in bytes.
   */
    'max_file_size' => 1024 * 1024 * 10, // 10MB

    'path_generator' => NicolePathGenerator::class,
];
