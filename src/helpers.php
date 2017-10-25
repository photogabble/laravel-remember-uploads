<?php

if (! function_exists('oldFile')) {
    /**
     * @param null|string $key
     * @param null|mixed $default
     * @return mixed|\Symfony\Component\HttpFoundation\FileBag|Symfony\Component\HttpFoundation\File\UploadedFile
     */
    function oldFile($key = null, $default = null) {
        /** @var Illuminate\Session\Store $session */
        $session = app('session');
        $fileBag = new Symfony\Component\HttpFoundation\FileBag();
        if ($files = $session->get('_remembered_files', null)) {
            foreach($files as $k => $f) {
                $fileBag->set(
                    $k,
                    new Symfony\Component\HttpFoundation\File\UploadedFile(
                        $f['tmpPathName'],
                        $f['originalName'],
                        $f['mimeType'],
                        $f['size']
                    )
                );
            }
        }

        return is_null($key) ? $fileBag : $fileBag->get($key, $default);
    }
}
