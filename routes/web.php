<?php

Route::group(['middleware' => ['web', 'admin']], function(){
    Route::get('/admin/translation','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@index');
    Route::post('/admin/translation/{translation}','\Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController@update');
});

