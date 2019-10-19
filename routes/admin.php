<?php

Route::group(['middleware' => ['admin.api']], function () {

    Route::get('/member', 'MemberController@_list');
    Route::post('/member', 'MemberController@create');
    Route::put('/member/{id}', 'MemberController@update')->where('id', '[0-9]+');
    Route::put('/member/{id}/password', 'MemberController@setPassword')->where('id', '[0-9]+');
    Route::post('/memberLogin', 'MemberController@login');

    Route::get('/role', 'RoleController@_list');
    Route::get('/role/{id}', 'RoleController@info')->where('id', '[0-9]+');
    Route::post('/role', 'RoleController@create');
    Route::put('/role/{id}/access', 'RoleController@access');
    Route::delete('/role/{id}', 'RoleController@del')->where('id', '[0-9]+');

    Route::get('/access', 'AccessController@_list');
    Route::post('/access', 'AccessController@create');
    Route::delete('/access/{id}', 'AccessController@del')->where('id', '[0-9]+');
});


