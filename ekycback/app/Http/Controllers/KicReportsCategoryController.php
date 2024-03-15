<?php

namespace App\Http\Controllers;

use App\Http\Requests\KicReportCategoryStore;
use App\Http\Requests\KicReportCategoryUpdate;
use App\Models\KicImportBusiness;
use App\Models\KicImportDepartment;
use App\Models\KicReportsCategory;
use App\Models\KicReportsManagement;
use App\Models\KicReportsUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class KicReportsCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $getAccess = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where `role_id`='" . auth()->user()->roles->first()->id . "'"));
        if ($getAccess[0]->sector_id != 0) {
            $position = KicReportsCategory::join('kic_category_work_on_behalf_sectors', function ($join) {
                $join->on('kic_reports_category.id', '=', 'kic_category_work_on_behalf_sectors.categoryId');
            })->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_category_work_on_behalf_sectors.sector_id')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_category_work_on_behalf_sectors.department_id')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_category_work_on_behalf_sectors.business_id');
            })->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->select(DB::raw('DISTINCT kic_reports_category.*'))->get();
        } else {
            $position = KicReportsCategory::all();
        }



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
    public function show(KicReportsCategory $dept, $id)
    {
        $fiscalyear = KicReportsCategory::Where('id', $id)->first();

        if ($fiscalyear) {
            $work_on_behalf = DB::select(DB::raw("SELECT sector_id, department_id, business_id, isWhat FROM kic_category_work_on_behalf_sectors where categoryId= $id"));

            $workvalues = [];
            foreach ($work_on_behalf as $behalf) {
                //$getobject = \DB::select(\DB::raw("SELECT * FROM object_model where id= $getapps->object_id"));
                //$workvalues[] = $behalf->department_id;
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
            }
            $fiscalyear->work_on_behalf = $workvalues;

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

    public function store(KicReportCategoryStore $request)
    {

        if (empty($request->id) || !isset($request->id) || $request->id === 0) {

            unset($request['id']);
            $prctype = KicReportsCategory::create($request->all());
            //echo 'ddd';

            if ($prctype->save()) {
                if (!File::exists(public_path('reports/' . str_replace(' ', '', $request->name)))) {
                    File::makeDirectory(public_path('reports/' . str_replace(' ', '', $request->name)), $mode = 0777, true, true);
                }

                $this->storeWorkBehalf($request->get('work_on_behalf'), $prctype->id);

                return response()->json([
                    "code" => 200,
                    "msg" => "data inserted successfully"
                ]);
            }
        } else {
            $prctype = KicReportsCategory::Where('id', $request->id);
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
    public function update(KicReportCategoryUpdate $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:128'
        ]);

        $prctype = KicReportsCategory::Where('id', $id)->first();

        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        $beforeName = str_replace(' ', '', $prctype->name);
        $new = str_replace(' ', '', $request->input('name'));
        unset($request['id']);

        if ($prctype->update($request->all())) {
            if ($beforeName != $new) {
                if (File::exists(public_path('reports/' . $beforeName))) {
                    File::moveDirectory(public_path('reports/' . $beforeName), public_path('reports/' . $new));
                }
                // if (!File::exists(public_path('reports/' . $new))) {
                //     File::makeDirectory(public_path('reports/' . $new), $mode = 0777, true, true);
                // }
            } else {
                if (!File::exists(public_path('reports/' . $new))) {
                    File::makeDirectory(public_path('reports/' . $new), $mode = 0777, true, true);
                }
            }
            $this->storeWorkBehalf($request->get('work_on_behalf'), $id);
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
        $prctype = KicReportsCategory::Where('id', $id)->first();

        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        $nameBefore = str_replace(" ", "", $prctype->name);

        $catId = $prctype->id;
        if ($prctype->delete()) {
            KicReportsManagement::Where('categoryId', $catId)->delete();
            KicReportsUpload::Where('categoryId', $catId)->delete();
            if (File::exists(public_path('reports/' . $nameBefore))) {
                File::deleteDirectory(public_path('reports/' . $nameBefore));
            }
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }

        return response()->json(["code" => 400]);
    }

    // public function storeWorkBehalf($workbehalfArray, $id)
    // {
    //     if (gettype($workbehalfArray) == 'string' || gettype($workbehalfArray) == 'integer') {
    //         $behalf = $workbehalfArray;
    //         DB::table("kic_category_work_on_behalf_sectors")->Where('categoryId', $id)->delete();
    //         if ($behalf != '0') {
    //             $department = KicImportDepartment::where(function ($query) use ($behalf) {
    //                 $query->where('SectorId', $behalf)
    //                     ->Orwhere('KICDeptId', $behalf);
    //             })->get();
    //             if (count($department) == 0) {
    //                 $department = KicImportBusiness::where(function ($query) use ($behalf) {
    //                     $query->where('SectorId', $behalf)
    //                         ->Orwhere('KICDeptId', $behalf);
    //                 })->get();
    //             }

    //             $sectorId = $department[0]->SectorId;
    //         } else {
    //             $sectorId = 0;
    //         }
    //         $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
    //             [
    //                 'sector_id' => $sectorId,
    //                 'department_id' => $behalf,
    //                 'categoryId' => $id,
    //             ]
    //         );
    //     } else if (gettype($workbehalfArray) == 'array') {

    //         if (is_countable($workbehalfArray) && count($workbehalfArray) > 0) {

    //             DB::table("kic_category_work_on_behalf_sectors")->Where('categoryId', $id)->delete();
    //             foreach ($workbehalfArray as $key => $workbehalf) {
    //                 if ($workbehalf != '0') {

    //                     $department = KicImportDepartment::where(function ($query) use ($workbehalf) {
    //                         $query->where('SectorId', $workbehalf)
    //                             ->Orwhere('KICDeptId', $workbehalf);
    //                     })->get();
    //                     if (count($department) == '0') {
    //                         $department = KicImportBusiness::select('sectorid as SectorId')->where(function ($query) use ($workbehalf) {
    //                             $query->where('sectorid', $workbehalf)
    //                                 ->Orwhere('deptid', $workbehalf);
    //                         })->get();
    //                     }
    //                     $sectorId = $department[0]->SectorId;
    //                 } else {
    //                     $sectorId = 0;
    //                 }
    //                 $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
    //                     [
    //                         'sector_id' => $sectorId,
    //                         'department_id' => $workbehalf,
    //                         'categoryId' => $id,
    //                     ]
    //                 );
    //             }
    //         }
    //     }
    // }
    public function storeWorkBehalf($workbehalfArray, $id)
    {


        if (gettype($workbehalfArray) == 'string' || gettype($workbehalfArray) == 'integer') {
            $behalf = $workbehalfArray;
            DB::table("kic_category_work_on_behalf_sectors")->Where('categoryId', $id)->delete();
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
                foreach ($businessArgs as $key => $val) {
                    $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                        [
                            'sector_id' => $val->sectorid,
                            'department_id' => $val->KICDeptId,
                            'business_id' => $val->deptid,
                            'categoryId' => $id,
                            'isWhat' => '0'
                        ]
                    );
                }
            } else if ($sectorId != 0 && $department_id != 0 && $business_id == 0) {
                //echo '2';
                $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                if (count($businessArgs) > 0) {
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->sectorid,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $val->deptid,
                                'categoryId' => $id,
                                'isWhat' => '1'
                            ]
                        );
                    }
                } else {
                    $businessArgs = KicImportDepartment::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->SectorId,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $business_id,
                                'categoryId' => $id,
                                'isWhat' => '1'
                            ]
                        );
                    }
                }
            } else if ($sectorId != 0 && $department_id != 0 && $business_id != 0) {
                //echo '3';
                $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                    [
                        'sector_id' => $sectorId,
                        'department_id' => $department_id,
                        'business_id' => $business_id,
                        'categoryId' => $id,
                        'isWhat' => '2'
                    ]
                );
            } else {
                //echo '4';
                $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                    [
                        'sector_id' => $sectorId,
                        'department_id' => $department_id,
                        'business_id' => $business_id,
                        'categoryId' => $id,
                        'isWhat' => '0'
                    ]
                );

                $businessArgs = KicImportBusiness::whereNotNull('KICDeptId')->get();
                if (count($businessArgs) > 0) {
                    foreach ($businessArgs as $key => $val) {
                        $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $val->sectorid,
                                'department_id' => $val->KICDeptId,
                                'business_id' => $val->deptid,
                                'categoryId' => $id,
                                'isWhat' => '0'
                            ]
                        );
                    }
                }
            }
        } else if (gettype($workbehalfArray) == 'array') {

            if (is_countable($workbehalfArray) && count($workbehalfArray) > 0) {

                DB::table("kic_category_work_on_behalf_sectors")->Where('categoryId', $id)->delete();
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
                    if ($sectorId == $department_id && $sectorId != 0) {

                        $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '!=', '')->get();
                        foreach ($businessArgs as $key => $val) {
                            $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                                [
                                    'sector_id' => $val->sectorid,
                                    'department_id' => $val->KICDeptId,
                                    'business_id' => $val->deptid,
                                    'categoryId' => $id,
                                    'isWhat' => '0'
                                ]
                            );
                        }
                    } else if ($sectorId != 0 && $department_id != 0 && $business_id == 0) {
                        $businessArgs = KicImportBusiness::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                        if (count($businessArgs) > 0) {
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->sectorid,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $val->deptid,
                                        'categoryId' => $id,
                                        'isWhat' => '1'
                                    ]
                                );
                            }
                        } else {
                            $businessArgs = KicImportDepartment::where('sectorid', '=', $sectorId)->where('KICDeptId', '=', $department_id)->get();
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->SectorId,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $business_id,
                                        'categoryId' => $id,
                                        'isWhat' => '1'
                                    ]
                                );
                            }
                        }
                    } else if ($sectorId != 0 && $department_id != 0 && $business_id != 0) {
                        $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $sectorId,
                                'department_id' => $department_id,
                                'business_id' => $business_id,
                                'categoryId' => $id,
                                'isWhat' => '2'
                            ]
                        );
                    } else {
                        $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $sectorId,
                                'department_id' => $department_id,
                                'business_id' => $business_id,
                                'categoryId' => $id,
                                'isWhat' => '0'
                            ]
                        );

                        $businessArgs = KicImportBusiness::whereNotNull('KICDeptId')->get();
                        if (count($businessArgs) > 0) {
                            foreach ($businessArgs as $key => $val) {
                                $prctype = DB::table("kic_category_work_on_behalf_sectors")->insert(
                                    [
                                        'sector_id' => $val->sectorid,
                                        'department_id' => $val->KICDeptId,
                                        'business_id' => $val->deptid,
                                        'categoryId' => $id,
                                        'isWhat' => '0'
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
