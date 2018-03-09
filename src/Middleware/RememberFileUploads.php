<?php

namespace Photogabble\LaravelRememberUploads\Middleware;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Session\Store;
use Photogabble\LaravelRememberUploads\RememberedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Photogabble\LaravelRememberUploads\RememberedFileBag;

/**
 * Class RememberFileUploads
 *
 * The point of this middleware is to provide a caching mechanism for uploaded files that would otherwise be lost
 * if the originating form has a validation error.
 *
 * The logical flow is as follows:
 *
 * 1.   Form Submit
 * 2.   Middleware checks Request for files
 *      2.a If files found:
 *          2.a.i   Check that they aren't already cached -> if they are replace cached
 *                  version with fresh uploaded version
 *          2.a.ii  For Uploaded Files that aren't cached do so
 * 3.   On validation error user is redirected back to form
 *      3.a Form now contains hidden fields for each item returned by rememberedFile helper.
 * 4.   Controller method can obtain Files via the rememberedFile helper.
 * 5.   Controller clears remembered files via the clearRememberedFiles helper.
 *
 * @package Photogabble\LaravelRememberUploads\Middleware
 */
class RememberFileUploads
{

    /**
     * @var Store
     */
    private $session;

    /**
     * @var CacheManager
     */
    private $cache;

    /**
     * @var string
     */
    private $storagePath;

    /**
     * Session lifetime, used for caching values.
     * @var int
     */
    private $cacheTimeout = 0;

    /**
     * RememberFileUploads constructor.
     * @param Store $store
     * @param CacheManager $cache
     * @throws \Exception
     */
    public function __construct(Store $store, CacheManager $cache)
    {
        $this->session = $store;
        $this->cache = $cache;
        $this->storagePath = storage_path('app' . DIRECTORY_SEPARATOR . 'tmp-image-uploads');
        $this->cacheTimeout = config('session.lifetime');

        if (! file_exists($this->storagePath)) {
            if (!mkdir($this->storagePath)) {
                throw new \Exception('Could not create directory ['. $this->storagePath .'].');
            }
        }
    }

    /**
     * Handle an incoming request.
     *
     * @todo write a test to check that adding additional uploaded files to the same session doesn't break things
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  array $fields
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next, $fields = ['*'])
    {
        $this->session->flash('_remembered_files', new RememberedFileBag(array_merge($this->checkRequestForRemembered($request, $fields), $this->remember($request, $fields))));
        return $next($request);
    }

    /**
     * Remember all files found in request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $fields
     * @return array|RememberedFile[]
     */
    private function checkRequestForRemembered($request, array $fields)
    {
        $remembered = $request->get('_rememberedFiles', []);
        $files = ($fields[0] === '*') ? $remembered : array_filter($remembered, function($k) use ($fields) { return in_array($k, $fields); }, ARRAY_FILTER_USE_KEY);
        return $this->rememberFilesFactory($files);
    }

    /**
     * Remember all files found in request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $fields
     * @return array|RememberedFile[]
     */
    private function remember($request, array $fields)
    {
        $files = ($fields[0] === '*') ? $request->files : $request->only($fields);
        return $this->rememberFilesFactory($files);
    }

    /**
     * Recursive factory method to create RememberedFile from UploadedFile.
     *
     * @param array|UploadedFile[] $files
     * @param string $prefix
     * @return array|RememberedFile[]
     */
    private function rememberFilesFactory($files, $prefix = '')
    {
        $result = [];

        foreach ($files as $key => $file) {
            $cacheKey = $prefix . (empty($prefix) ? '' : '.') . $key;
            if (is_string($file)){
                if (! $this->cache->has('_remembered_files.'.$cacheKey)){
                    continue;
                }
                /** @noinspection Annotator */
                $cached = $this->cache->get('_remembered_files.'.$cacheKey);
                if ($cached instanceof RememberedFile){
                    $result[$key] = $cached;
                }
                unset($cached);
                continue;
            }
            if (is_array($file)) {
                $result[$key] = $this->rememberFilesFactory($file, $cacheKey);
            } else {
                $storagePathName = $this->storagePath . DIRECTORY_SEPARATOR . $file->getFilename();
                copy($file->getPathname(), $storagePathName);
                $rememberedFile = new RememberedFile($storagePathName, $file);
                $this->cache->put('_remembered_files.'.$cacheKey, $rememberedFile, $this->cacheTimeout);
                $result[$key] = $rememberedFile;
            }
        }

        return $result;
    }
}
