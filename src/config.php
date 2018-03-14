<?php

return [

    /**
     * This is the path where the middleware should temporarily store uploaded files that
     * have been captured.
     *
     * It's best that this location is cleaned of files >= 24 hours old so that it doesn't
     * grow huge over time.
     */
    'temporary_storage_path' => storage_path('app' . DIRECTORY_SEPARATOR . 'tmp-image-uploads')

];