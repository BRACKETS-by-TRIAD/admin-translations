<?php

Route::group(['middleware' => ['web']], function(){
    Route::get('/admin/translation','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@index');
    Route::post('/admin/translation/{translation}','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@update');

    Route::post('/admin/rescan-translations','\Brackets\AdminTranslations\Http\Controllers\Admin\RescanTranslationsController@rescan');
});

