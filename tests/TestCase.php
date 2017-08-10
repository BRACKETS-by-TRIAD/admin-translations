<?php

namespace Brackets\AdminTranslations\Test;

use Illuminate\Support\Facades\Artisan;
use Brackets\AdminTranslations\LanguageLine;
use Orchestra\Testbench\TestCase as Orchestra;
use Brackets\AdminTranslations\TranslationServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var \Brackets\AdminTranslations\LanguageLine */
    protected $languageLine;

    public function setUp()
    {
        parent::setUp();

        Artisan::call('migrate');

        include_once __DIR__.'/../database/migrations/create_language_lines_table.php.stub';

        (new \CreateLanguageLinesTable())->up();

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
    }

    public function getFixturesDirectory(string $path): string
    {
        return __DIR__."/fixtures/{$path}";
    }

    protected function createLanguageLine(string $group, string $key, array $text): LanguageLine
    {
        return LanguageLine::create(compact('group', 'key', 'text'));
    }
}
