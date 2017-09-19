<?php

Route::group(['middleware' => ['web']], function(){
    Route::get('/admin/translations',                   '\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@index');
    Route::post('/admin/translations/rescan',           '\Brackets\AdminTranslations\Http\Controllers\Admin\RescanTranslationsController@rescan');

    Route::post('/admin/translations/{translation}',    '\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@update');
});

