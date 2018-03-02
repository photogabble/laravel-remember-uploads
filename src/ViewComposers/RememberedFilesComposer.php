<?php

namespace Photogabble\LaravelRememberUploads\ViewComposers;

use Illuminate\View\View;
use Photogabble\LaravelRememberUploads\RememberedFile;
use Symfony\Component\HttpFoundation\FileBag;

class RememberedFilesComposer
{

    /**
     * @var \Illuminate\Session\Store
     */
    private $session;

    /**
     * Create a new remembered files composer.
     *
     * @param \Illuminate\Session\Store $store
     */
    public function __construct(\Illuminate\Session\Store $store)
    {
        $this->session = $store;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $fileBag = $this->fileBagFactory($this->session->get('_remembered_files', []));
        $view->with('rememberedFiles', $fileBag);
    }

    /**
     * Construct and fill FileBag from $files array.
     *
     * @param array $files
     * @param FileBag|null $fileBag
     * @return FileBag
     */
    private function fileBagFactory(array $files, FileBag $fileBag = null)
    {
        if (is_null($fileBag)){
            $fileBag = new FileBag();
        }

        /**
         * @var array|RememberedFile $file
         */
        foreach ($files as $key => $file)
        {
            if (is_array($file)){
                $fileBag = $this->fileBagFactory($file, $fileBag);
            } else {
                $fileBag->set($key, $file->toUploadedFile());
            }
        }
        return $fileBag;
    }
}
