<?php

namespace App\Http\Controllers;

use App\Models\KicDepartment;
use App\Models\KicDepartmentPosition;
use App\Models\KicRoleAssign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class KicDepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $department = KicDepartment::all();

        if ($department) {
            $position = KicDepartmentPosition::all();
            $roles = Role::all();
            return response()->json([
                "code" => 200,
                "department" => $department,
                "position" => $position,
                "roles" => $roles
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
    public function show(KicDepartment $dept, $id)
    {
        $fiscalyear = KicDepartment::Where('id', $id)->first();

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
            $prctype = KicDepartment::create($request->all());
            //echo 'ddd';

            if ($prctype->save()) {
                return response()->json([
                    "code" => 200,
                    "msg" => "data inserted successfully"
                ]);
            }
        } else {
            $prctype = KicDepartment::Where('id', $request->id);
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
        $prctype = KicDepartment::Where('id', $id);

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


    /**
     * Code for create link for
     * Department
     * Position
     * Roles
     * Crud Opertaion
     * Created By : Bhavesh Darji
     */

    public function loadLinkList($id)
    {
        $departmentLink = DB::table('kic_role_assign')
            ->join('kic_departments', 'kic_role_assign.departmentId', '=', 'kic_departments.id')
            ->join('kic_department_positions', 'kic_role_assign.positionId', '=', 'kic_department_positions.id')
            ->join('roles', 'kic_role_assign.roleId', '=', 'roles.id')
            ->select('kic_role_assign.*', 'kic_departments.department', 'kic_departments.department_ar', 'kic_department_positions.position', 'kic_department_positions.position_ar', 'roles.name')
            ->where('kic_role_assign.departmentId', $id)
            ->get();

        if ($departmentLink) {

            return response()->json([
                "code" => 200,
                "departmentLink" => $departmentLink
            ]);
        }

        return response()->json(["code" => 400]);
    }

    public function storeLinkData(Request $request)
    {
        $depart = KicDepartment::where('id', $request->get('departmentId'))->first();
        $posit = KicDepartmentPosition::where('id', $request->get('positionId'))->first();
        $request['department'] = $depart->department;
        $request['position'] = $posit->position;

        if (empty($request->id) || !isset($request->id) || $request->id === 0) {

            unset($request['id']);
            $prctype = KicRoleAssign::create($request->all());
            //echo 'ddd';

            if ($prctype->save()) {
                return response()->json([
                    "code" => 200,
                    "msg" => "data inserted successfully"
                ]);
            }
        } else {
            $prctype = KicRoleAssign::Where('id', $request->id);
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

    /**
     * Display the specified resource.
     *
     * @param  \Modules\ClientApp\Entities\Mtp  $mtp
     * @return \Illuminate\Http\Response
     */
    public function loadLinkEdit(KicRoleAssign $dept, $id)
    {
        $fiscalyear = KicRoleAssign::Where('id', $id)->first();

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

    public function destroyLink($id)
    {
        $prctype = KicRoleAssign::Where('id', $id);

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
