<?php

namespace Photogabble\LaravelRememberUploads\Tests\Stubs;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ValidationTestController extends Controller
{
    use ValidatesRequests;

    public function fileUpload(Request $request)
    {
        $this->validate($request, [
            'img' => 'required_without:_rememberedFiles.img|mimes:jpeg'
        ]);

        $file = rememberedFile('img', $request->file('img'));

        return json_encode([
            'name' => $file->getFilename()
        ]);
    }

    public function arrayFileUpload(Request $request)
    {
        $this->validate($request, [
            'img' => 'array|required_without:_rememberedFiles.img|mimes:jpeg',
            'img.*' => 'present|file|mimes:jpeg'
        ]);

        $files = rememberedFile('img', $request->file('img'));

        return json_encode([
            'name_0' => $files->get('img')[0],
            'name_1' => $files->get('img')[1],
        ]);
    }

    public function failedFileUpload(Request $request)
    {
        $this->validate($request, [
            'img' => 'required_without:_rememberedFiles.img|mimes:png'
        ]);
    }
}