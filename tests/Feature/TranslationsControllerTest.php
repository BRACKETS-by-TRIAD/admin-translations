<?php namespace Brackets\AdminTranslations\Test\Unit\Scanner;

use Brackets\AdminTranslations\Http\Requests\Admin\LanguageLine\IndexLanguageLine;
use Brackets\AdminTranslations\LanguageLine;
use Brackets\AdminTranslations\Test\TestCase;
use Gate;
use Illuminate\Foundation\Auth\User;
use Mockery;

class TranslationsControllerTest extends TestCase
{

    /** @test */
    function authorized_user_can_see_translations_stored_in_database(){
        $this->authorizedToIndex();

        $this->createLanguageLine('admin', 'Default version', ['en' => '1 English version', 'sk' => '1 Slovak version']);
        $this->createLanguageLine('admin', 'some.key', ['en' => '2 English version', 'sk' => '2 Slovak version']);

        $this->get('/admin/translations')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertSee('some.key')
            ;

        $this->assertCount(3, LanguageLine::all());
    }

    /** @test */
    function authorized_user_can_filter_by_group(){
        $this->authorizedToIndex();

        $this->createLanguageLine('admin', 'Default version', ['en' => '1 English version', 'sk' => '1 Slovak version']);
        $this->createLanguageLine('frontend', 'some.key', ['en' => '2 English version', 'sk' => '2 Slovak version']);

        $this->get('/admin/translations?group=admin')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertDontSee('some.key')
        ;
    }


    /** @test */
    function not_authorized_user_cannot_see_or_update_anything(){
        $this->get('/admin/translations')
            ->assertStatus(403)
        ;
    }

    protected function authorizedToIndex() {
        $this->actingAs(new User);
        Gate::define('admin.translations.index', function() {
            return true;
        });
    }

}
