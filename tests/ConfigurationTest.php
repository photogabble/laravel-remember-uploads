<?php

namespace Photogabble\LaravelRememberUploads\Tests;

use Illuminate\Contracts\View\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\Store;
use Illuminate\View\View;
use Orchestra\Testbench\TestCase;
use Photogabble\LaravelRememberUploads\RememberedFileBag;
use Photogabble\LaravelRememberUploads\RememberUploadsServiceProvider;
use Photogabble\LaravelRememberUploads\ViewComposers\RememberedFilesComposer;
use Symfony\Component\HttpFoundation\FileBag;

class ConfigurationTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [RememberUploadsServiceProvider::class];
    }

    public function testConfig()
    {
        $config = config('remember-uploads');

        $this->assertCount(1, $config);
        $this->assertArrayHasKey('temporary_storage_path', $config);
    }

}