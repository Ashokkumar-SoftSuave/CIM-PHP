<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->user = JWTAuth::parseToken()->authenticate() ;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function ekyc(Request $request)
    {
        $data = DB::select('select * from kic_customerinfo order by created_at desc');

        return view('ekyc', ["content" => $data]);

    }

    public function ekycedit(Request $request, $id)
    {
        echo $id;
        die;
    }


    public function authUser(Request $request, $id)
    {

        if ($id != 'null') {
            $vals = (explode("-", $id));
            $id = $vals[1];
        }
        $url = explode("/api/", url()->current());
        $this->user->url = $url[0];

        if ($this->user) {
            //$userType = TenantUserType::find($this->user->user_type);
            //$this->user->roles = $userType->name;
            $this->user->roles = auth()->user()->getRoleNames();
        }

        $userPremissions = [];

        $allPermissions = [];
        $rolesMain = [];
        $rolesSec = [];
        foreach (auth()->user()->roles as $role) {
            if($role->is_main == 1) {
                $rolesMain[$role->name] = $role->name;
                $rolesSec[$role->name] = $role->name;
            } else {
                $rolesSec[$role->name] = $role->name;
            }
        }
        sort($rolesMain);
        sort($rolesSec);

        $sqlAdd = '';
        if(count($rolesMain) > 0 ) {
            //$sqlAdd .= " and obj.role='".$rolesMain[0]."'";
            $sqlAdd .= " and obj.role='".auth()->user()->currentRole."'";
        } else {
            //$sqlAdd .= " and obj.role='".$rolesSec[0]."'";
            $sqlAdd .= " and obj.role='".auth()->user()->currentRole."'";
        }
        $checkRoles = DB::select(DB::raw("SELECT app.id, model.name FROM check_roles_group app INNER JOIN app_objects obj on obj.app_id=app.id INNER JOIN object_model model on model.id= obj.object_id WHERE app.id=$id $sqlAdd"));
        //var_dump($checkRoles);

        $permissions = Permission::all();
        if ($permissions) {
            //$userGroupPremissions = [];
            foreach (Permission::all() as $permission) {
                if (Auth::user()->can($permission->name)) {

                    $allPermissions[] = $permission->name;
                    if ($checkRoles) {
                        foreach ($checkRoles as $permissionGrp) {
                            //echo $permission->name."===".$permissionGrp->routes.PHP_EOL;
                            if (strpos($permission->name, $permissionGrp->name) !== false) {
                                //echo 'inn';
                                //$userGroupPremissions[] = $permissionGrp->routes;
                                $userPremissions[] = $permission->name;
                            }
                        }
                    }
                }
            }
        }
        $this->user->allPermissions = array_values(array_unique($userPremissions));
        $this->user->totalpermissions = $allPermissions;

        return response()->json([
            "code" => 200,
            "data" => $this->user
        ]);
    }
}
