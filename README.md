<h1 align="center">Laravel Remember Uploads</h1>
<p align="center"><em>Middleware Package</em></p>

<p align="center">
  <a href="https://travis-ci.org/photogabble/laravel-remember-uploads"><img src="https://travis-ci.org/photogabble/laravel-remember-uploads.svg?branch=master" alt="Build Status"></a>
  <a href="https://packagist.org/packages/photogabble/laravel-remember-uploads"><img src="https://img.shields.io/packagist/v/photogabble/laravel-remember-uploads.svg" alt="Latest Stable Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/github/license/photogabble/laravel-remember-uploads.svg" alt="License"></a>
</p>

## About this package

This middleware solves the issue of unrelated form validation errors redirecting the user back and loosing the files that had been uploaded. It does this by temporarily caching server-side the file fields that have passed validation so that they may be processed once the whole form has been submitted passing validation.

## Install

Add to your project with composer via `composer require photogabble/laravel-remember-uploads`.

### Laravel Version >= 5.5

This library supports [package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) in Laravel >= 5.5.

### Laravel Versions 5.2 - 5.5

To enable the package you will need to add its service provider to your app providers configuration in Laravel.

```php
'providers' => [
    // ...
    
    Photogabble\LaravelRememberUploads\RememberUploadsServiceProvider::class,
    
    // ...
],
```

## Usage

You need to assign the middleware `remember.files` to routes that process uploaded files; in the case of CRUD terminology that would be the _create_ and _update_ methods.

So that the middleware is aware of remembered files from the previous request you need to include a reference by way of using a hidden input field with the name `_rememberedFiles`.

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

This example is viewable as a test case [within this libaries tests](https://github.com/photogabble/laravel-remember-uploads/blob/master/tests/UploadTest.php#L192).

### Array File Fields

In the case where you have multiple upload fields sharing the same name for example `image[0]`, `image[1]`; the helper `rememberedFile('image')` will return an array of `Symfony\Component\HttpFoundation\File\UploadedFile`.

The reference `_rememberedFiles` will also need to match the array syntax of the file inputs it mirrors:

```php
@if( $oldFile = rememberedFile('image'))
    <!-- $oldFile is now an array of Symfony\Component\HttpFoundation\File\UploadedFile -->
    <input type="hidden" name="_rememberedFiles[image][0]" value="{{ $oldFile[0]->getFilename() }}">
    <input type="hidden" name="_rememberedFiles[image][1]" value="{{ $oldFile[1]->getFilename() }}">
@else
    <input type="file" name="image[0]">
    <input type="file" name="image[1]">
@endif
```
