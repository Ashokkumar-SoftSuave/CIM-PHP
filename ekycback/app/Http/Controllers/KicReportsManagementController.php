<?php

namespace App\Http\Controllers;

use App\Http\Requests\KicReportCategoryStore;
use App\Http\Requests\KicReportCategoryUpdate;
use App\Http\Requests\KicReportManagementStore;
use App\Http\Requests\KicReportManagementUpdate;
use App\Models\KicReportsCategory;
use App\Models\KicReportSetting;
use App\Models\KicReportsManagement;
use App\Models\KicReportsUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class KicReportsManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $getAccess = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where `role_id`='" . auth()->user()->roles->first()->id . "'"));

        $getAccessCategory = [];
        if ($getAccess[0]->sector_id != 0) {

            // $getAccessCategory = DB::select(DB::raw("SELECT GROUP_CONCAT(sector_id) as sector_id, GROUP_CONCAT(department_id) as department_id FROM `notif_def_work_on_behalf_sectors` where `sector_id`='" . $getAccess[0]->sector_id . "'"));

            // $arrayCheckSec = array_merge(explode(",", $getAccessCategory[0]->sector_id), [0]);
            // $arrayCheckDept = [0, $getAccess[0]->department_id];

            // $position = KicReportsManagement::join('kic_category_work_on_behalf_sectors', function ($join) use ($arrayCheckSec, $arrayCheckDept, $getAccess) {

            //     $join->on('kic_reports_management.categoryId', '=', 'kic_category_work_on_behalf_sectors.categoryId')
            //         ->whereIn('kic_category_work_on_behalf_sectors.sector_id', $arrayCheckSec);
            //     if ($getAccess[0]->department_id != $getAccess[0]->sector_id) {
            //         $join->whereIn('kic_category_work_on_behalf_sectors.department_id', $arrayCheckDept);
            //     }
            // })->with('category', 'reportsetting')->get();


            $position = KicReportsManagement::join('kic_category_work_on_behalf_sectors', function ($join) {
                $join->on('kic_reports_management.categoryId', '=', 'kic_category_work_on_behalf_sectors.categoryId');
            })->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_category_work_on_behalf_sectors.sector_id')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_category_work_on_behalf_sectors.department_id')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_category_work_on_behalf_sectors.business_id');
            })->with('category', 'reportsetting')->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->select(DB::raw('DISTINCT kic_reports_management.*'))->get();


            // $category = KicReportsCategory::join('kic_category_work_on_behalf_sectors', function ($join) use ($arrayCheckSec, $arrayCheckDept, $getAccess) {

            //     // $join->on('kic_reports_category.id', '=', 'kic_category_work_on_behalf_sectors.categoryId')
            //     //     ->whereIn('kic_category_work_on_behalf_sectors.sector_id', $arrayCheck)
            //     //     ->whereIn('kic_category_work_on_behalf_sectors.department_id', $arrayCheck);

            //     $join->on('kic_reports_category.id', '=', 'kic_category_work_on_behalf_sectors.categoryId')
            //         ->whereIn('kic_category_work_on_behalf_sectors.sector_id', $arrayCheckSec);
            //     if ($getAccess[0]->department_id != $getAccess[0]->sector_id) {
            //         $join->whereIn('kic_category_work_on_behalf_sectors.department_id', $arrayCheckDept);
            //     }
            // })->select(DB::raw('DISTINCT kic_reports_category.*'))->get();

            $category = KicReportsCategory::join('kic_category_work_on_behalf_sectors', function ($join) {
                $join->on('kic_reports_category.id', '=', 'kic_category_work_on_behalf_sectors.categoryId');
            })->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_category_work_on_behalf_sectors.sector_id')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_category_work_on_behalf_sectors.department_id')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_category_work_on_behalf_sectors.business_id');
            })->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->select(DB::raw('DISTINCT kic_reports_category.*'))->get();

        } else {
            $position = KicReportsManagement::with('category', 'reportsetting')->get();
            $category = KicReportsCategory::all();
            //        all()->with('category');
        }


        $settings = KicReportSetting::all();

        if ($position) {
            return response()->json([
                "code" => 200,
                "position" => $position,
                "category" => $category,
                "settings" => $settings
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
    public function show(KicReportsManagement $dept, $id)
    {
        $fiscalyear = KicReportsManagement::Where('id', $id)->first();

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

    public function store(KicReportManagementStore $request)
    {

        if (empty($request->id) || !isset($request->id) || $request->id === 0) {

            unset($request['id']);
            $prctype = KicReportsManagement::create($request->all());
            //echo 'ddd';

            if ($prctype->save()) {
                return response()->json([
                    "code" => 200,
                    "msg" => "data inserted successfully"
                ]);
            }
        } else {
            $prctype = KicReportsManagement::Where('id', $request->id);
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
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(KicReportManagementUpdate $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:128'
        ]);

        $prctype = KicReportsManagement::Where('id', $id);
        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        unset($request['id']);

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


    public function destroy($id)
    {
        $prctype = KicReportsManagement::Where('id', $id)->first();

        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        $categoryId = $prctype->categoryId;
        $reportId = $prctype->id;
        if ($prctype->delete()) {
            $reports = KicReportsUpload::Where('categoryId', $categoryId)->where('reportId', $reportId)->get();
            foreach ($reports as $ke => $vl) {

                File::delete(public_path($vl->filename));
                KicReportsUpload::Where('id', $vl->id)->delete();
            }
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }

        return response()->json(["code" => 400]);
    }
}
