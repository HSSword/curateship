<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/login/ajax',[
    'as'   => 'login.ajax',
    'uses' => 'Auth\LoginController@ajaxShowForm'
]);

Route::get('/logout', 'Auth\LoginController@logout');

Route::get('/register/ajax',[
  'as'   => 'register.ajax',
  'uses' => 'Auth\RegisterController@ajaxShowForm'
]);

Route::get('/resetpassword',[
  'as'   => 'password.reset.ajax',
  'uses' => 'Auth\ForgotPasswordController@ajaxShowForm'
]);

Route::middleware('auth', 'role:admin')->group(function(){

  Route::prefix('admin')->group(function(){
    Route::get('users', 'UsersController@index');
    Route::get('users/add', 'UsersController@create');
    Route::post('users/store', 'UsersController@store');
    Route::get('users/edit/{id}', 'UsersController@edit');
    Route::post('users/update/{id}', 'UsersController@update');
    Route::get('users/suspend/{id}', 'UsersController@suspend');
    Route::get('users/activate/{id}', 'UsersController@activate');
    Route::get('users/trash/{id}', 'UsersController@trash');
    Route::get('users/delete/{id}', 'UsersController@destroy');
    Route::get('users/delete/{id}', 'UsersController@destroy');
    Route::get('users/empty-trash', 'UsersController@emptyTrash');
    Route::post('users/bulk-suspend', 'UsersController@bulkSuspend');
    Route::post('users/bulk-delete', 'UsersController@bulkDelete');
  });

});