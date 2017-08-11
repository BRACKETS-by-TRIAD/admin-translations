<?php

Route::group(['middleware' => 'bindings'], function(){
    Route::get('/admin/translation','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@index');
    Route::post('/admin/translation/{translation}','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@update');
});

