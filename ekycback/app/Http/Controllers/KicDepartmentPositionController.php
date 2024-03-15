<?php

namespace App\Http\Controllers;

use App\Models\KicDepartmentPosition;
use Illuminate\Http\Request;

class KicDepartmentPositionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $position = KicDepartmentPosition::all();

        if ($position) {
            return response()->json([
                "code" => 200,
                "position" => $position
            ]);
        }

        return response()->json(["code" => 400]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Modules\ClientApp\Entities\Mtp  $mtp
     * @return \Illuminate\Http\Response
     */
    public function show(KicDepartmentPosition $dept, $id)
    {
        $fiscalyear = KicDepartmentPosition::Where('id', $id)->first();

        if ($fiscalyear) {
            return response()->json([
                "code" => 200,
                "data" => $fiscalyear
            ]);
        }

        return response()->json([
            "code" => 404,
            "msg" => "data not found"
        ]);
    }

    public function store(Request $request)
    {

        if (empty($request->id) || !isset($request->id) || $request->id === 0) {

            unset($request['id']);
            $prctype = KicDepartmentPosition::create($request->all());
            //echo 'ddd';

            if ($prctype->save()) {
                return response()->json([
                    "code" => 200,
                    "msg" => "data inserted successfully"
                ]);
            }
        } else {
            $prctype = KicDepartmentPosition::Where('id', $request->id);
            if (!$prctype) {
                return response()->json([
                    "code" => 404,
                    "msg" => "data not found"
                ]);
            }

            if ($prctype->update($request->all())) {
                return response()->json([
                    "code" => 200,
                    "msg" => "data updated successfully"
                ]);
            }
        }
        return response()->json(["code" => 400, 'msg' => 'Same Data So you can not update']);
    }

    public function destroy($id)
    {
        $prctype = KicDepartmentPosition::Where('id', $id);

        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        if ($prctype->delete()) {
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }

        return response()->json(["code" => 400]);
    }
}
