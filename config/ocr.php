<?php

return [
    'review_threshold' => (float) env('OCR_REVIEW_THRESHOLD', 70),
    'private_disk' => env('OCR_PRIVATE_DISK', 'local'),
];
