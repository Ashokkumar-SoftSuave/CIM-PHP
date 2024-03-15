<?php

namespace App\Http\Controllers;

use App\Models\KicReportsCategory;
use App\Models\KicReportSetting;
use App\Models\KicReportsManagement;
use App\Models\KicReportsUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class KicReportsUploadController extends Controller
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

            // $position = KicReportsUpload::join('kic_category_work_on_behalf_sectors', function ($join) use ($arrayCheckSec, $arrayCheckDept, $getAccess) {

            //     $join->on('kic_reports_upload.categoryId', '=', 'kic_category_work_on_behalf_sectors.categoryId')
            //         ->whereIn('kic_category_work_on_behalf_sectors.sector_id', $arrayCheckSec);
            //     if ($getAccess[0]->department_id != $getAccess[0]->sector_id) {
            //         $join->whereIn('kic_category_work_on_behalf_sectors.department_id', $arrayCheckDept);
            //     }
            // })->with('category', 'reportsetting', 'reports')->get();


            $position = KicReportsUpload::join('kic_category_work_on_behalf_sectors', function ($join) {
                $join->on('kic_reports_upload.categoryId', '=', 'kic_category_work_on_behalf_sectors.categoryId');
            })->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_category_work_on_behalf_sectors.sector_id')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_category_work_on_behalf_sectors.department_id')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_category_work_on_behalf_sectors.business_id');
            })->with('category', 'reportsetting', 'reports')->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->select(DB::raw('DISTINCT kic_reports_upload.*'))->get();
        } else {
            $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->get();
        }
        if ($position) {
            return response()->json([
                "code" => 200,
                "position" => $position
            ]);
        }

        return response()->json(["code" => 400]);
    }

    public function fetchDefault(Request $request)
    {
        $getAccess = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where `role_id`='" . auth()->user()->roles->first()->id . "'"));

        $getAccessCategory = [];
        if ($getAccess[0]->sector_id != 0) {

            // $getAccessCategory = DB::select(DB::raw("SELECT GROUP_CONCAT(sector_id) as sector_id, GROUP_CONCAT(department_id) as department_id FROM `notif_def_work_on_behalf_sectors` where `sector_id`='" . $getAccess[0]->sector_id . "'"));

            // $arrayCheckSec = array_merge(explode(",", $getAccessCategory[0]->sector_id), [0]);
            // $arrayCheckDept = [0, $getAccess[0]->department_id];

            // $category = KicReportsCategory::join('kic_category_work_on_behalf_sectors', function ($join) use ($arrayCheckSec, $arrayCheckDept, $getAccess) {
            //     $join->on('kic_reports_category.id', '=', 'kic_category_work_on_behalf_sectors.categoryId')
            //         ->whereIn('kic_category_work_on_behalf_sectors.sector_id', $arrayCheckSec);
            //     if ($getAccess[0]->department_id != $getAccess[0]->sector_id) {
            //         $join->whereIn('kic_category_work_on_behalf_sectors.department_id', $arrayCheckDept);
            //     }
            // })->select(DB::raw('DISTINCT kic_reports_category.*'))->get();

            $position = KicReportsCategory::join('kic_category_work_on_behalf_sectors', function ($join) {
                $join->on('kic_reports_category.id', '=', 'kic_category_work_on_behalf_sectors.categoryId');
            })->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_category_work_on_behalf_sectors.sector_id')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_category_work_on_behalf_sectors.department_id')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_category_work_on_behalf_sectors.business_id');
            })->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->select(DB::raw('DISTINCT kic_reports_category.*'))->get();
        } else {
            $category = KicReportsCategory::all();
        }


        $settings = KicReportSetting::all();

        if ($category) {
            return response()->json([
                "code" => 200,
                "category" => $category,
                "settings" => $settings,
            ]);
        }

        return response()->json(["code" => 400]);
    }

    public function loadReportsByCategory(Request $request, $id)
    {
        $reports = KicReportsManagement::where('categoryId', $id)->get();
        if ($reports) {
            return response()->json([
                "code" => 200,
                "reports" => $reports,
            ]);
        } else {
            return response()->json([
                "code" => 200,
                "reports" => [],
            ]);
        }

        return response()->json(["code" => 400]);
    }

    public function getReportSetting(Request $request, $id)
    {
        $reports = KicReportsManagement::with('reportsetting')->where('id', $id)->get();
        if ($reports) {
            return response()->json([
                "code" => 200,
                "reportSetting" => $reports,
            ]);
        } else {
            return response()->json([
                "code" => 200,
                "reports" => [],
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \Modules\ClientApp\Entities\Mtp  $mtp
     * @return \Illuminate\Http\Response
     */
    public function show(KicReportsUpload $dept, $id)
    {
        $fiscalyear = KicReportsUpload::Where('id', $id)->first();

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
        // $checkExist = KicReportsUpload::where('categoryId', $request->get('categoryId'))->where('reportName', $request->get('reportId'))->get();
        // if(count($checkExist) > 0) {
        // return response()->json([
        //     "code" => 409,
        //     "msg" => "data alredy Exist"
        // ]);
        // }

        $kicmanagement = KicReportsManagement::with('category', 'reportsetting')->where('id', $request->get('reportId'))->first();

        if ($kicmanagement) {
            if ($kicmanagement->category) {

                $files = $request->file('UploadFiles');

                foreach ($files as $file) {
                    $civilIdString = '';
                    $filename = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $mimetype = $file->getClientMimeType();
                    $size = $file->getSize();
                    //$upload = new KicReportsUpload();

                    $string = $filename;
                    preg_match_all('!\d+!', $string, $matches);

                    if (count($matches[0]) > 0) {
                        if (strlen(max($matches[0])) == 12) {
                            $civilIdString =  max($matches[0]);
                        }
                    }

                    $newFileName = '';
                    if (strpos($kicmanagement->reportsetting->name, 'civil') !== false && strpos($kicmanagement->reportsetting->name, 'date') !== false) {
                        if ($civilIdString) {
                            $newFileName = str_replace(" ", '', $kicmanagement->name) . '_' . $civilIdString . '_' . $request->get('send_date') . '.' . $extension;

                            $fileData =  'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;
                            $upload = KicReportsUpload::Where('filename', $fileData)->first();
                            if (!$upload) {
                                $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                                $upload = new KicReportsUpload();
                                $upload->filename = $fileData;

                                $upload->reportId = $request->get('reportId');
                                $upload->reportSetting = $request->get('reportSetting');
                                $upload->categoryId = $request->get('categoryId');
                                $upload->mime_type = $mimetype;
                                $upload->size = $size;
                                $upload->name = str_replace(" ", '', $kicmanagement->name);
                                $upload->date = $request->get('send_date');
                                $upload->civilid = $civilIdString;
                                $upload->send_date = $request->get('send_date');
                                $upload->save();
                            } else {
                                //$upload = new KicReportsUpload();
                                $req['filename'] = $fileData;

                                $req['reportId'] = $request->get('reportId');
                                $req['reportSetting'] = $request->get('reportSetting');
                                $req['categoryId'] = $request->get('categoryId');
                                $req['mime_type'] = $mimetype;
                                $req['size'] = $size;
                                $req['name'] = str_replace(" ", '', $kicmanagement->name);
                                $req['date'] = $request->get('send_date');
                                $req['civilid'] = $civilIdString;
                                $req['send_date'] = $request->get('send_date');
                                $upload->update($req);
                            }
                        }
                    } else if (strpos($kicmanagement->reportsetting->name, 'date') != false) {
                        $upload = new KicReportsUpload();
                        $newFileName = str_replace(" ", '', $kicmanagement->name) . '_' . $request->get('send_date') . '.' . $extension;

                        // $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                        // //echo PHP_EOL;
                        // $upload->filename = 'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;

                        // $upload->reportId = $request->get('reportId');
                        // $upload->reportSetting = $request->get('reportSetting');
                        // $upload->categoryId = $request->get('categoryId');
                        // $upload->mime_type = $mimetype;
                        // $upload->size = $size;
                        // $upload->name = str_replace(" ", '', $kicmanagement->name);
                        // $upload->date = $request->get('send_date');
                        // //$upload->civilid = $civilIdString;
                        // $upload->send_date = $request->get('send_date');
                        // $upload->save();

                        $fileData =  'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;

                        $upload = KicReportsUpload::Where('filename', $fileData)->first();
                        if (!$upload) {
                            $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                            $upload = new KicReportsUpload();
                            $upload->filename = $fileData;

                            $upload->reportId = $request->get('reportId');
                            $upload->reportSetting = $request->get('reportSetting');
                            $upload->categoryId = $request->get('categoryId');
                            $upload->mime_type = $mimetype;
                            $upload->size = $size;
                            $upload->name = str_replace(" ", '', $kicmanagement->name);
                            $upload->date = $request->get('send_date');
                            $upload->civilid = $civilIdString;
                            $upload->send_date = $request->get('send_date');
                            $upload->save();
                        } else {
                            //$upload = new KicReportsUpload();
                            $req['filename'] = $fileData;

                            $req['reportId'] = $request->get('reportId');
                            $req['reportSetting'] = $request->get('reportSetting');
                            $req['categoryId'] = $request->get('categoryId');
                            $req['mime_type'] = $mimetype;
                            $req['size'] = $size;
                            $req['name'] = str_replace(" ", '', $kicmanagement->name);
                            $req['date'] = $request->get('send_date');
                            $req['civilid'] = $civilIdString;
                            $req['send_date'] = $request->get('send_date');
                            $upload->update($req);
                        }
                    } else if (strpos($kicmanagement->reportsetting->name, 'civil') != false) {
                        $upload = new KicReportsUpload();
                        if ($civilIdString) {
                            $newFileName = str_replace(" ", '', $kicmanagement->name) . '_' . $civilIdString . '.' . $extension;

                            // $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                            // //echo PHP_EOL;
                            // $upload->filename = 'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;

                            // $upload->reportId = $request->get('reportId');
                            // $upload->reportSetting = $request->get('reportSetting');
                            // $upload->categoryId = $request->get('categoryId');
                            // $upload->mime_type = $mimetype;
                            // $upload->size = $size;
                            // $upload->name = str_replace(" ", '', $kicmanagement->name);
                            // //$upload->date = $request->get('send_date');
                            // $upload->civilid = $civilIdString;
                            // // $upload->send_date = '';//$request->get('send_date');
                            // $upload->save();


                            $fileData =  'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;
                            $upload = KicReportsUpload::Where('filename', $fileData)->first();;
                            if (!$upload) {
                                $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                                $upload = new KicReportsUpload();
                                $upload->filename = $fileData;

                                $upload->reportId = $request->get('reportId');
                                $upload->reportSetting = $request->get('reportSetting');
                                $upload->categoryId = $request->get('categoryId');
                                $upload->mime_type = $mimetype;
                                $upload->size = $size;
                                $upload->name = str_replace(" ", '', $kicmanagement->name);
                                $upload->date = $request->get('send_date');
                                $upload->civilid = $civilIdString;
                                $upload->send_date = $request->get('send_date');
                                $upload->save();
                            } else {
                                //$upload = new KicReportsUpload();
                                $req['filename'] = $fileData;

                                $req['reportId'] = $request->get('reportId');
                                $req['reportSetting'] = $request->get('reportSetting');
                                $req['categoryId'] = $request->get('categoryId');
                                $req['mime_type'] = $mimetype;
                                $req['size'] = $size;
                                $req['name'] = str_replace(" ", '', $kicmanagement->name);
                                $req['date'] = $request->get('send_date');
                                $req['civilid'] = $civilIdString;
                                $req['send_date'] = $request->get('send_date');
                                $upload->update($req);
                            }
                        }
                    } else {

                        $newFileName = str_replace(" ", '', $kicmanagement->name) . '.' . $extension;

                        // $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                        // //echo PHP_EOL;
                        // $upload->filename = 'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;

                        // $upload->reportId = $request->get('reportId');
                        // $upload->reportSetting = $request->get('reportSetting');
                        // $upload->categoryId = $request->get('categoryId');
                        // $upload->mime_type = $mimetype;
                        // $upload->size = $size;
                        // $upload->name = str_replace(" ", '', $kicmanagement->name);
                        // //$upload->date = $request->get('send_date');
                        // //$upload->civilid = $civilIdString;
                        // //$upload->send_date = '';//$request->get('send_date');
                        // $upload->save();

                        $fileData =  'reports/' . str_replace(' ', '', $kicmanagement->category->name) . '/' . $newFileName;
                        $upload = KicReportsUpload::Where('filename', $fileData)->first();;
                        if (!$upload) {
                            $file->move(public_path('reports/' . str_replace(' ', '', $kicmanagement->category->name)), $newFileName);
                            $upload = new KicReportsUpload();
                            $upload->filename = $fileData;

                            $upload->reportId = $request->get('reportId');
                            $upload->reportSetting = $request->get('reportSetting');
                            $upload->categoryId = $request->get('categoryId');
                            $upload->mime_type = $mimetype;
                            $upload->size = $size;
                            $upload->name = str_replace(" ", '', $kicmanagement->name);
                            $upload->date = $request->get('send_date');
                            $upload->civilid = $civilIdString;
                            $upload->send_date = $request->get('send_date');
                            $upload->save();
                        } else {
                            //$upload = new KicReportsUpload();
                            $req['filename'] = $fileData;

                            $req['reportId'] = $request->get('reportId');
                            $req['reportSetting'] = $request->get('reportSetting');
                            $req['categoryId'] = $request->get('categoryId');
                            $req['mime_type'] = $mimetype;
                            $req['size'] = $size;
                            $req['name'] = str_replace(" ", '', $kicmanagement->name);
                            $req['date'] = $request->get('send_date');
                            $req['civilid'] = $civilIdString;
                            $req['send_date'] = $request->get('send_date');
                            $upload->update($req);
                        }
                    }
                }


                return response()->json([
                    "code" => 200,
                    "msg" => "data inserted successfully"
                ]);

                return response()->json(["code" => 400, 'msg' => 'Same Data So you can not update']);
            }
        }
        return response()->json(["code" => 400, 'msg' => 'Same Data So you can not update']);
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {


        $prctype = KicReportsUpload::Where('id', $id)->first();
        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }


        unset($request['id']);

        $kicmanagement = KicReportsManagement::with('category', 'reportsetting')->where('id', $request->get('reportId'))->first();

        if ($kicmanagement) {
            if ($kicmanagement->category) {
                if (strpos($kicmanagement->reportsetting->name, 'civil') !== false && strpos($kicmanagement->reportsetting->name, 'date') !== false) {
                    $beforeName = (explode("_", $prctype->filename));
                    if (count($beforeName) == 3) {
                        $afterName = str_replace(explode('.', $beforeName[2])[0], $request->get('send_date'), $prctype->filename);

                        if (File::exists(public_path($prctype->filename))) {
                            File::moveDirectory(public_path($prctype->filename), public_path($afterName));
                        }
                        $request['filename'] = $afterName;
                        $request['name'] = str_replace(" ", '', $kicmanagement->name);
                        $request['date'] = $request->get('send_date');
                        $request['civilid'] = $beforeName[1];
                    }
                    //$newFileName = str_replace(" ", '', $kicmanagement->name) . '_' . $civilIdString . '_' . $request->get('send_date') . '.' . $extension;
                } else if (strpos($kicmanagement->reportsetting->name, 'date') != false) {
                    //echo $kicmanagement->reportsetting->name;
                    $beforeName = (explode("_", $prctype->filename));
                    if (count($beforeName) == 2) {
                        $afterName = str_replace(explode('.', $beforeName[1])[0], $request->get('send_date'), $prctype->filename);

                        if (File::exists(public_path($prctype->filename))) {
                            File::moveDirectory(public_path($prctype->filename), public_path($afterName));
                        }
                        $request['filename'] = $afterName;
                        $request['name'] = str_replace(" ", '', $kicmanagement->name);
                        $request['date'] = $request->get('send_date');
                    }
                }
            }
        }
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
        $prctype = KicReportsUpload::Where('id', $id)->first();

        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        $kicmanagement = KicReportsManagement::with('category', 'reportsetting')->where('id', $prctype->reportId)->first();

        $nameBefore = str_replace(' ', '', $kicmanagement->category->name);
        $fileName = $prctype->filename;


        if ($prctype->delete()) {
            if (File::exists(public_path($fileName))) {
                File::delete(public_path($fileName));
            }
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }

        return response()->json(["code" => 400]);
    }
}
