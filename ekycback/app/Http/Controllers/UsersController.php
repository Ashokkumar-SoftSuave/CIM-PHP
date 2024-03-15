<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $you = auth()->user()->id;
        $users = DB::table('users')
        ->select('users.id', 'users.name', 'users.email', 'users.menuroles as roles', 'users.status', 'users.email_verified_at as registered')
        ->whereNull('deleted_at')
        ->get();
        return response()->json( compact('users', 'you') );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = DB::table('users')
        ->select('users.id', 'users.name', 'users.email', 'users.menuroles as roles', 'users.status', 'users.email_verified_at as registered')
        ->where('users.id', '=', $id)
        ->first();
        return response()->json( $user );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = DB::table('users')
        ->select('users.id', 'users.name', 'users.email', 'users.menuroles as roles', 'users.status')
        ->where('users.id', '=', $id)
        ->first();
        return response()->json( $user );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name'       => 'required|min:1|max:256',
            'email'      => 'required|email|max:256'
        ]);
        $user = User::find($id);
        $user->name       = $request->input('name');
        $user->email      = $request->input('email');
        $user->save();
        //$request->session()->flash('message', 'Successfully updated user');
        return response()->json( ['status' => 'success'] );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if($user){
            $user->delete();
        }
        return response()->json( ['status' => 'success'] );
    }

    public function getApps()
    {
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
            //$sqlAdd .= " where obj.role='".$rolesMain[0]."'";
            $sqlAdd .= " where obj.role='".auth()->user()->currentRole."' and app.status='1'";
        } else {
            //$sqlAdd .= " where obj.role='".$rolesSec[0]."'";
            $sqlAdd .= " where obj.role='".auth()->user()->currentRole."'  and app.status='1'";
        }

        $checkRolesOne = DB::select(DB::raw("SELECT app.id, app.name, app.description_en, app.description_ar, app.routes, app.role, app.sort_no, app.css_class FROM check_roles_group app INNER JOIN app_objects obj on obj.app_id=app.id INNER JOIN object_model model on model.id= obj.object_id $sqlAdd GROUP by app.id, app.name,app.description_en, app.description_ar, app.routes, app.role, app.sort_no, app.css_class order by app.sort_no ASC"));

        $checkRolesTwo = DB::select(DB::raw("SELECT * FROM check_roles_group ORDER BY sort_no ASC"));
        if ($checkRolesOne) {
            return response()->json([
                "code" => 200,
                "data" => $checkRolesOne
            ]);
        } else if ($checkRolesTwo) {
            return response()->json([
                "code" => 200,
                "data" => $checkRolesTwo
            ]);
        }

        return response()->json([
            "code" => 400,
            "msg" => "data not found"
        ]);
    }
}
