<?php namespace Brackets\AdminTranslations\Test\Unit\Scanner;

use Brackets\AdminTranslations\LanguageLine;
use Brackets\AdminTranslations\Test\TestCase;

class TranslationsControllerTest extends TestCase
{
    /** @test */
    function authorized_user_can_see_translations_stored_in_database(){

        $this->createLanguageLine('admin', 'Default version', ['en' => 'English version', 'sk' => 'Slovak version']);

        $this->get('/admin/translations')
            ->assertSee('Default version')
            ->assertSee('group.key');

        $this->assertCount(2, LanguageLine::all());

    }

}
