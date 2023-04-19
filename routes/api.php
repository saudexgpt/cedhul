<?php

use App\Http\Controllers\ArticlesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('set-admin-role', [Controller::class, 'setAdminRole']);

Route::get('get-articles', [ArticlesController::class, 'index']);
Route::get('article/read/{article}', [ArticlesController::class, 'read']);

Route::post('save-download-survey', [ArticlesController::class, 'saveDownloadSurvey']);

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('confirm-registration/{hash}', [AuthController::class, 'confirmRegistration']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('user', [AuthController::class, 'user']); //->middleware('permission:read-users');
    });
});


//////////////////////////////// APP APIS //////////////////////////////////////////////
Route::group(['middleware' => 'auth:sanctum'], function () {


    Route::get('user-notifications', [UsersController::class, 'userNotifications']);
    Route::get('notification/mark-as-read', [UsersController::class, 'markNotificationAsRead']);

    // Access Control Roles & Permission
    Route::group(['prefix' => 'acl'], function () {
        Route::get('roles/index', [RolesController::class, 'index']);
        Route::post('roles/save', [RolesController::class, 'store']);
        Route::put('roles/update/{role}', [RolesController::class, 'update']);
        Route::post('roles/assign', [RolesController::class, 'assignRoles']);


        Route::get('permissions/index', [PermissionsController::class, 'index']);
        Route::post('permissions/assign-user', [PermissionsController::class, 'assignUserPermissions']);
        Route::post('permissions/assign-role', [PermissionsController::class, 'assignRolePermissions']);
    });
    Route::group(['prefix' => 'articles'], function () {
        Route::get('all', [ArticlesController::class, 'allArticles']);
        Route::get('downlaods', [ArticlesController::class, 'downloads']);

        Route::get('my-articles', [ArticlesController::class, 'myArticles']);
        Route::post('store', [ArticlesController::class, 'store']);

        Route::put('approve/{article}', [ArticlesController::class, 'approve']);
        Route::put('publish/{article}', [ArticlesController::class, 'publish']);
        Route::delete('destroy/{article}', [ArticlesController::class, 'destroy']);
    });
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardsController::class, 'index']);
    });
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UsersController::class, 'index']);
        Route::put('perfom-action/{user}', [UsersController::class, 'performAction']);
    });
});
