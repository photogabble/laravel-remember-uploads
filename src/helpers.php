<?php
if (! function_exists('clearRememberedFiles')) {
    function clearRememberedFiles() {
        /** @var Illuminate\Session\Store $session */
        $session = app('session');
        $session->forget('_remembered_files');
    }
}

if (! function_exists('rememberedFile'))
{
    /**
     * @param null|string $key
     * @param null|mixed $default
     * @return mixed|\Symfony\Component\HttpFoundation\FileBag|Illuminate\Http\UploadedFile
     */
    function rememberedFile($key = null, $default = null) {
        /** @var Illuminate\Session\Store $session */
        $session = app('session');

        /** @var \Illuminate\Support\ViewErrorBag $errors */
        $errors = $session->get('errors', new \Illuminate\Support\MessageBag());

        $fileBag = new Symfony\Component\HttpFoundation\FileBag();
        if ($files = $session->get('_remembered_files', null)) {
            /**
             * @var string $k
             * @var \Photogabble\LaravelRememberUploads\RememberedFile $f
             */
            foreach($files as $k => $f) {
                if ($errors->has($k)) { continue; }
                $fileBag->set(
                    $k,
                    $f->toUploadedFile()
                );
            }
        }

        return is_null($key) ? $fileBag : $fileBag->get($key, $default);
    }
}

if (! function_exists('oldFile')) {
    /**
     * @deprecated
     * @throws Exception
     * @todo remove from version 1.4 onwards
     */
    function oldFile() {
        throw new Exception('The oldFile function has been deprecated in favour of using rememberedFile');
    }
}
