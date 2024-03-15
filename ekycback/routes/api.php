<?php

use App\Http\Controllers\formGeneratorController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['middleware' => 'api'], function ($router) {
    Route::post('sendMail', "AuthController@sendMail");
    Route::get('generateTemplate/{id}', "AuthController@generateTemplate");
    Route::get('menu', 'MenuController@index');

    Route::post('authenticatePACI', 'KicAuthController@authenticatePACI');
    Route::post('GetResponseDetail', 'KicAuthController@GetResponseDetail');
    Route::GET('IsKICCustomer', 'KicAuthController@IsKICCustomer');
    Route::GET('GetCustomerDetail', 'KicAuthController@GetCustomerDetail');
    Route::GET('PostCustomerDetail', 'KicAuthController@PostCustomerDetail');

    
    
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::get('refresh', 'AuthController@refresh');
    Route::post('register', 'AuthController@register');

    Route::get('/loadtranslation', [formGeneratorController::class, 'loadtranslation']);
    Route::get('/loadformgenerate/{id}/{form_id}/{clientid}/{lang}/{pacitoken}/{from}/{isShowApprove}', [formGeneratorController::class, 'loadformgenerate']);
    Route::post('saveData', [formGeneratorController::class, 'store']);
    Route::post('saveSignature', [formGeneratorController::class, 'saveSignature']);
    Route::post('saveUserImage', [formGeneratorController::class, 'saveUserImage']);
    Route::post('saveDocument', [formGeneratorController::class, 'saveDocument']);
    
    Route::get('/showSignature/{customerId}', [formGeneratorController::class, 'showSignature']);
    Route::get('/showDocuments/{customerId}', [formGeneratorController::class, 'showDocuments']);
    Route::get('/approvRejectDocuments/{customerId}/{id}/{status}', [formGeneratorController::class, 'approvRejectDocuments']);

    Route::post('fetchAreas/{id}', [formGeneratorController::class, 'fetchAreas']);
    Route::post('fetchCity/{id}', [formGeneratorController::class, 'fetchCity']);
    Route::post('fetchRelatives/{id}', [formGeneratorController::class, 'fetchRelatives']);
    Route::get('/ekyc/getInfo/{id}', 'formGeneratorController@getInfo');
    //Route::post('/kicpaciauth', 'KicAuthController@authenticatePACI');

    Route::post('checkPaciAuth', [formGeneratorController::class, 'checkPaciAuth']);


    Route::resource('notes', 'NotesController');

    Route::resource('resource/{table}/resource', 'ResourceController');
    Route::post("notificationsaveImage", "NotificationController@notificationsaveImage");
    // Route::group(['middleware' => 'admin'], function ($router) {
    Route::group(['middleware' => ['auth.jwt']], function () {


        Route::get("getcustomersecdep", "NotificationController@getCustomerSecDep");
        Route::get("getSectorDeptArray/{from}", "NotificationController@getSectorDeptArray");

        Route::get("getEmailLogs", "NotificationController@getEmailLogs");
        Route::get("getSectorDept/{sectorId}/{deptId}/{businessId}/{fromScreen}", "NotificationController@getSectorDept");


        Route::post("notificationsaveAttachment", "NotificationController@notificationsaveAttachment");
        Route::delete("notificationremoveAttachment", "NotificationController@notificationremoveAttachment");

        Route::get("loadNotificationDefaultData", "NotificationController@loadNotificationDefaultData");
        Route::post("loadUserDataFromRole", "NotificationController@loadUserDataFromRole");
        Route::post("saveNotification", "NotificationController@saveNotification");
        Route::get("notificationlist", "NotificationController@index");
        Route::get("notificationDetail/{id}", "NotificationController@show");

        Route::get("notificationedit/{id}/{lang}", "NotificationController@edit");
        Route::get("notificationview/{id}/{lang}", "NotificationController@notificationview");
        Route::post("notificationactivedeactive", "NotificationController@notificationActiveDeactive");
        Route::post("notificationdelete", "NotificationController@notificationdelete");
        Route::post("sendManualMsg", "NotificationController@sendManualMsg");
        Route::get("/getNotificationArgs/{event_id}", "NotificationController@getNotificationArgs");

        Route::get('/ekyc/{type}', 'formGeneratorController@index');
        Route::resource('ekyc', 'formGeneratorController')->except(['store']);

        Route::get('/fetchBusiness/{deptid}', 'formGeneratorController@fetchBusiness');
        Route::post('/ekyccustomercif', 'formGeneratorController@ekyccustomercif');
        Route::get('/ekyccustomercif/{customerId}', 'formGeneratorController@getEkycCustomerCif');




        Route::get('/ekyc/{id}/{form_id}/{clientid}/{lang}/{pacitoken}/{from}/{isShowApprove}', 'formGeneratorController@loadformgenerate');

        Route::post('/ekyc/section/{id}', 'formGeneratorController@loadSectionFields');
        Route::post('/ekyc/section/{id}/{sectionId}', 'formGeneratorController@saveSectionFields');
        Route::post('/ekyc/section/{formId}/{customerId}/{status}', 'formGeneratorController@updateKyc');
        Route::post('/checkCivilIdExist/{civilId}', 'formGeneratorController@checkCivilIdExist');
        Route::post('/ekycChangeStatus/{civilId}/{status}', 'formGeneratorController@ekycChangeStatus');


        //Route::post('saveData', 'formGeneratorController@store');

        Route::resource('mail',        'MailController');
        Route::get('prepareSend/{id}', 'MailController@prepareSend')->name('prepareSend');
        Route::post('mailSend/{id}',   'MailController@send')->name('mailSend');

        Route::resource('bread',  'BreadController');   //create BREAD (resource)

        Route::resource('users', 'UsersController')->except(['create', 'store']);
        Route::get("/auth/{id}", "HomeController@authUser");
        Route::get("/getApps", "UsersController@getApps");
        //Route::resource('ekyc', 'formGeneratorController')->except(['create', 'edit', 'store']);

        Route::prefix('menu/menu')->group(function () {
            Route::get('/',         'MenuEditController@index')->name('menu.menu.index');
            Route::get('/create',   'MenuEditController@create')->name('menu.menu.create');
            Route::post('/store',   'MenuEditController@store')->name('menu.menu.store');
            Route::get('/edit',     'MenuEditController@edit')->name('menu.menu.edit');
            Route::post('/update',  'MenuEditController@update')->name('menu.menu.update');
            Route::get('/delete',   'MenuEditController@delete')->name('menu.menu.delete');
        });
        Route::prefix('menu/element')->group(function () {
            Route::get('/',             'MenuElementController@index')->name('menu.index');
            Route::get('/move-up',      'MenuElementController@moveUp')->name('menu.up');
            Route::get('/move-down',    'MenuElementController@moveDown')->name('menu.down');
            Route::get('/create',       'MenuElementController@create')->name('menu.create');
            Route::post('/store',       'MenuElementController@store')->name('menu.store');
            Route::get('/get-parents',  'MenuElementController@getParents');
            Route::get('/edit',         'MenuElementController@edit')->name('menu.edit');
            Route::post('/update',      'MenuElementController@update')->name('menu.update');
            Route::get('/show',         'MenuElementController@show')->name('menu.show');
            Route::get('/delete',       'MenuElementController@delete')->name('menu.delete');
        });
        Route::prefix('media')->group(function ($router) {
            Route::get('/',                 'MediaController@index')->name('media.folder.index');
            Route::get('/folder/store',     'MediaController@folderAdd')->name('media.folder.add');
            Route::post('/folder/update',   'MediaController@folderUpdate')->name('media.folder.update');
            Route::get('/folder',           'MediaController@folder')->name('media.folder');
            Route::post('/folder/move',     'MediaController@folderMove')->name('media.folder.move');
            Route::post('/folder/delete',   'MediaController@folderDelete')->name('media.folder.delete');;

            Route::post('/file/store',      'MediaController@fileAdd')->name('media.file.add');
            Route::get('/file',             'MediaController@file');
            Route::post('/file/delete',     'MediaController@fileDelete')->name('media.file.delete');
            Route::post('/file/update',     'MediaController@fileUpdate')->name('media.file.update');
            Route::post('/file/move',       'MediaController@fileMove')->name('media.file.move');
            Route::post('/file/cropp',      'MediaController@cropp');
            Route::get('/file/copy',        'MediaController@fileCopy')->name('media.file.copy');

            Route::get('/file/download',    'MediaController@fileDownload');
        });

        Route::resource('roles',        'RolesController');
        Route::resource('permissions',        'ObjectModelController');
        Route::get('/roles/move/move-up',      'RolesController@moveUp')->name('roles.up');
        Route::get('/roles/move/move-down',    'RolesController@moveDown')->name('roles.down');
        Route::get("rolesObject", "RolesController@getRoleObject");

        Route::get("tables", "translationController@index");
        Route::get("gettablecolumns/{tablename}", "translationController@gettablecolumns");
        Route::post("Translations", "translationController@store");
        Route::get("loadtranslations", "translationController@loadtranslations");
        Route::get("translationdatabyId/{id}", "translationController@translationdatabyId");
        Route::get("gettranslations", "translationController@gettranslations");
        Route::delete("translationdatadelete/{id}", "translationController@destroy");

        Route::resource('department',        'KicDepartmentController');
        Route::resource('position',        'KicDepartmentPositionController');
        Route::resource('reportcategory',        'KicReportsCategoryController');

        Route::get('reportUploads/default',        'KicReportsUploadController@fetchDefault');
        Route::get('loadReportsByCategory/{id}',        'KicReportsUploadController@loadReportsByCategory');
        Route::get('getReportSetting/{id}',        'KicReportsUploadController@getReportSetting');

        Route::post('reportUploads/save',        'KicReportsUploadController@UploadBox');
        Route::post('reportUploads/remove',        'KicReportsUploadController@RemoveBox');

        Route::resource('reportUploads',        'KicReportsUploadController');


        Route::resource('reportManagement',        'KicReportsManagementController');

        Route::get("departmentpositionLink/{prc_id}", "KicDepartmentController@loadLinkList");
        Route::get("departmentpositionLinkEdit/{id}", "KicDepartmentController@loadLinkEdit");
        Route::post("departmentpositionLink", "KicDepartmentController@storeLinkData");
        Route::put("departmentpositionLink/{id}", "KicDepartmentController@update1");
        Route::delete("departmentpositionLink/{id}", "KicDepartmentController@destroyLink");

        /** Priority Types */
        Route::get("TaskTodos/{taskid}", "TaskTodosController@index");
        Route::post("TaskTodos", "TaskTodosController@store");
        Route::get("TaskTodos/{taskid}/{id}", "TaskTodosController@show");
        Route::put("TaskTodos/{id}", "TaskTodosController@update");
        Route::delete("TaskTodos/{todo_id}", "TaskTodosController@destroy");

        //Route::resource('TaskTodos',        'TaskTodosController');
    });
});
