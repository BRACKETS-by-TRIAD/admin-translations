<?php namespace Brackets\AdminTranslations\Test\Unit\Scanner;

use Brackets\AdminTranslations\LanguageLine;
use Brackets\AdminTranslations\Test\TestCase;

class TranslationsControllerTest extends TestCase
{
    /** @test */
    function authorized_user_can_see_translations_stored_in_database(){

        $this->disableExceptionHandling();

        $this->createLanguageLine('admin', 'Default version', ['en' => '1 English version', 'sk' => '1 Slovak version']);
        $this->createLanguageLine('admin', 'some.key', ['en' => '2 English version', 'sk' => '2 Slovak version']);

        $this->get('/admin/translations')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertSee('some.key')
            ;

        $this->assertCount(3, LanguageLine::all());

    }

}
