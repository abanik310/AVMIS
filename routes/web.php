<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Login
Route::get('/login', ['as'=>'login','uses'=>'App\Http\Controllers\UserController@login']);
Route::post('/check_login', ['as'=>'check_login','uses'=>'App\Http\Controllers\UserController@handleLogin']);

//Common
Route::get('/user_data',['as'=>'user_data','uses'=>'App\Http\Controllers\AdminController@getUserData']);
Route::get('/template_list', ['as' => 'template_list', 'uses' => 'App\Http\Controllers\AdminController@getTemplate']);

Route::group(['middleware' => 'auth'], function () {
    //Dashboard
    Route::get('/admin_dashboard', ['as'=>'admin_dashboard','uses'=>'App\Http\Controllers\UserController@adminDashboard']);

    //Registration
    Route::get('/admin_registration', ['as'=>'admin_registration','uses'=>'App\Http\Controllers\AdminController@adminRegistrationView']);
    Route::post('/register', ['as'=>'register','uses'=>'App\Http\Controllers\AdminController@adminRegistration']);

    //Entry List
    Route::get('/entry_list', ['as'=>'entry_list','uses'=>'App\Http\Controllers\AdminController@entryListView']);
    Route::get('/import_list', ['as'=>'import_list','uses'=>'App\Http\Controllers\AdminController@importListView']);

    //Division, District, Thana
    Route::prefix('common-api')->group(function () {
        Route::get('DistrictName', ['as' => 'district_name', 'uses' => 'App\Http\Controllers\AdminController@DistrictName']);
        Route::get('DivisionName', ['as' => 'division_name', 'uses' => 'App\Http\Controllers\AdminController@DivisionName']);
        Route::get('ThanaName', ['as' => 'thana_name', 'uses' => 'App\Http\Controllers\AdminController@ThanaName']);
        Route::get('union/showall', ['as' => 'HRM.union.showall', 'uses' => 'App\Http\Controllers\AdminController@UnionName']);
     
    });

    //KPI Info
    Route::get('/kpi_info', ['as' => 'kpi_info', 'uses' => 'App\Http\Controllers\AdminController@kpiInfo']);
    Route::get('/kpi_list', ['as' => 'kpi_list', 'uses' => 'App\Http\Controllers\AdminController@kpiList']);
    Route::get('/create_kpi', ['as' => 'create_kpi', 'uses' => 'App\Http\Controllers\AdminController@CreateKpi']);
    Route::get('/edit_kpi', ['as' => 'edit_kpi', 'uses' => 'App\Http\Controllers\AdminController@CreateKpi']);

        
    //User
    Route::get('/user_management', ['as' => 'view_user_list', 'uses' => 'App\Http\Controllers\UserController@userManagement']);
    Route::get('/getAllUser', ['as' => 'getAllUser', 'uses' => 'App\Http\Controllers\UserController@getAllUser']);
    Route::get('/edit_user_permission/{id}', ['as' => 'edit_user_permission', 'uses' => 'App\Http\Controllers\UserController@editUserPermission']);
    Route::post('/update_permission/{id}', ['as' => 'update_permission', 'uses' => 'App\Http\Controllers\UserController@updatePermission']);
     
    //Member Info
    Route::get('/ansar_vdp_info', ['as' => 'ansar_vdp_info', 'uses' => 'App\Http\Controllers\AdminController@ansarVdpInfo']);

    Route::get('/logout', ['as'=>'logout','uses'=>'App\Http\Controllers\AdminController@logout']);
});