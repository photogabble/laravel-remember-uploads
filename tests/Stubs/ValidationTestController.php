<?php

namespace Photogabble\LaravelRememberUploads\Tests\Stubs;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            'img' => 'required_without:_rememberedFiles.img',
            'img.*' => 'mimes:jpeg'
        ]);

        /** @var UploadedFile[] $files */
        $files = rememberedFile('img', $request->file('img'));

        return json_encode([
            'name_0' => $files[0]->getFilename(),
            'name_1' => $files[1]->getFilename(),
        ]);
    }

    public function failedFileUpload(Request $request)
    {
        $this->validate($request, [
            'img' => 'required_without:_rememberedFiles.img|mimes:png'
        ]);
    }
}