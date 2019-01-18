<?php

Route::group(['middleware' => ['web']], function(){
    Route::namespace('Brackets\AdminTranslations\Http\Controllers\Admin')->group(function () {
        Route::get('/admin/translations',                   'TranslationsController@index');
        Route::post('/admin/translations/rescan',           'RescanTranslationsController@rescan');

        Route::post('/admin/translations/{translation}',    'TranslationsController@update');
    });
});

