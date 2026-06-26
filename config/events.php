<?php

return [
    'cover_image_disk' => env('EVENT_COVER_IMAGE_DISK', env('FILESYSTEM_DISK', 'local')),
];
