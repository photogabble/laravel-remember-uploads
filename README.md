# laravel-remember-uploads

**[Please Note]**: This package is currently a work in progress and has been initially developed for Laravel 5.4.

## Install

Add to your project with compoer via `composer require photogabble/laravel-remember-uploads`.

Next add the package service provider to your providers configuration in Laravel.

```php
'providers' => [
    // ...
    
    Photogabble\LaravelRememberUploads\RememberUploadsServiceProvider::class,
    
    // ...
],
```

Now you can assign the middleware `remember.files` to routes that you want the packages functionality to operate on.

## Usage

To ensure that remembered files remain as such accross page refreshes (due to other validation errors) you need to include a reference by way of using a hidden input field with the name `_rememberedFiles`.

```php
@if( $oldFile = oldFile('file'))
    <input type="hidden" name="_rememberedFiles[file]" value="{{ $oldFile->getFilename() }}">
@else
    <input type="file" name="file">
@endif
```

Then within your controller code you can obtain the file via the `oldFile` helper:

```php
function store(Illuminate\Http\Request $request) {    
    if ($file = oldFile('img', $request->file('img'))) {
        // ... File exists ...
    }
}
```

The `$file` variable will equal an instance of `Symfony\Component\HttpFoundation\File\UploadedFile` if the file has been posted during the current request or remembered. 

This example is viewable as a test case within this libaries tests [here](https://github.com/photogabble/laravel-remember-uploads/blob/master/tests/UploadTest.php#L114).