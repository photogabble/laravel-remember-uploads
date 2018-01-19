<h1 align="center">Laravel Remember Uploads</h1>
<p align="center"><em>Middleware Package</em></p>

<p align="center">
  <a href="https://travis-ci.org/photogabble/laravel-remember-uploads"><img src="https://travis-ci.org/photogabble/laravel-remember-uploads.svg?branch=master" alt="Build Status"></a>
  <a href="https://packagist.org/packages/photogabble/laravel-remember-uploads"><img src="https://poser.pugx.org/photogabble/laravel-remember-uploads/v/stable.svg" alt="Latest Stable Version"></a>
  <a href="LICENSE"><img src="https://poser.pugx.org/photogabble/laravel-remember-uploads/license.svg" alt="License"></a>
</p>

## About this package

This middleware allows the application to capture uploaded files and temporarily store them just-in-case the form validation redirects back otherwise losing the files before your controller could process them.

## Install

Add to your project with composer via `composer require photogabble/laravel-remember-uploads`.

To enable the package you will need to add its service provider to your app providers configuration in Laravel.

```php
'providers' => [
    // ...
    
    Photogabble\LaravelRememberUploads\RememberUploadsServiceProvider::class,
    
    // ...
],
```

Now you can assign the middleware `remember.files` to routes that you want the packages functionality to operate on.

## Usage

To ensure that remembered files remain as such across page refreshes (due to other validation errors) you need to include a reference by way of using a hidden input field with the name `_rememberedFiles`.

```php
@if( $oldFile = rememberedFile('file'))
    <input type="hidden" name="_rememberedFiles[file]" value="{{ $oldFile->getFilename() }}">
@else
    <input type="file" name="file">
@endif
```

Then within your controller code you can obtain the file via the `rememberedFile` helper:

```php
function store(Illuminate\Http\Request $request) {    
    if ($file = $request->file('img', rememberedFile('img')) {
        // ... File exists ...
    }
}
```

The `$file` variable will equal an instance of `Symfony\Component\HttpFoundation\File\UploadedFile` if the file has been posted during the current request or remembered. 

This example is viewable as a test case [within this libaries tests](https://github.com/photogabble/laravel-remember-uploads/blob/master/tests/UploadTest.php#L114).
