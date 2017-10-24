<?php

namespace App\Http\Middleware;

use Closure;

class RememberFileUploads
{

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
     */
    private function remember($request, array $fields)
    {
        $files = ($fields[0] === '*') ? $request->files : $request->only($fields);
        $stored = [];

        /**
         * @var \Symfony\Component\HttpFoundation\File\UploadedFile $file
         */
        foreach ($files as $file) {
            $details = [
                'tmpPathName' => $file->getPathname(),
                'originalName' => $file->getClientOriginalName()
            ];
            array_push($stored, $details);
        }
        
        $this->session->put('remembered.files', $stored);
    }
}
