<?php

Route::group(['middleware' => 'bindings'], function(){
    Route::get('/admin/translations','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@index');
    Route::post('/admin/translations/{languageLine}','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@update');
});

