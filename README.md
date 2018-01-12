<h1 align="center">Laravel Remember Uploads</h1>
<p align="center"><em>Middleware Package</em></p>

<p align="center">
  <a href="https://packagist.org/packages/photogabble/laravel-remember-uploads"><img src="https://poser.pugx.org/photogabble/laravel-remember-uploads/v/stable.svg" alt="Latest Stable Version"></a>
  <a href="LICENSE"><img src="https://poser.pugx.org/photogabble/laravel-remember-uploads/license.svg" alt="License"></a>
</p>

## About this package

This middleware allows the application to capture uploaded files and temporarily store them just-in-case the form validation redirects back otherwise loosing the files before your controler could process them.

## Install

Add to your project with compoer via `composer require photogabble/laravel-remember-uploads`.

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
