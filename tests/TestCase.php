<?php

namespace Brackets\AdminTranslations\Test;

use Brackets\AdminAuth\AdminAuthServiceProvider;
use Brackets\AdminAuth\Http\Middleware\CanAdmin;
use Brackets\AdminTranslations\AdminTranslationsServiceProvider;
use Brackets\Translatable\TranslatableServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Brackets\AdminTranslations\Translation;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Brackets\AdminTranslations\Test\Exceptions\Handler;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @var \Brackets\AdminTranslations\Translation */
    protected $languageLine;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('migrate');

        include_once __DIR__.'/../database/migrations/create_translations_table.php.stub';

        (new \CreateTranslationsTable())->up();

        $this->languageLine = $this->createTranslation('*', 'group', 'key', ['en' => 'english', 'nl' => 'nederlands']);

        File::copyDirectory(__DIR__.'/fixtures/resources/views', resource_path('views'));
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            TranslatableServiceProvider::class,
            AdminTranslationsServiceProvider::class,
            AdminAuthServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['path.lang'] = $this->getFixturesDirectory('lang');

        $app['config']->set('translatable.locales', ['en', 'sk']);

        if (env('DB_CONNECTION') === 'pgsql') {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => 'testing',
                'port' => '5432',
                'database' => 'homestead',
                'username' => 'homestead',
                'password' => 'secret',
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        $app['config']->set('admin-translations.model', Translation::class);

        $app['config']->set('admin-translations.scanned_directories', [__DIR__.'/fixtures/views']);

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');

        $app['config']->set('admin-auth.defaults.guard', 'admin');
        $app['config']->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'admin_users',
        ]);
        $app['config']->set('auth.providers.admin_users', [
            'driver' => 'eloquent',
            'model' => \Brackets\AdminAuth\Models\AdminUser::class,
        ]);
    }

    public function getFixturesDirectory(string $path): string
    {
        return __DIR__."/fixtures/{$path}";
    }

    //TODO reorder
    protected function createTranslation(string $namespace, string $group, string $key, array $text): Translation
    {
        return Translation::create(compact('group', 'key', 'namespace', 'text'));
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
