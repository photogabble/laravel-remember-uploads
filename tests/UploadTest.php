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

    public function tearDown()
    {
        $file = new Filesystem();
        $file->cleanDirectory(storage_path('app' . DIRECTORY_SEPARATOR . 'tmp-image-uploads'));
        $file->deleteDirectory(storage_path('app' . DIRECTORY_SEPARATOR . 'tmp-image-uploads'));
        parent::tearDown();
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

        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');

        $response = $this->call('POST', 'test', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertTrue($content->ok);
        $session->ageFlashData(); // should this be required, shouldn't it happen during $this->call?

        /** @var RememberedFileBag $remembered */
        $remembered = $session->get('_remembered_files');
        $this->assertInstanceOf(RememberedFileBag::class, $remembered);
        $this->assertTrue($remembered->has('img'));
        $this->assertEquals($file->getClientOriginalName(), $remembered->get('img')->originalName);

        //
        // Test that the view composer sets the right properties
        //

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(1, $remembered->count());

        //
        // Test that upon re-calling the post event without any image data that
        // the _remembered_files doesn't contain any old data.
        //

        $response = $this->call('POST', 'test', [], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData(); // should this be required, shouldn't it happen during $this-

        $remembered = $session->get('_remembered_files', new RememberedFileBag());
        $this->assertInstanceOf(RememberedFileBag::class, $remembered);
        $this->assertEquals(0, $remembered->count());

        //
        // Test that the view composer sets the right properties
        //

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(0, $remembered->count());
    }

    /**
     * This tests to see if the middleware correctly captures the uploaded file if the input
     * is within an array.
     *
     * This test was written for issue #15.
     * @see https://github.com/photogabble/laravel-remember-uploads/issues/15
     */
    public function testArrayFileUpload()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $files = [
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
        ];

        $response = $this->call('POST', 'test', [], [], ['img' => $files], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertTrue($content->ok);
        $session->ageFlashData();

        /** @var RememberedFileBag $remembered */
        $remembered = $session->get('_remembered_files');
        $this->assertTrue($remembered->has('img'));
        $this->assertTrue(is_array($remembered->get('img')));
        $this->assertEquals($files[0]->getClientOriginalName(), $remembered->get('img')[0]->originalName);
        $this->assertEquals($files[1]->getClientOriginalName(), $remembered->get('img')[1]->originalName);

        //
        // Test that the view composer sets the right properties
        //

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);

        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];

        $this->assertInstanceOf(FileBag::class, $viewData['rememberedFiles']);
        $this->assertEquals(1, $remembered->count());
        $this->assertTrue($remembered->has('img'));
        $this->assertTrue(is_array($remembered->get('img')));
        $this->assertEquals(2, count($remembered->get('img')));

        //
        // Test that upon re-calling the post event without any image data that
        // the _remembered_files doesn't contain any old data.
        //

        $response = $this->call('POST', 'test', [], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData(); // should this be required, shouldn't it happen during $this-

        $remembered = $session->get('_remembered_files', new RememberedFileBag());
        $this->assertInstanceOf(RememberedFileBag::class, $remembered);
        $this->assertEquals(0, $remembered->count());

        //
        // Test that the view composer sets the right properties
        //

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(0, $remembered->count());
    }

    /**
     * This test is in place as an example to be referenced by the README.md
     */
    public function testFileControllerExample()
    {
        /**
         * @var \Illuminate\Routing\Router $router
         */
        $router = $this->app->make('router');

        $router->post('test-request', function (Request $request) {
            $file = rememberedFile('img', $request->file('img'));
            return ['ok' => true, 'filename' => $file->getFilename(), 'pathname' => $file->getPathname()];
        })->middleware('remember.files');

        /** @var Store $session */
        $session = $this->app->make(Store::class);

        // Post the File the first time
        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');

        $this->call('POST', 'test-request', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $session->ageFlashData();

        // Post the _rememberedFiles value
        $response = $this->call('POST', 'test-request', ['_rememberedFiles' => ['img' => rememberedFile('img')->getFilename()]], [], [], ['Accept' => 'application/json']);
        $content  = json_decode($response->content());

        $this->assertSame($file->getFilename(), $content->filename);
        $this->assertFileExists($content->pathname);
        $this->assertSame(sha1_file($file->getPathname()), sha1_file($content->pathname));
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

        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');

        $response = $this->call('POST', 'test', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        // "Refresh"...

        $response = $this->call('POST', 'test', ['_rememberedFiles' => ['img' => $file->getClientOriginalName()]], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(1, $remembered->count());

        // "Refresh...

        $response = $this->call('POST', 'test', [], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(0, $remembered->count());
    }

    /**
     * This test was written for issue #15.
     * @see https://github.com/photogabble/laravel-remember-uploads/issues/15
     */
    public function testArrayFileUploadOldRemembered()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $files = [
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
        ];

        $response = $this->call('POST', 'test', [], [], ['img' => $files], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        // "Refresh"...

        $response = $this->call('POST', 'test', ['_rememberedFiles' => ['img' => [$files[0]->getClientOriginalName(), $files[1]->getClientOriginalName()]]], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(1, $remembered->count());

        // "Refresh...

        $response = $this->call('POST', 'test', [], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $viewData = $this->mockView()->getData();
        $this->assertArrayHasKey('rememberedFiles', $viewData);
        /** @var FileBag $remembered */
        $remembered = $viewData['rememberedFiles'];
        $this->assertInstanceOf(FileBag::class, $remembered);
        $this->assertEquals(0, $remembered->count());
    }

    /**
     * Test the rememberedFile helper.
     */
    public function testHelper()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');

        $response = $this->call('POST', 'test', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $fileBag = rememberedFile();
        $this->assertInstanceOf(FileBag::class, $fileBag);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\File\UploadedFile::class, $fileBag->get('img'));

        $rememberedFile = rememberedFile('img');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\File\UploadedFile::class, $rememberedFile);

        $this->assertNull(rememberedFile('test'));
        $this->assertTrue(rememberedFile('test', true));
        $this->assertFalse(rememberedFile('test', false));
    }

    /**
     * Test the rememberedFile helper.
     * This test was extended for issue #15.
     * @see https://github.com/photogabble/laravel-remember-uploads/issues/15
     */
    public function testHelperWithArray()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $files = [
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
        ];

        $response = $this->call('POST', 'test', [], [], ['img' => $files], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        $fileBag = rememberedFile();
        $this->assertInstanceOf(FileBag::class, $fileBag);
    }

    /**
     * Test written for issue #2.
     * Tests to check that validation being recommended in the README actually works.
     * @see https://github.com/photogabble/laravel-remember-uploads/issues/2
     */
    public function testSingleFileValidationPasses()
    {
        /**
         * @var \Illuminate\Routing\Router $router
         */
        $router = $this->app->make('router');
        $router->post(
            'test-validation',
            [
                'middleware' => ['remember.files'],
                'uses' => '\Photogabble\LaravelRememberUploads\Tests\Stubs\ValidationTestController@fileUpload'
            ]
        );

        /** @var Store $session */
        $session = $this->app->make(Store::class);

        // Test controller validation is working.
        $response = $this->call('POST', 'test-validation', [], [], [], ['Accept' => 'application/json']);
        $this->assertFalse($response->isOk());

        // Test controller based rememberedFile is working.
        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');
        $response = $this->call('POST', 'test-validation', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertEquals($file->getClientOriginalName(), $content->name);

        $session->ageFlashData();
        $session->flush();

        // Test controller _rememberedFiles is working.
        $response = $this->call('POST', 'test-validation', ['_rememberedFiles'=> ['img' => $file->getClientOriginalName()]], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertEquals($file->getClientOriginalName(), $content->name);
    }

    public function testArrayFileValidationPasses()
    {
        /**
         * @var \Illuminate\Routing\Router $router
         */
        $router = $this->app->make('router');
        $router->post(
            'test-validation',
            [
                'middleware' => ['remember.files'],
                'uses' => '\Photogabble\LaravelRememberUploads\Tests\Stubs\ValidationTestController@arrayFileUpload'
            ]
        );

        /** @var Store $session */
        $session = $this->app->make(Store::class);

        // Test controller validation is working.
        $response = $this->call('POST', 'test-validation', [], [], [], ['Accept' => 'application/json']);
        $this->assertFalse(is_null($response->exception));
        $this->assertEquals('The given data failed to pass validation.', $response->exception->getMessage());
        $this->assertFalse($response->isOk());

        // Test controller based rememberedFile is working.
        $files = [
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
            $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg'),
        ];

        $response = $this->call('POST', 'test-validation', [], [], ['img' => $files], ['Accept' => 'application/json']);
        $this->assertTrue(is_null($response->exception));
        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());
        $this->assertEquals($files[0]->getClientOriginalName(), $content->name_0);
        $this->assertEquals($files[1]->getClientOriginalName(), $content->name_1);

        $session->ageFlashData();
        $session->flush();

        // Test controller _rememberedFiles is working.
        $response = $this->call('POST', 'test-validation', ['_rememberedFiles'=> ['img' => [$files[0]->getClientOriginalName(), $files[1]->getClientOriginalName()]]], [], [], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertEquals($files[0]->getClientOriginalName(), $content->name_0);
        $this->assertEquals($files[1]->getClientOriginalName(), $content->name_1);
    }

    /**
     * Test written for issue #2
     * @see https://github.com/photogabble/laravel-remember-uploads/issues/2
     */
    public function testFilesForgottenWhenValidationFails()
    {
        /**
         * @var \Illuminate\Routing\Router $router
         */
        $router = $this->app->make('router');
        $router->post(
            'test-validation',
            [
                'middleware' => ['remember.files'],
                'uses' => '\Photogabble\LaravelRememberUploads\Tests\Stubs\ValidationTestController@failedFileUpload'
            ]
        );

        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');
        $response = $this->call('POST', 'test-validation', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertFalse($response->isOk());

        $remembered = rememberedFile('img');
        $this->assertNull($remembered);
    }

    /**
     * Test written for issue #4.
     * Tests the clearRememberedFiles helper function.
     * @see https://github.com/photogabble/laravel-remember-uploads/issues/4
     */
    public function testClearRememberedFilesHelperFunction()
    {
        /** @var Store $session */
        $session = $this->app->make(Store::class);

        $remembered = $session->get('_remembered_files', []);
        $this->assertEquals([], $remembered);

        $file = $this->mockUploadedFile(__DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'test.jpg');

        $response = $this->call('POST', 'test', [], [], ['img' => $file], ['Accept' => 'application/json']);
        $this->assertTrue($response->isOk());
        $session->ageFlashData();

        /** @var RememberedFileBag $remembered */
        $remembered = $session->get('_remembered_files', new RememberedFileBag());
        $this->assertTrue($remembered->has(('img')));

        clearRememberedFiles();

        /** @var RememberedFileBag $remembered */
        $remembered = $session->get('_remembered_files', new RememberedFileBag());
        $this->assertFalse($remembered->has(('img')));
    }

    /**
     * Mock an uploaded file from a given src file.
     *
     * @param string $stub
     * @return UploadedFile
     */
    private function mockUploadedFile($stub) {
        $name = str_random(8).'.jpg';
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;

        copy($stub, $path);
        return new UploadedFile($path, $name, filesize($path), 'image/jpeg', null, true);
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