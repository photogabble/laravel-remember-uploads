<?php

namespace Photogabble\LaravelRememberUploads;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Photogabble\LaravelRememberUploads\Middleware\RememberFileUploads;

class RememberUploadsServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        View::composer(
            '*',
            'Photogabble\LaravelRememberUploads\ViewComposers\RememberedFilesComposer'
        );

        $this->publishes([
            __DIR__.DIRECTORY_SEPARATOR.'config.php' => config_path('remember-uploads.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.DIRECTORY_SEPARATOR.'config.php', 'remember-uploads'
        );

        /** @var Router $router */
        $router =$this->app->make(Router::class);
        $router->aliasMiddleware('remember.files', RememberFileUploads::class);
    }
}
