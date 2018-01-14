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
            'img' => 'required_without:_rememberedFiles[img]|mimes:jpeg'
        ]);

        $n =1;
    }
}