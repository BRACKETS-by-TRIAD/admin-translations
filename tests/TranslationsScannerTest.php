<?php namespace Brackets\AdminTranslations\Test;

use Brackets\AdminTranslations\TranslationsScanner;
use Illuminate\Filesystem\Filesystem;

class TranslationsScannerTest extends TestCase
{
    private $viewsDir = __DIR__.'/fixtures/views';

    /** @test */
    function testing(){
        $scanner = new TranslationsScanner(
            new Filesystem,
            [$this->viewsDir]
        );

        $this->assertEquals([
            'fooA.blade.php' => [
                "good.key1",
                "good.key2",
                "Good key 3",
                "Good 'key' 4",
                "Good \"key\" 5",
            ]
        ], $scanner->getAllViewFilesWithTranslations());
    }

}
