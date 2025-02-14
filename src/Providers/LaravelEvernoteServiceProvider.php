<?php

namespace N30\LaravelEvernoteApi\Providers;

use Illuminate\Support\ServiceProvider;
use N30\LaravelEvernoteApi\Evernote;
use App;

class LaravelEvernoteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this
            ->registerPublishables();

        require_once __DIR__ . '/../../thirdparty/src/autoload.php';

        $this->app->bind(Evernote::class, function () {
            $config = config('evernote');

            return new Evernote($config);
        });

        try { 
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('EvernoteAPI',\N30\LaravelEvernoteApi\Facades\EvernoteAPI::class);
        } catch (\Exception $e) {
            // do nothing
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/evernote.php', 'evernote');

        App::bind('evernote', function()
        {
            return new Evernote();
        });
    }

    protected function registerPublishables(): self
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/evernote.php' => config_path('evernote.php'),
            ], 'config');
        }

        return $this;
    }
}
