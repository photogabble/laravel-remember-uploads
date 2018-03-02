<?php

namespace Photogabble\LaravelRememberUploads\Middleware;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Session\Store;
use Photogabble\LaravelRememberUploads\RememberedFile;
use Illuminate\Http\UploadedFile;

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
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  array $fields
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next, $fields = ['*'])
    {
        //
        // There is a bug here, that if the user uploads an additional file to what has been remembered
        // then they will loose the remembered one.
        //
        // The method checkRequestForRemembered needs to always be executed.
        // @todo the above
        //
        if ($request->files->count() > 0) {
            $this->remember($request, $fields);
        }else{
            $this->checkRequestForRemembered($request, $fields);
        }

        return $next($request);
    }

    /**
     * Remember all files found in request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $fields
     */
    private function checkRequestForRemembered($request, array $fields)
    {
        $remembered = $request->get('_rememberedFiles', []);
        $files = ($fields[0] === '*') ? $remembered : array_filter($remembered, function($k) use ($fields) { return in_array($k, $fields); }, ARRAY_FILTER_USE_KEY);
        $stored = [];

        foreach ($files as $key => $fileName) {
            if (! $this->cache->has('_remembered_files.'.$key)){
                continue;
            }
            $stored[$key] = $this->cache->get('_remembered_files.'.$key);
        }

        $this->session->flash('_remembered_files', $stored);
    }

    /**
     * Remember all files found in request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $fields
     * @throws \Exception
     */
    private function remember($request, array $fields)
    {
        $files = ($fields[0] === '*') ? $request->files : $request->only($fields);
        $this->session->flash('_remembered_files', $this->rememberFilesFactory($files));
    }

    /**
     * @param array|UploadedFile[] $files
     * @param string $prefix
     * @return array
     */
    private function rememberFilesFactory($files, $prefix = '')
    {
        $result = [];

        foreach ($files as $key => $file) {
            $cacheKey = $prefix . (empty($prefix) ? '' : '.') . $key;
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
