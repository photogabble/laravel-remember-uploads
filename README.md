# laravel-remember-uploads

**[Please Note]**: This package is currently a work in progress and has been initially developed for Laravel 5.4.

## Install

Add to your project with compoer via `composer require photogabble/laravel-remember-uploads`.

Next add the package service provider to your providers configuration in Laravel.

```
'providers' => [
    // ...
    
    Photogabble\LaravelRememberUploads\RememberUploadsServiceProvider::class,
    
    // ...
],
```

Now you can assign the middleware `remember.files` to routes that you want the packages functionality to operate on.
