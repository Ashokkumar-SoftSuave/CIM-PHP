<?php

namespace App\Http\Controllers;

use App\Http\Requests\ObjectModelStore;
use App\Http\Requests\ObjectModelUpdate;
use App\Models\ObjectModel;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class ObjectModelController extends Controller
{

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $objectModel = ObjectModel::all();

        if ($objectModel) {
            return response()->json([
                "code" => 200,
                "objectModel" => $objectModel
            ]);
        }

        return response()->json(["code" => 400]);
    }


     /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return response()->json( ['status' => 'success'] );
        //return view('clientapp::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(ObjectModelStore $request)
    {
//        $TenantId = \Auth::user()->idTenants;
//
        $prctype = new ObjectModel();
        $prctype->name = $request->name;
        $prctype->name_ar = $request->name_ar;
        $prctype->en_description = $request->en_description;
        $prctype->ar_description = $request->ar_description;

        $permisson = ['create', 'edit', 'delete', 'view'];

        foreach ($permisson as $value) {
            $user = Permission::where('name', '=', $request->name.'-'.$value)->first();
            if ($user === null) {
                Permission::create(['name' => $request->name.'-'.$value, 'guard_name' => 'api']);
            }
        }

        $prctype = ObjectModel::create(
            [
                'name' => $request->name,
                'name_ar' => $request->name_ar,
                'en_description' => $request->en_description,
                'ar_description' => $request->ar_description,
            ]
        );

        if ($prctype->save()) {
            return response()->json([
                "code" => 200,
                "msg" => "data inserted successfully"
            ]);
        }

        return response()->json(["code" => 400]);
    }


    public function edit($id)
    {
        $role = ObjectModel::where('id', '=', $id)->first();
        return response()->json( ['role' => $role]);
        //return view('clientapp::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(ObjectModelUpdate $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:128'
        ]);

        $prctype = ObjectModel::Where('id', $id);
        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        unset($request['_method']);
        unset($request['id']);
        unset($request['token']);

        if ($prctype->update($request->all())) {
            return response()->json([
                "code" => 200,
                "msg" => "data updated successfully"
            ]);
        }

        return response()->json([
            "code" => 400,
            "msg" => "error updating the data"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        $prctype = ObjectModel::Where('id', $id);


        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        $request = [];
        $objData = ($prctype->first());
        //echo $objData['status'];
       // die;
        $request['status'] = ($objData['status'] == '0' ? '1' : '0');
        if ($prctype->update($request)) {
            return response()->json([
                "code" => 200,
                'status' => 'success',
                "msg" => ($request['status'] == '1') ? "activeted the record" : 'deactiveted the recode'
            ]);
        }

        return response()->json(["code" => 400]);

    }

    public function show($id)
    {
        $prctype = ObjectModel::Where('id', $id)->first();

        if ($prctype) {
            return response()->json([
                "code" => 200,
                "objectModel" => $prctype
            ]);
        }

        return response()->json([
            "code" => 404,
            "msg" => "data not found"
        ]);
    }
}
