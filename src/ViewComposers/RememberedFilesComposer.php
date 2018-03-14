<?php

namespace Photogabble\LaravelRememberUploads\ViewComposers;

use Illuminate\Session\Store;
use Illuminate\View\View;
use Photogabble\LaravelRememberUploads\RememberedFileBag;

class RememberedFilesComposer
{

    /**
     * @var Store
     */
    private $session;

    /**
     * Create a new remembered files composer.
     *
     * @param Store $store
     */
    public function __construct(Store $store)
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
        /** @var RememberedFileBag $rememberedFiles */
        $rememberedFiles = $this->session->get('_remembered_files', new RememberedFileBag());
        $view->with('rememberedFiles', $rememberedFiles->toFileBag());
    }
}
