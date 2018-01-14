<?php

if (! function_exists('oldFile')) {
    /**
     * @param null|string $key
     * @param null|mixed $default
     * @return mixed|\Symfony\Component\HttpFoundation\FileBag|Illuminate\Http\UploadedFile
     */
    function oldFile($key = null, $default = null) {
        /** @var Illuminate\Session\Store $session */
        $session = app('session');

        /** @var \Illuminate\Support\ViewErrorBag $errors */
        $errors = $session->get('errors', new \Illuminate\Support\MessageBag());

        $fileBag = new Symfony\Component\HttpFoundation\FileBag();
        if ($files = $session->get('_remembered_files', null)) {
            foreach($files as $k => $f) {
                if ($errors->has($k)) { continue; }
                $fileBag->set(
                    $k,
                    new Illuminate\Http\UploadedFile(
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
