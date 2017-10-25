<?php

namespace Photogabble\LaravelRememberUploads\Tests;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use \Illuminate\Session\Store;
use Illuminate\View\View;
use Orchestra\Testbench\TestCase;
use Photogabble\LaravelRememberUploads\RememberUploadsServiceProvider;
use Photogabble\LaravelRememberUploads\ViewComposers\RememberedFilesComposer;
use Symfony\Component\HttpFoundation\FileBag;

class UploadTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [RememberUploadsServiceProvider::class];
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        /**
         * @var \Illuminate\Routing\Router $router
         */
        $router = $this->app->make('router');

        $router->post('test', function () {
            return ['ok' => true];
        })->middleware('remember.files');
    }

    /**
     * This tests to see if the middleware correctly captures the uploaded file and that the
     * view composer injects that captured upload into the next page load via flash sessions.
     *
     * It then goes to check that "refreshing" the page without any file upload will clear
     * the captured uploaded file.
     */
    public function testSingleFileUpload()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $stub = __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'test.jpg';
        $name = str_random(8).'.jpg';
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, filesize($path), 'image/jpeg', null, true);

        $response = $this->call('POST', 'test', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertTrue($content->ok);
        $session->ageFlashData(); // should this be required, shouldn't it happen during $this->call?

        $remembered = $session->get('_remembered_files');
        $this->assertArrayHasKey('img', $remembered);
        $this->assertEquals($name, $remembered['img']['originalName']);

        //
        // Test that the view composer sets the right properties
        //
        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        $this->assertInstanceOf(FileBag::class, $viewData['rememberedFiles']);
        $this->assertEquals(1, $viewData['rememberedFiles']->count());

        //
        // Test that upon re-calling the post event without any image data that
        // the _remembered_files doesn't contain any old data.
        //

        $response = $this->call('POST', 'test', [], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData(); // should this be required, shouldn't it happen during $this-

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        //
        // Test that the view composer sets the right properties
        //
        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        $this->assertInstanceOf(FileBag::class, $viewData['rememberedFiles']);
        $this->assertEquals(0, $viewData['rememberedFiles']->count());
    }

    /**
     * This tests to see if the middleware correctly captures the cached upload file from the
     * form data with the naming format _rememberedFiles[key].
     *
     * It then goes to check that "refreshing" the page without any file upload will clear
     * the captured uploaded file.
     */
    public function testSingleFileUploadOldRemembered()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $stub = __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'test.jpg';
        $name = str_random(8).'.jpg';
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, filesize($path), 'image/jpeg', null, true);

        $response = $this->call('POST', 'test', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        // "Refresh"...

        $response = $this->call('POST', 'test', ['_rememberedFiles' => ['img' => $name]], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        $this->assertInstanceOf(FileBag::class, $viewData['rememberedFiles']);
        $this->assertEquals(1, $viewData['rememberedFiles']->count());

        // "Refresh...

        $response = $this->call('POST', 'test', [], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        $this->assertInstanceOf(FileBag::class, $viewData['rememberedFiles']);
        $this->assertEquals(0, $viewData['rememberedFiles']->count());
    }

    private function mockView()
    {
        /** @var Factory $factory */
        $factory = app(Factory::class);

        /** @var View $mockView */
        $mockView = $factory->file(__DIR__ . DIRECTORY_SEPARATOR . 'test.blade.php');

        /** @var RememberedFilesComposer $mockComposer */
        $mockComposer = $this->app->make(RememberedFilesComposer::class);

        $mockComposer->compose($mockView);
        return $mockView;
    }

}