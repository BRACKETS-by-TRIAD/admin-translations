<?php

Route::group(['middleware' => ['web']], function(){
    Route::namespace('Brackets\AdminTranslations\Http\Controllers\Admin')->group(function () {
        Route::get('/admin/translations',                   'TranslationsController@index');
        Route::get('/admin/translations/export',            'TranslationsController@export')->name('admin/translations/export');
        Route::post('/admin/translations/rescan',           'RescanTranslationsController@rescan');

        Route::post('/admin/translations/{translation}',    'TranslationsController@update');
    });
});

