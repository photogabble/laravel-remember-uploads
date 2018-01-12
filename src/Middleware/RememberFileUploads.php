<?php

namespace Photogabble\LaravelRememberUploads\Middleware;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
            $storagePathName = $this->storagePath . DIRECTORY_SEPARATOR . $fileName;

            if (! $this->cache->has('_remembered_files.'.$key)){
                continue;
            }

            $cached = $this->cache->get('_remembered_files.'.$key);
            $stored[$key] = [
                'tmpPathName' => $storagePathName,
                'originalName' => $cached['originalName'],
                'mimeType' => $cached['mimeType'],
                'size' => $cached['size']
            ];
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
        $stored = [];

        /**
         * @var UploadedFile $file
         * @todo there is likely a bug here when $file is an array and not an UploadedFile... write unit test
         */
        foreach ($files as $key => $file) {
            $storagePathName = $this->storagePath . DIRECTORY_SEPARATOR . $file->getFilename();
            copy($file->getPathname(), $storagePathName);

            $this->cache->put('_remembered_files.'.$key, [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize()
            ], $this->cacheTimeout);

            $stored[$key] = [
                'tmpPathName' => $storagePathName,
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize()
            ];
        }

        $this->session->flash('_remembered_files', $stored);
    }
}
