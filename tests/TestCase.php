<?php

namespace Brackets\AdminTranslations\Test;

use Brackets\AdminTranslations\AdminTranslationsProvider;
use Illuminate\Support\Facades\Artisan;
use Brackets\AdminTranslations\LanguageLine;
use Spatie\TranslationLoader\TranslationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Brackets\AdminTranslations\Test\Exceptions\Handler;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

abstract class TestCase extends Orchestra
{
    /** @var \Brackets\AdminTranslations\LanguageLine */
    protected $languageLine;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('migrate');

        include_once __DIR__.'/../vendor/spatie/laravel-translation-loader/database/migrations/create_language_lines_table.php.stub';
        include_once __DIR__.'/../database/migrations/change_language_lines_table.php.stub';

        (new \CreateLanguageLinesTable())->up();
        (new \ChangeLanguageLinesTable())->up();

        $this->languageLine = $this->createLanguageLine('group', 'key', ['en' => 'english', 'nl' => 'nederlands']);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
            AdminTranslationsProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['path.lang'] = $this->getFixturesDirectory('lang');

        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('laravel-translation-loader.model', LanguageLine::class);
    }

    public function getFixturesDirectory(string $path): string
    {
        return __DIR__."/fixtures/{$path}";
    }

    protected function createLanguageLine(string $group, string $key, array $text): LanguageLine
    {
        return LanguageLine::create(compact('group', 'key', 'text'));
    }

    public function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct() {}

            public function report(Exception $e)
            {
                // no-op
            }

            public function render($request, Exception $e) {
                throw $e;
            }
        });
    }
}
