<?php

namespace Photogabble\LaravelRememberUploads\Middleware;

use Closure;

class RememberFileUploads
{

    /**
     * @var \Illuminate\Session\Store
     */
    private $session;

    /**
     * RememberFileUploads constructor.
     * @param \Illuminate\Session\Store $store
     */
    public function __construct(\Illuminate\Session\Store $store)
    {
        $this->session = $store;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  array $fields
     * @return mixed
     */
    public function handle($request, Closure $next, $fields = ['*'])
    {
        if ($request->files->count() > 0) {
            $this->remember($request, $fields);
        }

        return $next($request);
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
         * @var \Symfony\Component\HttpFoundation\File\UploadedFile $file
         * @todo there is likely a bug here when $file is an array and not an UploadedFile... write unit test
         */
        foreach ($files as $key => $file) {
            $storagePath = storage_path('app' . DIRECTORY_SEPARATOR . 'tmp-image-uploads');

            if (! file_exists($storagePath)) {
                if (!mkdir($storagePath)) {
                    throw new \Exception('Could not create directory ['. $storagePath .'].');
                }
            }

            $storagePathName = $storagePath . DIRECTORY_SEPARATOR . $file->getFilename();

            copy($file->getPathname(), $storagePathName);

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
