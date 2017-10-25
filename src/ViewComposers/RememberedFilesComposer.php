<?php

namespace Photogabble\LaravelRememberUploads\ViewComposers;

use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $fileBag = new FileBag();

        if ($files = $this->session->get('_remembered_files', null)) {
            foreach($files as $key => $file) {
                $fileBag->set($key, new UploadedFile($file['tmpPathName'], $file['originalName'], $file['mimeType'], $file['size']));
            }
        }

        $view->with('rememberedFiles', $fileBag);
    }
}
