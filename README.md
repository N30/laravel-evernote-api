
## Installation

The Evernote cloud Service Provider can be installed via [Composer](http://getcomposer.org) by requiring the
`n30/laravel-evernote-api` package and setting the `minimum-stability` to `dev` (required for Laravel 5) in your
project's `composer.json`.

```json
{
    "require": {
        "n30/laravel-evernote-api": "^1.0"
    },
    "minimum-stability": "dev"
}
```

or

Require this package with composer:
```
composer require n30/laravel-evernote-api
```
Update your `composer.json` file to include this package as a dependency

Update your packages with ```composer update``` or install with ```composer install```.

In Windows, you'll need to include the GD2 DLL `php_gd2.dll` as an extension in php.ini.


## Usage

To use the Evernote Cloud Service Provider, you must register the provider when bootstrapping your Laravel application. There are
essentially two ways to do this.
  
The package will automatically load its service provider and register the Facade: Evernote::

You can optionally, publish the config file of the package.
```bash
php artisan vendor:publish --provider="N30\LaravelEvernoteApi\Providers\LaravelEvernoteServiceProvider" --tag=config
```

For Laravel 5 use [1.0.0](https://github.com/n30/laravel-evernote-api/tree/1.0.0)

## Configuration

You can configure this in your .env file.

```php 
	EVERNOTE_KEY=your evernote key
	EVERNOTE_SECRET=your evernote secrect
	EVERNOTE_SANDBOX=true/false
	EVERNOTE_CALL_BACK=callback url eg: /evernote/callback , ?action=callback
	EVERNOTE_CHINA=false
```

to receive a token - Authentication

```php 
    //simple example of all-in-one route
    Route::any('oauth/callback/evernote',function( Request $request){
        
        \Evernote::authorize();
        
    });
```
