<?php namespace Brackets\AdminTranslations\Test;

use Brackets\AdminTranslations\TranslationsScanner;
use Illuminate\Filesystem\Filesystem;

class TranslationsManagerTest extends TestCase
{

    /** @test */
    function testing(){
        $path = realpath(base_path('resources/lang'));

        $manager = new TranslationsScanner(
            new Filesystem,
            $path,
            array_merge($this->app['config']['view.paths'], [$this->app['path']])
        );

        dd($manager->getAllViewFilesWithTranslations());
    }

}
