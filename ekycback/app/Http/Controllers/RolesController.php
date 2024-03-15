<?php

namespace App\Http\Controllers;

use App\Models\KicImportBusiness;
use App\Models\KicImportDepartment;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\Menurole;
use App\Models\ObjectModel;
use App\Models\RoleHierarchy;

class RolesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {

        /*$this->middleware('permission:role-view');
        $this->middleware('permission:role-view', ['only' => ['show']]);
        $this->middleware('permission:role-create', ['only' => ['store']]);
        $this->middleware('permission:role-edit', ['only' => ['update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);*/

        // $this->middleware('permission:role-view|role-create|role-edit|role-delete', ['only' => ['index', 'show']]);
        // $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        // $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    /*public function getModels($path){
        $out = [];
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') continue;
            $filename = $path . '/' . $result;
            if (is_dir($filename)) {
                $out = array_merge($out, $this->getModels($filename));
            }else{
                //$out[] = substr($filename,0,-4);
                if( strpos( $filename, '.git' ) == false) {
                    $out[] = substr($filename, 56, -4);
                }
            }
        }
        return $out;
    }*/
    public function index(Request $request)
    {

        /* $tables_in_db = \DB::select('SHOW TABLES');
         $db = "Tables_in_".env('DB_DATABASE');
         $tables = [];
         foreach($tables_in_db as $table){
             $tables[] = $table->{$db};
         }
         print_r($tables);
     echo $path = base_path('Modules/ClientApp/Entities');//"Modules\\ClientApp\\";
 //die;

     dd($this->getModels($path));*/

        $roles = Role::orderBy('id', 'DESC')->get();
        if ($roles) {
            //$role = Role::create(['name' => 'fsdfsfdsf', 'guard_name' => 'api']);
            return response()->json([
                "code" => 200,
                "roles" => $roles
            ]);
        }
        return response()->json(["code" => 400]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $roleName = Role::pluck('name')->all();
        $oldString = "roles: [" . "'" . implode("', '", $roleName) . "'" . "]";
        /* echo "<pre>";
         var_dump($request->get('apppermissions'));
         echo "</pre>";
         die;*/
        $role = new Role();
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->guard_name = 'api';
        $role->is_main = $request->input('is_main');
        $role->is_full_access = $request->input('is_full_access');
        //$role = Role::save(['name' => $request->input('name'), 'guard_name' => 'api']);
        if ($role->save()) {

            $this->storeWorkBehalf($request->get('work_on_behalf'), $role->id);

            if (count($request->get('apppermissions')) > 0) {
                foreach ($request->get('apppermissions') as $key => $apps) {
                    if (is_countable($apps) && count($apps) > 0) {
                        //echo $key;
                        foreach ($apps as $app) {
                            $getObjectName = DB::select(DB::raw("SELECT * FROM `object_model` WHERE name  = '$app'"));
                            if ($getObjectName) {
                                $prctype = DB::table("app_objects")->insert(
                                    [
                                        'app_id' => $key,
                                        //'name' => $request->date_from[0],
                                        'object_id' => $getObjectName[0]->id,
                                        'role' => $role->name,
                                        'role_id' => $role->id,
                                    ]
                                );
                            }
                        }
                        //var_dump($apps);
                    }
                }
            }
            $role->syncPermissions($request->input('permission'));
        }
        return response()->json([
            "code" => 200,
            "msg" => "data inserted successfully"
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::find($id);
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        $getappspermission = DB::select(DB::raw("SELECT * FROM app_objects where role_id= $id"));

        $values = [];
        foreach ($getappspermission as $getapps) {
            $getobject = DB::select(DB::raw("SELECT * FROM object_model where id= $getapps->object_id"));
            $values[$getapps->app_id][] = $getobject[0]->name;
        }

        if ($role) {

            $work_on_behalf = DB::select(DB::raw("SELECT sector_id, department_id, business_id, isWhat FROM role_work_on_behalf_sectors where role_id= $id"));

            $workvalues = [];
            foreach ($work_on_behalf as $behalf) {
                // if ($behalf->isWhat == '2') {
                //     $workvalues[0] = $behalf->business_id;
                // } else if ($behalf->isWhat == '1') {
                //     $workvalues[0] = $behalf->department_id;
                // } else if ($behalf->isWhat == '0') {
                //     $workvalues[0] = $behalf->sector_id;
                // }

                if ($behalf->isWhat == '2') {
                    $workvalues[] = $behalf->business_id;
                } else if ($behalf->isWhat == '1') {
                    if (!in_array($behalf->department_id, $workvalues)) {
                        $workvalues[] = $behalf->department_id;
                    }
                } else if ($behalf->isWhat == '0') {
                    if (!in_array($behalf->sector_id, $workvalues)) {
                        $workvalues[] = $behalf->sector_id;
                    }
                }

                //$getobject = \DB::select(\DB::raw("SELECT * FROM object_model where id= $getapps->object_id"));

            }

            $permission = Permission::whereIn('id', $rolePermissions)->pluck('name')->all();
            return response()->json([
                "code" => 200,
                "data" => $role,
                "permissionId" => array_values($rolePermissions),
                "permissionVal" => $permission,
                "apppermissions" => $values,
                "work_on_behalf" => ($workvalues),
            ]);
        }

        return response()->json([
            "code" => 404,
            "msg" => "data not found"
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    function replace_string_in_file($filename, $string_to_replace, $replace_with)
    {
        $content = file_get_contents($filename);
        $content_chunks = explode($string_to_replace, $content);
        $content = implode($replace_with, $content_chunks);
        file_put_contents($filename, $content);
    }


    public function update(Request $request, $id)
    {
        $roleName = Role::pluck('name')->all();
        $oldString = "roles: [" . "'" . implode("', '", $roleName) . "'" . "]";

        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->is_main = $request->input('is_main');
        $role->is_full_access = $request->input('is_full_access');
        //$role->work_on_behalf = $request->input('work_on_behalf');

        if ($role->save()) {
            if (count($request->get('apppermissions')) > 0) {
                $prctype = DB::table("app_objects")->Where('role_id', $id)->delete();
                foreach ($request->get('apppermissions') as $key => $apps) {
                    if (is_countable($apps) && count($apps) > 0) {
                        //echo $key;
                        foreach ($apps as $app) {
                            $getObjectName = DB::select(DB::raw("SELECT * FROM `object_model` WHERE name  = '$app'"));
                            if ($getObjectName) {
                                $prctype = DB::table("app_objects")->insert(
                                    [
                                        'app_id' => $key,
                                        //'name' => $request->date_from[0],
                                        'object_id' => $getObjectName[0]->id,
                                        'role' => $role->name,
                                        'role_id' => $role->id,
                                    ]
                                );
                            }
                        }
                        //var_dump($apps);
                    }
                }
            }

            $this->storeWorkBehalf($request->get('work_on_behalf'), $role->id);

            if ($id == 1) {
                $getPer = Permission::where('name', 'LIKE', '%role%')->pluck('id')->all();
                $getObjectName = DB::delete("delete from role_has_permissions where permission_id NOT IN (" . implode(",", $getPer) . ") and role_id='" . $id . "'");
            }
            $role->syncPermissions($request->input('permission'));
            return response()->json([
                "code" => 200,
                "msg" => "data updated successfully"
            ]);
        }


        return response()->json(["code" => 400]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $role = Role::find($id);
        /*if (\DB::table("roles")->where('id', $id)->delete()) {
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }*/
        if ($role) {
            $status = ($role->status == '1') ? 'inactive' : 'active';
            $statusActive = ($role->status == '1') ? '0' : '1';
            $updates["status"] = $statusActive;
            $role->update($updates);
            return response()->json([
                "code" => 200,
                "msg" => "$status the record"
            ]);
        }
        return response()->json([
            "code" => 400,
            "msg" => "error deleting the data"
        ]);
    }

    public function getPermissions(Request $request)
    {
        $permissions = Permission::get();
        if ($permissions) {
            //$role = Role::create(['name' => 'fsdfsfdsf', 'guard_name' => 'api']);
            return response()->json([
                "code" => 200,
                "permissions" => $permissions
            ]);
        }
        return response()->json(["code" => 400]);
    }

    public function getRoleObject(Request $request)
    {
        $objectModel = ObjectModel::whereNotIn('name', ['forms'])->where('status', '1')->get();
        $new = [];
        foreach ($objectModel as $key => $value) {

            //var_dump($value->name);
            $permissions = Permission::Where('name', 'like', '' . $value->name . '%')->where('status', '1')->get();
            $new[$value->name] = $permissions;
            $new[$value->name]['en'] = $value->en_description;
            $new[$value->name]['ar'] = $value->ar_description;
        }

        $getApps = DB::select(DB::raw("SELECT * FROM check_roles_group where status= '1' ORDER BY sort_no ASC"));

        $roleShow = [];
        $i = 0;
        foreach ($getApps as $ro) {
            //var_dump($ro);
            $roleShow[$i]['id'] = $ro->id;
            $roleShow[$i]['name'] = $ro->name;
            $i++;
        }
        return response()->json([
            "code" => 200,
            "objectModel" => $new,
            "getApps" => $roleShow
        ]);
    }

    public function rolespermissions($perm)
    {

        $values = [];
        if ($perm == '1') {
            $getapps = DB::select(DB::raw("SELECT * FROM check_roles_group where status= '1'"));
            foreach ($getapps as $getapp) {
                $getobjects = DB::select(DB::raw("SELECT * FROM object_model"));
                foreach ($getobjects as $getobject) {
                    $values[$getapp->id][] = $getobject->name;
                }
            }
            return response()->json([
                "code" => 200,
                "apppermissions" => $values
            ]);
        } else {
            $getapps = DB::select(DB::raw("SELECT * FROM check_roles_group where status= '1'"));
            foreach ($getapps as $getapp) {
                //$getobjects = \DB::select(\DB::raw("SELECT * FROM object_model"));
                //foreach ($getobjects as $getobject) {
                $values[$getapp->id][] = '';
                //}
            }
            return response()->json([
                "code" => 200,
                "apppermissions" => $values
            ]);
        }
        return response()->json([
            "code" => 200,
            "apppermissions" => $values
        ]);
    }

    public function storeWorkBehalf($workbehalfArray, $id)
    {


        if (gettype($workbehalfArray) == 'string' || gettype($workbehalfArray) == 'integer') {
            $behalf = $workbehalfArray;
            DB::table("role_work_on_behalf_sectors")->Where('role_id', $id)->delete();
            if ($behalf != '0') {
                $business_id = 0;
                $department_id = $behalf;
                $department = KicImportDepartment::where(function ($query) use ($behalf) {
                    $query->where('SectorId', $behalf)
                        ->Orwhere('KICDeptId', $behalf);
                })->get();
                if (count($department) == 0) {
                    $department = KicImportBusiness::select('sectorid as SectorId', 'KICDeptId as department_id', 'deptid as business_id')->where(function ($query) use ($behalf) {
                        $query->where('sectorid', $behalf)
                            ->Orwhere('KICDeptId', $behalf)
                            ->Orwhere('deptid', $behalf);
                    })->get();
                    $business_id = $department[0]->business_id;
                    $department_id = $department[0]->department_id;
                }

                $sectorId = $department[0]->SectorId;
            } else {
                $sectorId = 0;
                $department_id = 0;
                $business_id = 0;
            }

            //echo $sectorId . '==' . $department_id . '==' . $business_id;
            //die;
            if ($sectorId != '0' && $sectorId == $department_id) {
                //echo '1';
                $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '!=', '')->get();
                if (count($businessArgs) > 0) {
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->sectorid,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $val->deptid,
                                'role_id' => $id,
                                'isWhat' => '0'
                            ]
                        );
                    }
                } else {
                    $businessArgs = KicImportDepartment::where('sectorid', '=', $sectorId)->where('KICDeptId', '!=', '')->get();
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->SectorId,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $business_id,
                                'role_id' => $id,
                                'isWhat' => '0'
                            ]
                        );
                    }
                }
            } else if ($sectorId != 0 && $department_id != 0 && $business_id == 0) {
                //echo '2';
                $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();

                if (count($businessArgs) > 0) {
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->sectorid,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $val->deptid,
                                'role_id' => $id,
                                'isWhat' => '1'
                            ]
                        );
                    }
                } else {
                    $businessArgs = KicImportDepartment::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->SectorId,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $business_id,
                                'role_id' => $id,
                                'isWhat' => '1'
                            ]
                        );
                    }
                }
            } else if ($sectorId != 0 && $department_id != 0 && $business_id != 0) {
                //echo '3';
                $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                    [
                        'sector_id' => $sectorId,
                        'department_id' => $department_id,
                        'business_id' => $business_id,
                        'role_id' => $id,
                        'isWhat' => '2'
                    ]
                );
            } else {
                //echo '4';
                $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                    [
                        'sector_id' => $sectorId,
                        'department_id' => $department_id,
                        'business_id' => $business_id,
                        'role_id' => $id,
                        'isWhat' => '0'
                    ]
                );
            }
        } else if (gettype($workbehalfArray) == 'array') {

            if (is_countable($workbehalfArray) && count($workbehalfArray) > 0) {

                DB::table("role_work_on_behalf_sectors")->Where('role_id', $id)->delete();
                foreach ($workbehalfArray as $key => $workbehalf) {
                    $business_id = 0;
                    $department_id = $workbehalf;
                    if ($workbehalf != '0') {

                        $department = KicImportDepartment::where(function ($query) use ($workbehalf) {
                            $query->where('SectorId', $workbehalf)
                                ->Orwhere('KICDeptId', $workbehalf);
                        })->get();
                        if (count($department) == '0') {
                            $department = KicImportBusiness::select('sectorid as SectorId', 'KICDeptId as department_id', 'deptid as business_id')->where(function ($query) use ($workbehalf) {
                                $query->where('sectorid', $workbehalf)
                                    ->Orwhere('KICDeptId', $workbehalf)
                                    ->Orwhere('deptid', $workbehalf);
                            })->get();
                            $business_id = $department[0]->business_id;
                            $department_id = $department[0]->department_id;
                        }
                        $sectorId = $department[0]->SectorId;
                    } else {
                        $sectorId = 0;
                        $department_id = 0;
                        $business_id = 0;
                    }
                    //echo $sectorId . '==' . $department_id . '==' . $business_id;
                    if ($sectorId != '0' && $sectorId == $department_id) {

                        $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '!=', '')->get();
                        if (count($businessArgs) > 0) {
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->sectorid,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $val->deptid,
                                        'role_id' => $id,
                                        'isWhat' => '0'
                                    ]
                                );
                            }
                        } else {
                            $businessArgs = KicImportDepartment::where('sectorid', '=', $sectorId)->where('KICDeptId', '!=', '')->get();
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->SectorId,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $business_id,
                                        'role_id' => $id,
                                        'isWhat' => '0'
                                    ]
                                );
                            }
                        }
                    } else if ($sectorId != 0 && $department_id != 0 && $business_id == 0) {
                        $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                        if (count($businessArgs) > 0) {
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->sectorid,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $val->deptid,
                                        'role_id' => $id,
                                        'isWhat' => '1'
                                    ]
                                );
                            }
                        } else {
                            $businessArgs = KicImportDepartment::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->SectorId,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $business_id,
                                        'role_id' => $id,
                                        'isWhat' => '1'
                                    ]
                                );
                            }
                        }
                    } else if ($sectorId != 0 && $department_id != 0 && $business_id != 0) {
                        $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $sectorId,
                                'department_id' => $department_id,
                                'business_id' => $business_id,
                                'role_id' => $id,
                                'isWhat' => '2'
                            ]
                        );
                    } else {
                        $prctype = DB::table("role_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $sectorId,
                                'department_id' => $department_id,
                                'business_id' => $business_id,
                                'role_id' => $id,
                                'isWhat' => '0'
                            ]
                        );
                    }
                }
            }
        }
    }
}
