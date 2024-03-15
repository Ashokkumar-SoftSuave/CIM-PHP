<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Jobs\SendSMSMessage;
use App\Models\EmailLogs;
use App\Models\KicCustomerImport;
use App\Models\KicCustomerImportInvesement;
use App\Models\KicImportBusiness;
use App\Models\KicImportDepartment;
use App\Models\KicImportSector;
use App\Models\KicReportsManagement;
use App\Models\KicReportsUpload;
use App\Models\NotificationDefination;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class NotificationController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // function __construct()
    // {
    //     $this->middleware('permission:notification-view|notification-create|notification-edit|notification-delete', ['only' =>
    //         ['index', 'show']]);
    //     $this->middleware('permission:notification-create', ['only' => ['edit', 'saveNotification']]);
    //     $this->middleware('permission:notification-edit', ['only' => ['edit', 'saveNotification']]);
    //     $this->middleware('permission:notification-view', ['only' => ['viewnotification']]);
    //     $this->middleware('permission:notification-delete', ['only' => ['notificationdelete']]);
    //     $this->middleware('permission:notification-active-inactive', ['only' => ['notificationActiveDeactive']]);
    // }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {

        //$this->dispatch(new SendEmailJob($notiuser = [], 'test', 'Hello Message Content'));
        //dd('Email Sent');
        //die;
        $getAccess = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where `role_id`='" . auth()->user()->roles->first()->id . "'"));
        $getAccessCategory = [];
        if ($getAccess[0]->sector_id != 0) {
            $notification = NotificationDefination::join('notif_def_work_on_behalf_sectors', function ($join) {
                $join->on('notif_def.id', '=', 'notif_def_work_on_behalf_sectors.categoryId');
            })->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'notif_def_work_on_behalf_sectors.sector_id')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'notif_def_work_on_behalf_sectors.department_id')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'notif_def_work_on_behalf_sectors.business_id');
            })->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->select(DB::raw('DISTINCT notif_def.*'))->get();
        } else {
            $notification = NotificationDefination::get();
        }

        $i = 0;
        $notifiChannel = $notifiEvent = $notifiStatus = [];
        foreach ($notification as $notif) {
            $event = DB::select(DB::raw("select * from  notif_event where id=$notif->event_id"));
            $channelName = ($notif->channel == 'a' ? 'Alert' : ($notif->channel == 'i' ? 'Inbox' : ($notif->channel == 'e'
                ? 'Email' : ($notif->channel == 'b' ? 'Broadcast' : 'SMS'))));
            $notification[$i]->eventName = $event[0]->name;
            $notification[$i]->channelName = $channelName;
            $notifiEvent[$i]['value'] = $notif->event_id;
            $notifiEvent[$i]['text'] = $event[0]->name;
            $notifiChannel[$i]['value'] = $notif->channel;
            $notifiChannel[$i]['text'] = $channelName;
            $notifiStatus[$i]['value'] = ($notif->status_active) ? 1 : 0;
            $notifiStatus[$i]['text'] = ($notif->status_active == 0 ? 'deactive' : 'active');
            $i++;
        }
        $newArray = array();
        $usedFruits = array();
        foreach ($notifiChannel as $key => $line) {
            if (!in_array($line['value'], $usedFruits)) {
                $usedFruits[] = $line['value'];
                $newArray[$key] = $line;
            }
        }
        $notifiChannel = $newArray;
        $newArray = NULL;
        $usedFruits = NULL;

        $newArray = array();
        $usedFruits = array();
        foreach ($notifiEvent as $key => $line) {
            if (!in_array($line['value'], $usedFruits)) {
                $usedFruits[] = $line['value'];
                $newArray[$key] = $line;
            }
        }
        $notifiEvent = $newArray;
        $newArray = NULL;
        $usedFruits = NULL;

        $newArray = array();
        $usedFruits = array();
        foreach ($notifiStatus as $key => $line) {
            if (!in_array($line['value'], $usedFruits)) {
                $usedFruits[] = $line['value'];
                $newArray[$key] = $line;
            }
        }
        $notifiStatus = $newArray;
        $newArray = NULL;
        $usedFruits = NULL;

        return response()->json([
            "code" => 200,
            "notificationlist" => $notification,
            "notifiChannel" => $notifiChannel,
            "notifiEvent" => $notifiEvent,
            "notifiStatus" => $notifiStatus,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('clientapp::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    public function notificationview(Request $request, $id, $lang)
    {
        return $this->edit($request, $id, $lang);
    }

    public function edit(Request $request, $id, $lang)
    {
        $notification = NotificationDefination::Where('id', $id)->first();

        //var_dump($notification);
        if ($notification) {
            $getUsers = DB::table('notif_to_user')->where('notif_id', $id)->get();
            $getRoles = DB::table('notif_to_role')->where('notif_id', $id)->get();
            $getAttachment = DB::table('notif_def_attachment')->select('name')->where('noti_id', $id)->get();
            $roleIds = [];
            foreach ($getRoles as $role) {
                $roleIds[] = (int)$role->role_id;
            }

            $usersIdData = [];
            foreach ($getUsers as $user) {
                $usersIdData[] = $user->user_id;
            }

            $attachIdData = [];
            $at = 0;
            foreach ($getAttachment as $attach) {
                $attachIdData[] = $attach->name;
            }

            $notification->attachment = implode('|', $attachIdData);

            if ($notification->kicmanagement) {
                //echo $notification->kicmanagement;
                $kicmanagement = KicReportsManagement::with('category')->whereIn('id', explode(",", $notification->kicmanagement))->get();
                $management = [];
                //echo count($kicmanagement);
                foreach ($kicmanagement as $k => $v) {

                    $management[$k]['id'] = $v->id;
                    $management[$k]['name'] = ($lang == 'en') ? $v->category->name . ' -> ' . $v->name : $v->category->name_ar . ' -> ' . $v->name_ar;
                    $management[$k]['name_ar'] = $v->category->name_ar . ' -> ' . $v->name_ar;
                }

                $notification->kicmanagement = $management;
                //var_dump($kicmanagement);
                //die;
                // $kicAccountType = DB::select(DB::raw("select * from kic_account_types s where  s.id in ($v)"));
                // if (count($kicAccountType) > 0) {

                //     $ji = 0;
                //     $v = [];
                //     foreach ($kicAccountType as $typeVal) {
                //         $v[$ji] = [
                //             'id' => $typeVal->id,
                //             'name' => ($lang == 'en') ? $typeVal->name : $typeVal->name_ar
                //         ];
                //         $ji++;
                //     }
                // } else {
                //     $v = null;
                // }
            }

            $workvalues = [];
            $work_on_behalf = DB::select(DB::raw("SELECT sector_id, department_id, business_id, isWhat FROM notif_def_work_on_behalf_sectors where categoryId= $id"));
            $i = 0;
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
            $notification->work_on_behalf = $workvalues;

            // //$role = DB::table('kic_user_types')->whereIn('id', $roleIds)->get(); //Role::whereIn('id', $roleIds)->get();
            // $role = KicCustomerImport::select('dept as id', DB::raw("CONCAT(KICSectorEn,'-',KICDepartment,'-',dept_name) AS name"), 'KICSectorid', 'KICSectorEn', 'KICDeptId', 'KICDepartment', 'dept', 'dept_name')->where('KICSectorid', '!=', '38')
            //     ->where('dept', '!=', '500')
            //     ->where('ClientType', '=', 'Individual')
            //     // ->whereIn('KICSectorid', $roleIds)
            //     // ->OrwhereIn('KICDeptId', $roleIds)
            //     // ->OrwhereIn('dept', $roleIds)
            //     ->where(function ($query) use ($roleIds) {
            //         $query->whereIn('KICSectorid', $roleIds)
            //             ->OrwhereIn('KICDeptId', $roleIds)
            //             ->OrwhereIn('dept', $roleIds);
            //     })
            //     ->groupBy('KICSectorid', 'KICSectorEn', 'KICDeptId', 'KICDepartment', 'dept', 'dept_name')
            //     ->get();

            // //$users = KicCustomerImport::select('id', 'FullNameEn as name', 'Email as email')->where('ClientType', '=', 'Individual')->get(); //->whereIn('id', $roleIds)->get();
            // // >whereHas('roles', static function ($query) use ($roleIds) {
            // //     return $query->whereIn('id', $roleIds);
            // // })

            // $users = KicCustomerImport::with('todos')->select(

            //     'CustomerId as id',
            //     'FullNameEn as name',
            //     'FullNameAr as name_ar',
            //     DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
            //     DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
            //     DB::raw("CONCAT(KICSectorEn,'-',KICDepartment,'-',dept_name) AS sector_name"),
            //     'Email as email',
            //     'CivilId',
            //     'Mobile',
            //     'AddInfo_SMS_LangId',
            //     'CivilIdExpiry',
            //     'KICSectorid',
            //     'dept',
            //     'KICDeptId',
            //     'UpdatedOn'
            // )->where('ClientType', '=', 'Individual')
            //     // ->whereIn('KICSectorid', $roleIds)
            //     // ->OrwhereIn('KICDeptId', $roleIds)
            //     // ->OrwhereIn('dept', $roleIds)

            //     ->where(function ($query) use ($roleIds) {
            //         $query->whereIn('KICSectorid', $roleIds)
            //             ->OrwhereIn('KICDeptId', $roleIds)
            //             ->OrwhereIn('dept', $roleIds);
            //     })
            //     // )->whereHas('roles', static
            //     // function ($query) use ($roles) {
            //     //     return $query->whereIn('id', [$roles]);
            //     // })
            //     ->get();

            $role = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select('kic_customerInfo_investments.BusinessId as id', DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS name"), 'kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment', 'kic_customerInfo_investments.BusinessId as dept', 'kic_customerInfo_investments.Business as dept_name')->where('kic_customerInfo_investments.SectorId', '!=', '38')
                ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                ->where('ClientType', '!=', '')
                ->where(function ($query) use ($roleIds) {
                    $query->whereIn('kic_customerInfo_investments.SectorId', $roleIds)
                        ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roleIds)
                        ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roleIds);
                })
                // ->whereIn('kic_customerInfo_investments.SectorId', $roles)
                // ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roles)
                // ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roles)
                ->groupBy('kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment', 'kic_customerInfo_investments.BusinessId', 'kic_customerInfo_investments.Business')
                ->get();
            //}

            $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->with('todos')->select(
                'kic_customerInfo_investments.CustomerId as id',
                'FullNameEn as name',
                'FullNameAr as name_ar',
                DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                'Email as email',
                'CivilId',
                'Mobile',
                'AddInfo_SMS_LangId',
                'CivilIdExpiry',
                'kic_customerInfo_investments.SectorId as KICSectorid',
                'kic_customerInfo_investments.BusinessId as dept',
                'kic_customerInfo_investments.KICDeptId',
                'UpdatedOn'
            )->where('ClientType', '!=', '')
                ->where(function ($query) use ($roleIds) {
                    $query->whereIn('kic_customerInfo_investments.SectorId', $roleIds)
                        ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roleIds)
                        ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roleIds);
                })
                ->orderBy('kic_customerinfo_import.Email', 'DESC')

                // where('ClientTypes', '=', 'Individual')
                //     ->whereIn('kic_customerInfo_investments.SectorId', $roles)
                //     ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roles)
                //     ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roles)
                // )->whereHas('roles', static
                // function ($query) use ($roles) {
                //     return $query->whereIn('id', [$roles]);
                // })
                ->get();

            $userDataArray = $userCheck = [];
            $j = 0;
            foreach ($users as $userData) {
                // if (in_array($userData->id, $usersIdData)) {
                //     $userData->is_id = $userData->id;
                //     $userData->noti_id = $userData->id;
                //     //$userData->subtenant_id = (int)$userData->subtenant_id;
                //     //$userData->isCheck = true;
                //     $userDataArray[$j] = $userData;
                // } else {
                //     //$userData->isCheck = false;
                //     $userData->noti_id = $userData->id;
                //     $userDataArray[$j] = $userData;
                // }


                $customer = $userData->id;




                if (!in_array($customer, $userCheck)) {
                    $userCheck[] = $customer;


                    $userDataArray[$customer]['id'] = $userData->id;

                    $userDataArray[$customer]['name'] = $userData->name;
                    $userDataArray[$customer]['name_ar'] = $userData->name_ar;
                    $userDataArray[$customer]['full_name_en'] = $userData->full_name_en;
                    $userDataArray[$customer]['full_name_ar'] = $userData->full_name_ar;

                    $userDataArray[$customer]['email'] = $userData->email;
                    $userDataArray[$customer]['CivilId'] = $userData->CivilId;

                    $userDataArray[$customer]['AddInfo_SMS_LangId'] = $userData->AddInfo_SMS_LangId;
                    $userDataArray[$customer]['sector_name'] = $userData->sector_name;
                    $userDataArray[$customer]['sectorids'] = $userData->sectorids;
                    $userDataArray[$customer]['KICSectorid'] = $userData->KICSectorid;
                    $userDataArray[$customer]['dept'] = $userData->dept;
                    $userDataArray[$customer]['KICDeptId'] = $userData->KICDeptId;
                    if (in_array($userData->id, $usersIdData)) {
                        $userDataArray[$customer]['is_id'] = $userData->id;
                        $userDataArray[$customer]['noti_id'] = $userData->id;
                    } else {
                        $userDataArray[$customer]['noti_id'] = $userData->id;
                    }



                    $i++;
                } else if (in_array($customer, $userCheck)) {
                    //echo 'in';
                    $userDataArray[$customer]['sector_name'] = $userDataArray[$customer]['sector_name'] . ', ' . $userData->sector_name;
                    $userDataArray[$customer]['sectorids'] = $userDataArray[$customer]['sectorids'] . ', ' . $userData->sectorids;
                }

                $j++;
            }
            //->whereIn('users.id', $usersIdData)

            $userIds = [];
            $i = 0;
            foreach (array_values($userDataArray) as $use) {
                //echo strlen($use->email).'===';
                if (isset($use['is_id'])) {
                    if (strlen($use['email']) > 0) {
                        $userIds[] = $use['id'];
                    }
                    //$userIds[] = $use->id;
                }
                //$getSub = DB::table('subtenant')->where('id', $use->sector_id)->get();
                //$userDataArray[$i]->sector_name = $getSub[0]->name;
                $i++;
            }

            $notIn = array_diff($usersIdData, $userIds);

            if (count($notIn) > 0) {
                foreach ($notIn as $not) {
                    //$user = KicCustomerImport::select('id', 'FullNameEn as name', 'Email as email')->where('ClientType', '=', 'Individual')->where('id', $not)->get();
                    // $user = KicCustomerImport::with('todos')->select(

                    //     'CustomerId as id',
                    //     'FullNameEn as name',
                    //     'FullNameAr as name_ar',
                    //     DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                    //     DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                    //     DB::raw("CONCAT(KICSectorEn,'-',KICDepartment,'-',dept_name) AS sector_name"),
                    //     'Email as email',
                    //     'CivilId',
                    //     'Mobile',
                    //     'AddInfo_SMS_LangId',
                    //     'CivilIdExpiry',
                    //     'KICSectorid',
                    //     'dept',
                    //     'KICDeptId',
                    //     'UpdatedOn'
                    // )->where('ClientType', '=', 'Individual')
                    //     ->where('id', $not)
                    //     ->get();

                    $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->with('todos')->select(
                        'kic_customerInfo_investments.CustomerId as id',
                        'FullNameEn as name',
                        'FullNameAr as name_ar',
                        DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                        DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                        DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                        DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                        'Email as email',
                        'CivilId',
                        'Mobile',
                        'AddInfo_SMS_LangId',
                        'CivilIdExpiry',
                        'kic_customerInfo_investments.SectorId as KICSectorid',
                        'kic_customerInfo_investments.BusinessId as dept',
                        'kic_customerInfo_investments.KICDeptId',
                        'UpdatedOn'
                    )->where('ClientType', '!=', '')
                        ->where(function ($query) use ($roleIds) {
                            $query->whereIn('kic_customerInfo_investments.SectorId', $roleIds)
                                ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roleIds)
                                ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roleIds);
                        })
                        ->where('kic_customerinfo_import.CustomerId', $not)
                        ->orderBy('kic_customerinfo_import.Email', 'DESC')
                        ->get();

                    $k = 0;
                    $notInData = [];
                    foreach ($user as $use) {
                        $notInData[$k] = $use;
                        if (strlen($use->email) > 0) {
                            $userIds[] = $use->id;
                        }
                        $notInData[$k]->is_id = $use->id;
                        $notInData[$k]->outOfRole = true;
                        $notInData[$k]->noti_id = $use->id;
                        //$notInData[$k]->subtenant_id = (int)$use->subtenant_id;
                        // $getSub = DB::table('subtenant')->where('id', $use->sector_id)->get();
                        // $notInData[$k]->sector_name = $getSub[0]->name;
                        $k++;
                    }
                }
                $userDataArray = array_merge(array_values($userDataArray), $notInData);
            }

            /*$notiShowData = [];
            foreach ($notification as $notify) {
                $notiShowData['channel'] = $notify['channel'];
                $notiShowData['contents'] = $notify['contents'];
                $notiShowData['created_by'] = $notify['created_by'];
                $notiShowData['description'] = $notify['description'];
                $notiShowData['event_id'] = $notify['event_id'];
                $notiShowData['is_recur'] = $notify['is_recur'];
                $notiShowData['notif_time'] = $notify['notif_time'];
                $notiShowData['recur_dom'] = $notify['recur_dom'];
                $notiShowData['recur_dow'] = $notify['recur_dow'];
                $notiShowData['recur_m_condition'] = $notify['recur_m_condition'];
                $notiShowData['recur_period'] = $notify['recur_period'];
                $notiShowData['recur_q_condition'] = $notify['recur_q_condition'];
                $notiShowData['recur_qe_diff_days'] = $notify['recur_qe_diff_days'];
                $notiShowData['start_dt'] = $notify['start_dt'];
                $notiShowData['status_active'] = $notify['status_active'];
            }*/
            $roleShow = [];
            $i = 0;
            foreach ($role as $ro) {
                $roleShow[$i]['id'] = $ro->id;
                $roleShow[$i]['name'] = $ro->name;
                $i++;
            }

            $userShow = [];
            $j = 0;
            foreach ($userDataArray as $user) {
                $userShow[$j]['id'] = $user['id'];
                $userShow[$j]['name'] = $user['name']; // . ' ' . $user['last_name'];
                $userShow[$j]['email'] = $user['email'];
                //$userShow[$j]['sub_name'] = $user['sub_name'];
                //$userShow[$j]['sector_name'] = $user['sector_name'];
                $j++;
            }

            return response()->json([
                "code" => 200,
                "notificationdata" => $notification,
                "role" => $role,
                "roleIds" => $roleIds,
                "users" => array_values($userDataArray),
                "userIds" => array_unique($userIds),
                "usersIdData" => $usersIdData,
                "notIn" => $notIn,
                "roleShow" => $roleShow,
                "userShow" => $userShow
            ]);
        }

        return response()->json([
            "code" => 404,
            "msg" => "data not found"
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
    }

    public function show($id)
    {
        $notification = NotificationDefination::Where('id', $id)->first();
        if ($notification) {
            return response()->json([
                "code" => 200,
                "notification" => $notification
            ]);
        }
        return response()->json([
            "code" => 401,
            "msg" => "No record"
        ]);
    }

    public function notificationActiveDeactive(Request $request)
    {
        $id = $request->get('notifyId');
        $event = DB::select(DB::raw("select * from  notif_def where id=$id"));

        $status = ($event[0]->status_active == 0) ? 1 : 0;
        $statusMessage = ($status == 0) ? 'deactive' : 'active';

        if (DB::select(DB::raw("update notif_def set status_active= $status where id=$id"))) {
        }
        return response()->json([
            "code" => 200,
            "msg" => "data $statusMessage successfully"
        ]);
    }

    public function notificationdelete(Request $request)
    {
        $id = $request->get('notifyId');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table("notif_to_user")->where('notif_id', $id)->delete();
        DB::table("notif_to_role")->where('notif_id', $id)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $user = NotificationDefination::find($id);
        if ($user->delete()) {
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }
    }

    /***
     * @param $treeArrayGroups
     * @param $rootArray
     * @return mixed
     * Recursive Array
     */
    function transformTree($treeArrayGroups, $rootArray)
    {
        // Read through all nodes where parent is root array
        foreach ($treeArrayGroups[$rootArray['id']] as $child) {
            //echo $child['id'].PHP_EOL;
            // If there is a group for that child, aka the child has children
            if (isset($treeArrayGroups[$child['id']])) {
                // Traverse into the child
                $newChild = $this->transformTree($treeArrayGroups, $child);
            } else {
                $newChild = $child;
            }

            if ($child['id'] != '') {
                // Assign the child to the array of children in the root node
                $rootArray['tree'][] = $newChild;
            }
        }
        return $rootArray;
    }

    public function loadNotificationDefaultData(Request $request)
    {
        $notiArgs = DB::table('event_arg')->get();
        $notiEvent = DB::table('notif_event')->get();
        $roles = DB::table('kic_user_types')->get(); //Role::get();
        $dom = [];
        for ($i = 1; $i <= 31; $i++) {
            $dom[] = $i;
        }

        $kicmanagement = KicReportsManagement::with('category')->get();
        $management = [];
        $ii = 0;
        foreach ($kicmanagement as $k => $v) {
            $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->get();
            if (count($position) > 0) {
                $management[$ii]['id'] = $v->id;
                $management[$ii]['name'] = $v->category->name . ' -> ' . $v->name;
                $management[$ii]['name_ar'] = $v->category->name_ar . ' -> ' . $v->name_ar;
                $ii++;
            }
        }

        // $rows = DB::select(DB::raw("WITH RECURSIVE cte (id, name, parent_id, level, path) AS (select id, name, parent_id, CAST('' AS CHAR(10)), concat( cast(id as char(200)), '') from subtenant where id = 2 UNION ALL select s.id, concat(CONCAT(c.level, ''), '', s.name), s.parent_id, CONCAT(c.level, ''), CONCAT(c.path, ',', s.id) from subtenant s inner join cte c on s.parent_id = c.id UNION ALL select null, repeat('', 50), 2, '', CONCAT(id, '') from subtenant where id = 2) select id, name as label, name, parent_id from cte order by path"));
        // $result = array_map(function ($value) {
        //     return (array)$value;
        // }, $rows);

        // // Group by parent id
        // $treeArrayGroups = [];
        // foreach ($result as $record) {
        //     $treeArrayGroups[$record['parent_id']][] = $record;
        // }
        // // Get the root
        // $rootArray = $result[0]['id'] != '' ? $result[0] : $result[1];
        // // Transform the data
        // $outputTree = $this->transformTree($treeArrayGroups, $rootArray);

        // $data = [];
        // $data[] = $outputTree;

        return response()->json([
            "code" => 200,
            "roles" => $roles,
            "notiArgs" => $notiArgs,
            "notiEvent" => $notiEvent,
            "dom" => $dom,
            "kicmanagement" => $management
            //"sectors" => $data
        ]);
    }

    public function loadUserDataFromRole(Request $request)
    {
        //echo 'ddd';
        // die;
        $roles = $request->get('roles');
        $type = $request->get('type');
        $isWhich = $request->get('isWhich');

        $notification = [];
        $role = [];
        if ($type == 2) {
            //auth()->user()->roles->first()->id
            $getAccess = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where `role_id`='" . auth()->user()->roles->first()->id . "'"));
            //var_dump($getAccess[0]->role_id);
            //die;
            $notification = NotificationDefination::select('id as value', 'description as text')->where('isOfflineOrCron', '0')->whereNotIn('event_id', [1, 2, 3, 4, 5, 6, 7])->where('status_active', '1')->get();

            if (auth()->user()->sector != 0) {
                $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->join('role_work_on_behalf_sectors', function ($join) {
                    $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_customerInfo_investments.SectorId')
                        ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_customerInfo_investments.KICDeptId')
                        ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_customerInfo_investments.BusinessId');
                })->with('todos')->select(
                    'kic_customerInfo_investments.id',
                    'kic_customerInfo_investments.CustomerId',
                    'FullNameEn as name',
                    'FullNameAr as name_ar',
                    DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                    DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                    DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                    DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                    'Email as email',
                    'CivilId',
                    'Mobile',
                    'AddInfo_SMS_LangId',
                    'CivilIdExpiry',
                    'kic_customerInfo_investments.SectorId as KICSectorid',
                    'kic_customerInfo_investments.BusinessId as dept',
                    'kic_customerInfo_investments.KICDeptId',
                    'UpdatedOn',
                    'T24Approved',
                    'T24ApprovedOn',
                    'T24Result',
                    'ClientType'
                )->where('ClientType', '!=', '')
                    ->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)
                    // ->where(function ($query) use ($getAccess) {
                    //     if ($getAccess[0]->sector_id != 0) {
                    //         $query->whereIn('kic_customerInfo_investments.SectorId', [$getAccess[0]->department_id])
                    //             ->OrwhereIn('kic_customerInfo_investments.KICDeptId', [$getAccess[0]->department_id])
                    //             ->OrwhereIn('kic_customerInfo_investments.BusinessId', [$getAccess[0]->department_id]);
                    //     }
                    // })
                    ->where(function ($query) use ($isWhich) {
                        if ($isWhich != null) {
                            $query->whereIn('kic_customerinfo_import.T24Approved', [0]);
                        }
                    })
                    ->get();
            } else {
                $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->with('todos')->select(
                    'kic_customerInfo_investments.id',
                    'kic_customerInfo_investments.CustomerId',
                    'FullNameEn as name',
                    'FullNameAr as name_ar',
                    DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                    DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                    DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                    DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                    'Email as email',
                    'CivilId',
                    'Mobile',
                    'AddInfo_SMS_LangId',
                    'CivilIdExpiry',
                    'kic_customerInfo_investments.SectorId as KICSectorid',
                    'kic_customerInfo_investments.BusinessId as dept',
                    'kic_customerInfo_investments.KICDeptId',
                    'UpdatedOn',
                    'T24Approved',
                    'T24ApprovedOn',
                    'T24Result',
                    'ClientType'
                )->where('ClientType', '!=', '')
                    ->where(function ($query) use ($getAccess, $isWhich) {
                        if ($getAccess[0]->sector_id != 0) {
                            $query->whereIn('kic_customerInfo_investments.SectorId', [$getAccess[0]->department_id])
                                ->OrwhereIn('kic_customerInfo_investments.KICDeptId', [$getAccess[0]->department_id])
                                ->OrwhereIn('kic_customerInfo_investments.BusinessId', [$getAccess[0]->department_id]);
                        }

                        if ($isWhich != null) {
                            $query->whereIn('kic_customerinfo_import.T24Approved', [0]);
                        }
                    })
                    ->get();
            }
        } else {


            //var_dump($roles);
            //die;
            // if ($isWhich == 'business') {
            //     $role = KicCustomerImport::select('')->whereIn('KICSectorid', $roles)->OrwhereIn('KICDeptId', $roles)->OrwhereIn('dept', $roles)->get();
            // } elseif ($isWhich == 'department') {
            //     $role = KicCustomerImport::whereIn('KICSectorid', $roles)->OrwhereIn('KICDeptId', $roles)->OrwhereIn('dept', $roles)->get();
            // } else if ($isWhich == 'sector') {
            $role = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select('kic_customerInfo_investments.BusinessId as id', DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS name"), 'kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment', 'kic_customerInfo_investments.BusinessId as dept', 'kic_customerInfo_investments.Business as dept_name')->where('kic_customerInfo_investments.SectorId', '!=', '38')
                ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                ->where('ClientType', '!=', '')
                ->where(function ($query) use ($roles) {
                    $query->whereIn('kic_customerInfo_investments.SectorId', $roles)
                        ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roles)
                        ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roles);
                })
                // ->whereIn('kic_customerInfo_investments.SectorId', $roles)
                // ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roles)
                // ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roles)
                ->groupBy('kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment', 'kic_customerInfo_investments.BusinessId', 'kic_customerInfo_investments.Business')
                ->get();
            //}

            $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->with('todos')->select(
                'kic_customerInfo_investments.CustomerId as id',
                'FullNameEn as name',
                'FullNameAr as name_ar',
                DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                'Email as email',
                'CivilId',
                'Mobile',
                'AddInfo_SMS_LangId',
                'CivilIdExpiry',
                'kic_customerInfo_investments.SectorId as KICSectorid',
                'kic_customerInfo_investments.BusinessId as dept',
                'kic_customerInfo_investments.KICDeptId',
                'UpdatedOn',
                'T24Approved',
                'T24ApprovedOn',
                'T24Result',
                'ClientType'
            )->where('ClientType', '!=', '')
                ->where(function ($query) use ($roles) {
                    $query->whereIn('kic_customerInfo_investments.SectorId', $roles)
                        ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roles)
                        ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roles);
                })
                ->orderBy('kic_customerinfo_import.Email', 'DESC')

                // where('ClientTypes', '=', 'Individual')
                //     ->whereIn('kic_customerInfo_investments.SectorId', $roles)
                //     ->OrwhereIn('kic_customerInfo_investments.KICDeptId', $roles)
                //     ->OrwhereIn('kic_customerInfo_investments.BusinessId', $roles)
                // )->whereHas('roles', static
                // function ($query) use ($roles) {
                //     return $query->whereIn('id', [$roles]);
                // })
                ->get();
        }
        $userIds = [];
        $i = 0;

        $countTodos = 0;
        $userMap = [];
        $userCheck = [];

        foreach ($users as $use) {
            if ($type == 2) {
                $customer = $use->CustomerId;
            } else {
                $customer = $use->id;
            }

            if (count($use->todos) > 0) {
                $countTodos++;
            }
            $number = explode('965', $use->Mobile);
            if (strlen($use->email) > 0) {
                if (!in_array($use->id, $userIds)) {
                    $userIds[] = $use->id;
                }
            }

            if (!in_array($customer, $userCheck)) {

                // $ekycData = DB::table('kic_customerinfo')
                //     ->where('kic_customerinfo.CivilId', '=', $use->CivilId)
                //     ->orderBy('CustomerId', 'DESC')
                //     ->limit(1)
                //     ->get();
                $ekycData = DB::table('kic_customerinfo')
                    ->where('CivilId', '=', $use->CivilId)
                    ->orderBy('CustomerId', 'DESC')
                    ->limit(1)
                    ->get();

                $userMap[$customer]['havekyc'] = false;
                $userMap[$customer]['cid'] = $use->CustomerId;
                if (count($ekycData) > 0) {
                    $userMap[$customer]['havekyc'] = true;
                    $userMap[$customer]['cid'] = $ekycData[0]->CustomerId;
                }
                $userCheck[] = $customer;

                if ($type == 2) {
                    $userMap[$customer]['CustomerId'] = $use->CustomerId;
                    $userMap[$customer]['id'] = $use->id;
                } else {
                    $userMap[$customer]['id'] = $use->id;
                }
                $userMap[$customer]['name'] = $use->name;
                $userMap[$customer]['name_ar'] = $use->name_ar;
                $userMap[$customer]['full_name_en'] = $use->full_name_en;
                $userMap[$customer]['full_name_ar'] = $use->full_name_ar;

                $userMap[$customer]['email'] = $use->email;
                $userMap[$customer]['CivilId'] = $use->CivilId;

                $userMap[$customer]['AddInfo_SMS_LangId'] = $use->AddInfo_SMS_LangId;
                $userMap[$customer]['sector_name'] = $use->sector_name;
                $userMap[$customer]['sectorids'] = $use->sectorids;
                $userMap[$customer]['KICSectorid'] = $use->KICSectorid;
                $userMap[$customer]['dept'] = $use->dept;
                $userMap[$customer]['KICDeptId'] = $use->KICDeptId;
                $userMap[$customer]['T24Approved'] = $use->T24Approved;
                $userMap[$customer]['T24ApprovedOn'] = $use->T24ApprovedOn;
                $userMap[$customer]['T24Result'] = $use->T24Result;
                $userMap[$customer]['ClientType'] = $use->ClientType;

                $userMap[$customer]['is_id'] = $use->id;
                $userMap[$customer]['noti_id'] = $use->id;
                $userMap[$customer]['CivilIdExpiry'] = $use->CivilIdExpiry ? date('Y-m-d', strtotime($use->CivilIdExpiry)) : '';
                $userMap[$customer]['UpdatedOn'] = date('Y-m-d', strtotime($use->UpdatedOn));
                $userMap[$customer]['Mobile'] = (substr($use->Mobile, 0, 3) === "965") ? '+965' . $number[0] . '-' . $number[1] : $use->Mobile; //explode('965', $use->Mobile);
                $userMap[$customer]['todos'] = $use->todos;
                $i++;
            } else if (in_array($customer, $userCheck)) {
                //echo 'in';
                $userMap[$customer]['sector_name'] = $userMap[$customer]['sector_name'] . ', ' . $use->sector_name;
                $userMap[$customer]['sectorids'] = $userMap[$customer]['sectorids'] . ', ' . $use->sectorids;
            }
        }


        $active =  DB::select(DB::raw("SELECT COUNT(*) as active FROM `kic_customerinfo_import` where ClientType !=''"));

        $updatekyc = DB::select(DB::raw("SELECT COUNT(*) as updatekyc FROM `kic_customerinfo_import` WHERE UpdatedOn <= DATE_SUB(DATE(DATE_Add(NOW(), INTERVAL 1 DAY)), INTERVAL 1 YEAR) and ClientType !=''"));

        $CivilIdExpiryComing = DB::select(DB::raw("SELECT count(*) as CivilIdExpiryComing  FROM `kic_customerinfo_import` WHERE CivilIdExpiry > CURDATE() and  CivilIdExpiry <= DATE_ADD(CURDATE(), INTERVAL '30' DAY) and ClientType !=''"));

        $CivilIdExpired = DB::select(DB::raw("SELECT count(*) as CivilIdExpired  FROM `kic_customerinfo_import` WHERE CivilIdExpiry <= CURDATE() and ClientType !=''"));

        $missingInfo = DB::select(DB::raw("SELECT COUNT(*) as missingInfo FROM `kic_customerinfo_import` WHERE (Email is Null OR Mobile is Null OR CivilIdExpiry is Null OR CivilId is Null) and ClientType !=''"));

        return response()->json([
            "code" => 200,
            "role" => $role,
            "users" => array_values($userMap),
            "userIds" => $userIds,
            "notification" => $notification,
            "counts" => [
                'active' => $active[0]->active,
                'updatekyc' => $updatekyc[0]->updatekyc,
                'CivilIdExpiryComing' => $CivilIdExpiryComing[0]->CivilIdExpiryComing,
                'CivilIdExpired' => $CivilIdExpired[0]->CivilIdExpired,
                'missingInfo' => $missingInfo[0]->missingInfo,
                'countTodos' => $countTodos
            ]
        ]);
    }

    public function saveNotification(Request $request)
    {
        if (isset($request->notification['id']) && $request->notification['id'] != '') {
            $notifDef = NotificationDefination::find($request->notification['id']);

            if (!$notifDef) {
                return response()->json([
                    "code" => 404,
                    "msg" => "data not found"
                ]);
            } else {
                //var_dump($request->all());

                $notifDef->description = $request->notification['description'];
                $notifDef->tenant_id = 1; //auth()->user()->tenant_id; //env('TENANT_ID');
                $notifDef->channel = $request->notification['channel'];
                $notifDef->event_id = $request->notification['event_id'];
                $notifDef->kicmanagement =  $request->notification['kicmanagement'] ? implode(",", array_column($request->notification['kicmanagement'], 'id')) : $request->notification['kicmanagement'];
                $notifDef->entity = $request->notification['entity'];
                $notifDef->civil_expiry_days = ($request->notification['event_id'] == 1) ? $request->notification['civil_expiry_days'] : null;
                $notifDef->custommsgdescription = $request->notification['custommsgdescription'] ? $request->notification['custommsgdescription'] : null;

                $notifDef->contents_en = $request->notification['contents_en'];
                $notifDef->contents_ar = $request->notification['contents_ar'];
                $notifDef->subject_en = $request->notification['subject_en'];
                $notifDef->subject_ar = $request->notification['subject_ar'];
                $notifDef->channel = $request->notification['channel'];
                //$notifDef->status_active = 0;
                $notifDef->start_dt = $request->notification['start_dt'];
                $notifDef->end_dt = $request->notification['end_dt'];
                $notifDef->isOfflineOrCron = $request->notification['isOfflineOrCron'];
                $notifDef->notif_time = $request->notification['notif_time'];
                $notifDef->is_recur = $request->notification['is_recur'];
                $notifDef->recur_period = $request->notification['recur_period'];
                $notifDef->recur_dow = $request->notification['recur_dow'];
                $notifDef->recur_dom = $request->notification['recur_dom'];
                $notifDef->recur_m_condition = $request->notification['recur_m_condition'];
                $notifDef->recur_q_condition = $request->notification['recur_q_condition'];
                $notifDef->recur_qe_diff_days = $request->notification['recur_qe_diff_days'];
                $notifDef->created_by = auth()->user()->id;

                if ($notifDef->save()) {
                    if (count($request->users) > 0) {
                        DB::statement('SET FOREIGN_KEY_CHECKS=0');
                        DB::table("notif_to_user")->where('notif_id', $notifDef->id)->delete();
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                        foreach (array_unique($request->users) as $user) {
                            $notifUser = DB::table("notif_to_user")->insert( //insert(
                                [
                                    'notif_id' => $notifDef->id,
                                    'user_id' => $user,
                                ]
                            );
                        }
                    } else {
                        DB::statement('SET FOREIGN_KEY_CHECKS=0');
                        DB::table("notif_to_user")->where('notif_id', $notifDef->id)->delete();
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    }
                    if (!empty($request->roles)) {

                        DB::statement('SET FOREIGN_KEY_CHECKS=0');
                        DB::table("notif_to_role")->where('notif_id', $notifDef->id)->delete();
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');

                        foreach ($request->roles as $role) {
                            $notifUser = DB::table("notif_to_role")->insert( //insert(
                                [
                                    'notif_id' => $notifDef->id,
                                    'role_id' => $role,
                                ]
                            );
                        }
                    }

                    //$attachData = isset($request->notification['attachment']) ? array_filter(explode('|', $request->notification['attachment'])) : [];
                    $attachData = isset($request->notification['attachment']) && !empty($request->notification['attachment']) ? array_filter(explode('|', $request->notification['attachment'])) : [];
                    DB::table("notif_def_attachment")->where('noti_id', $notifDef->id)->delete();
                    if (!empty($attachData)) {
                        foreach ($attachData as $attach) {
                            $fileName = explode("notification_doc/", $attach);
                            if (file_exists(public_path('notification_doc') . '/' . $fileName[1])) {
                                //unlink(public_path('notification_doc') . '/' . $fileName[1]);
                                DB::table("notif_def_attachment")->insert( //insert(
                                    [
                                        'noti_id' => $notifDef->id,
                                        'name' => $attach,
                                    ]
                                );
                            }
                        }
                    }

                    $this->storeWorkBehalf($request->notification['work_on_behalf'], $notifDef->id);
                }
                return response()->json([
                    "code" => 200,
                    "msg" => "data updated successfully"
                ]);
            }
        } else {
            $notifDef = new NotificationDefination();
            $notifDef->description = $request->notification['description'];
            $notifDef->tenant_id = 1; //auth()->user()->tenant_id; //env('TENANT_ID');
            $notifDef->channel = $request->notification['channel'];
            $notifDef->event_id = $request->notification['event_id'];
            $notifDef->kicmanagement =  $request->notification['kicmanagement'] ? implode(",", array_column($request->notification['kicmanagement'], 'id')) : $request->notification['kicmanagement'];
            $notifDef->entity = $request->notification['entity'];
            $notifDef->civil_expiry_days = ($request->notification['event_id'] == 1) ? $request->notification['civil_expiry_days'] : null;
            $notifDef->custommsgdescription = $request->notification['custommsgdescription'] ? $request->notification['custommsgdescription'] : null;
            $notifDef->contents_en = $request->notification['contents_en'];
            $notifDef->contents_ar = $request->notification['contents_ar'];
            $notifDef->subject_en = $request->notification['subject_en'];
            $notifDef->subject_ar = $request->notification['subject_ar'];
            $notifDef->channel = $request->notification['channel'];
            $notifDef->status_active = 0;
            $notifDef->start_dt = $request->notification['start_dt'];
            $notifDef->end_dt = $request->notification['end_dt'];
            $notifDef->isOfflineOrCron = $request->notification['isOfflineOrCron'];

            $notifDef->notif_time = $request->notification['notif_time'];
            $notifDef->is_recur = $request->notification['is_recur'];
            $notifDef->recur_period = $request->notification['recur_period'];
            $notifDef->recur_dow = $request->notification['recur_dow'];
            $notifDef->recur_dom = $request->notification['recur_dom'];
            $notifDef->recur_m_condition = $request->notification['recur_m_condition'];
            $notifDef->recur_q_condition = $request->notification['recur_q_condition'];
            $notifDef->recur_qe_diff_days = $request->notification['recur_qe_diff_days'];
            $notifDef->created_by = auth()->user()->id;

            if ($notifDef->save()) {

                if (count($request->users) > 0) {
                    foreach (array_unique($request->users) as $user) {
                        $notifUser = DB::table("notif_to_user")->insert( //insert(
                            [
                                'notif_id' => $notifDef->id,
                                'user_id' => $user,
                            ]
                        );
                    }
                }

                if (!empty($request->roles)) {

                    foreach ($request->roles as $role) {
                        $notifUser = DB::table("notif_to_role")->insert( //insert(
                            [
                                'notif_id' => $notifDef->id,
                                'role_id' => $role,
                            ]
                        );
                    }
                }

                //$attachData = strlen($request->notification['attachment']) > 0  ? array_filter(explode('|', $request->notification['attachment'])) : [];
                $attachData = isset($request->notification['attachment']) && !empty($request->notification['attachment']) ? array_filter(explode('|', $request->notification['attachment'])) : [];
                if (!empty($attachData)) {
                    //DB::table("notif_def_attachment")->where('noti_id', $notifDef->id)->delete();
                    foreach ($attachData as $attach) {
                        $fileName = explode("notification_doc/", $attach);
                        if (file_exists(public_path('notification_doc') . '/' . $fileName[1])) {
                            //unlink(public_path('notification_doc') . '/' . $fileName[1]);
                            DB::table("notif_def_attachment")->insert( //insert(
                                [
                                    'noti_id' => $notifDef->id,
                                    'name' => $attach,
                                ]
                            );
                        }
                    }
                }

                $this->storeWorkBehalf($request->notification['work_on_behalf'], $notifDef->id);
            }
            return response()->json([
                "code" => 200,
                "msg" => "data created successfully"
            ]);
        }
        //var_dump($request->notification['description']);
        //var_dump($request->notification['description']);
        //var_dump($request->users);
        //var_dump($request->roles);
    }

    public function selectUser($id)
    {
        $user = User::join('subtenant', 'users.subtenant_id', '=', 'subtenant.id')->select('users.id', 'users.name', 'users.last_name', 'subtenant.name as sub_name', 'users.sector_id', 'users.subtenant_id', 'users.email')->where('users.id', $id)->get();
        $i = 0;
        foreach ($user as $use) {
            $userIds[] = $use->id;
            $user[$i]->is_id = $use->id;
            $user[$i]->noti_id = $use->id;
            $user[$i]->subtenant_id = (int)$use->subtenant_id;
            $user[$i]->outOfRole = true;
            $getSub = DB::table('subtenant')->where('id', $use->sector_id)->get();
            $user[$i]->sector_name = $getSub[0]->name;
            $i++;
        }
        return response()->json([
            "code" => 200,
            "user" => $user
        ]);
    }

    public function loadKpiOrgUsersNotification($orgUnit, $sector = null)
    {
        if ($orgUnit != 'null' && $orgUnit != 'undefined') {

            $users = DB::select(DB::raw("WITH RECURSIVE cte (level1_id, id, parent_id, subtenant_type, name, path) AS (
	-- This is end of the recursion: Select low level
	select id, id, parent_id, subtenant_type_id, name, concat( cast(id as char(200)), '_')
		from subtenant where
        id = $orgUnit -- set your arg here
	UNION ALL
    -- This is the recursive part: It joins to cte
    select c.level1_id, s.id, s.parent_id, s.subtenant_type_id, s.name, CONCAT(c.path, ',', s.id)
		from subtenant s
        inner join cte c on s.parent_id = c.id
	)
	-- select id, name, subtenant_type, parent_id
	--  cte.level1_id, cte.id, cte.parent_id, cte.subtenant_type,
	select cte.name as subname, u.*
	from cte, users u where
	u.subtenant_id = cte.id
	and u.deleted_at is null
	order by path, u.name;"));
        } else {
            $users = [];
        }
        return response()->json([
            "code" => 200,
            "data" => $users
        ]);
    }

    public function getNotificationArgs($event_id)
    {
        $notiArgs = DB::select(DB::raw("select * from event_arg arg inner join event_arg_rel rel on rel.arg_id=arg.id where rel.event_id=$event_id"));
        if (count($notiArgs) == 0) {
            $notiArgs = DB::select(DB::raw("select * from event_arg"));
        }
        $notiBroadCastLink = DB::select(DB::raw("select * from notif_event where id=$event_id"));
        return response()->json([
            "code" => 200,
            "notiArgs" => $notiArgs,
            "notiBroadCastLink" => ($notiBroadCastLink[0]->screen_name) ? url('') . '/api/v1/client/' . $notiBroadCastLink[0]->screen_name : ''
        ]);
    }

    public function notificationsaveImage(Request $request)
    {
        if (!empty($request->UploadFiles->getClientOriginalName())) {
            $fileName = $request->UploadFiles->getClientOriginalName();
            if (file_exists(public_path('richeditor') . '/' . $fileName)) {
                unlink(public_path('richeditor') . '/' . $fileName);
            }
        }
        $imageName = $fileName;
        if (!File::exists(public_path('richeditor'))) {
            File::makeDirectory(public_path("richeditor"), $mode = 0777, true, true);
        }
        $request->UploadFiles->move(public_path('richeditor'), $imageName);
    }

    public function notificationsaveAttachment(Request $request)
    {
        if (!empty($request->file->getClientOriginalName())) {
            $fileName = $request->file->getClientOriginalName();
            if (file_exists(public_path('notification_doc') . '/' . $fileName)) {
                unlink(public_path('notification_doc') . '/' . $fileName);
            }
        }

        $name = pathinfo($request->file->getClientOriginalName(), PATHINFO_FILENAME);
        //$extension = pathinfo($request->UploadFiles->getClientOriginalName(), PATHINFO_EXTENSION);
        $imageName = $name . "-" . time() . '.' . $request->file->getClientOriginalExtension(); //$fileName;

        //$imageName = $fileName;
        if (!File::exists(public_path('notification_doc'))) {
            File::makeDirectory(public_path("notification_doc"), $mode = 0777, true, true);
        }
        $request->file->move(public_path('notification_doc'), $imageName);
        return response()->json([
            "code" => 200,
            'success' => true,
            'filePath' => url('/notification_doc/' . $imageName)
            //"notiArgs" => $notiArgs,
            //"notiBroadCastLink" => ($notiBroadCastLink[0]->screen_name) ? url('') . '/api/v1/client/' . $notiBroadCastLink[0]->screen_name : ''
        ]);
    }

    public function notificationremoveAttachment(Request $request)
    {
        $fileName = explode("notification_doc/", $request->filePath);
        if (file_exists(public_path('notification_doc') . '/' . $fileName[1])) {
            unlink(public_path('notification_doc') . '/' . $fileName[1]);
        }
        return response()->json([
            "code" => 200
        ]);
    }

    /**
     * Send Manual Message
     * Notification Screen
     */
    public function sendManualMsg(Request $request)
    {

        $channel = $request->get('channel');
        $template = $request->get('template');
        $users = $request->get('users');

        $userArray = [];
        foreach ($users as $user) {
            $userArray[] = ($user['CustomerId']);
        }

        if ($channel == 'e') {

            $this->dispatch(new SendEmailJob($users, 'Test', $template));
            return response()->json([
                "code" => 200,
                "msg" => 'mail send'
            ]);
            //var_dump($notifDef);
        } else if ($channel == 's') {

            $this->dispatch(new SendSMSMessage($users, 'Test', $template));
            return response()->json([
                "code" => 200,
                "msg" => 'sms send'
            ]);
            die;
            $notifDef = NotificationDefination::where('id', $template)->get();

            $counter = 0;
            foreach ($userArray as $id) {
                $data = KicCustomerImport::where('CustomerId', $id)->where('ClientType', '!=', '')->first();

                $token = array(
                    'CLIENT_NAME' => $data->AddInfo_SMS_LangId == 1 ? $data->FullNameEn : $data->FullNameAr,
                    'CIVIL_ID_EXPIRY' => $data->CivilIdExpiry,
                    'KYC_EXPIRY' => $data->UpdatedOn,
                    'SERVICES' => null,
                    'CIVIL_ID' => $data->CivilId,
                );
                $pattern = '[%s]';
                foreach ($token as $key => $val) {
                    $varMap[sprintf($pattern, $key)] = $val;
                }

                $notiSubject = $data->AddInfo_SMS_LangId == 1 ? strtr($notifDef[0]->subject_en, $varMap) : strtr($notifDef[0]->subject_ar, $varMap);
                //die;
                //$text = '<p>Hello  [CLIENT_NAME] ,</p><p>KIC Updation Sms Test En.</p><p>Thank You</p>';

                $cleanText = str_replace(['<br>', '</p>'], PHP_EOL, strip_tags($notiSubject, '<p><br>'));

                $again = str_replace(['<p>', '<br>'], '', $cleanText);

                //echo urlencode($again);

                //die;
                $data = [
                    'UID' => 'kuincuser',
                    'P' => 'kinc153',
                    'S' => 'KICTRADE',
                    'G' => $data->Mobile, //'96551303993',
                    'M' => urlencode($again),
                    'L' => ($data->AddInfo_SMS_LangId == 1) ? 'L' : 'A'
                ];

                $postvars = '';
                $i = 1;
                foreach ($data as $key => $value) {
                    $postvars .= $key . "=" . $value . ((count($data) > $i) ? "&" : '');
                    $i++;
                }

                //  var_dump($postvars);
                //die;
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "http://62.215.226.174/fccsms_P.aspx?",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30000,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postvars, //json_encode($data),
                    // CURLOPT_HTTPHEADER => array(
                    //     // Set here requred headers
                    //     "accept: */*",
                    //     "accept-language: en-US,en;q=0.8",
                    //     "Content-Type: text/html; charset=ISO-8859-1\r\n",
                    // ),
                ));

                $response = curl_exec($curl);
                //var_dump($response);
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                    //echo 'ddd';

                    //echo "cURL Error #:" . $err;
                } else {
                    $counter++;
                    //print_r(json_decode($response));
                }
                // echo 'dd';
                // die;
            }

            return response()->json([
                "code" => 200,
                "msg" => (count($userArray) == $counter) ? 'sms send successfully' : 'sms not yet implimented'
            ]);
        }
        //var_dump($request->all());
        die;
    }

    public function getCustomerSecDep(Request $request)
    {

        $countAll = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')
            ->select(DB::raw('COUNT(1) AS EmployeesCount'), DB::raw('count(DISTINCT `kic_customerInfo_investments`.`CustomerId`) as realCount'))
            ->where('kic_customerInfo_investments.SectorId', '!=', '38')
            ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
            ->where('ClientType', '!=', '')
            //->groupBy('KICSectorid', 'KICSectorEn', 'dept', 'dept_name')
            ->get();


        //kic_customerInfo_investments.BusinessId as dept,
        //'kic_customerInfo_investments.BusinessId',
        $notiArgs = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('kic_customerInfo_investments.SectorId  as KICSectorid, kic_customerInfo_investments.KICSector as JobDescription, kic_customerInfo_investments.KICDeptId, kic_customerInfo_investments.KICDepartment as JobGroup, COUNT(*) AS EmployeesCount'))
            ->where('kic_customerInfo_investments.SectorId', '!=', '38')
            ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
            ->where('ClientType', '!=', '')
            ->groupBy('kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment')
            ->orderBy('EmployeesCount', 'DESC')
            ->get();


        $sectorCount = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('kic_customerInfo_investments.KICSector as JobDescription, kic_customerInfo_investments.SectorId as KICSectorid, COUNT(*) AS EmployeesCount, COUNT(DISTINCT kic_customerInfo_investments.KICDeptId) as deptCount'))
            ->where('kic_customerInfo_investments.SectorId', '!=', '38')
            ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
            ->where('ClientType', '!=', '')
            //->groupBy('KICSectorid', 'KICSectorEn', 'dept', 'dept_name')
            ->groupBy('kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.SectorId')
            ->orderBy('EmployeesCount', 'DESC')
            ->get();

        $arr1 = $test = [];
        $ii = 0;
        $ij = 0;
        $dataArray = [];
        foreach ($sectorCount as $sa => $sk) {
            $dataArray[$ij]['TaskID'] = $sk->KICSectorid;
            $dataArray[$ij]['TaskCount'] = number_format($sk->EmployeesCount);
            $dataArray[$ij]['TaskName'] = $sk->JobDescription;
            $dataArray[$ij]['deptCount'] = $sk->deptCount;
            $dataArray[$ij]['isParent'] = true;
            $ij++;

            if (!in_array($sk->JobDescription, $test)) {
                $newcount = ($sk->EmployeesCount / $countAll[0]->EmployeesCount) * 100;
                $arr1[$ii]['Name'] = $sk->JobDescription . ' [' . $sk->EmployeesCount . ']';;
                $arr1[$ii]['KICSectorid'] = $sk->KICSectorid;
                $arr1[$ii]['Population'] = $sk->EmployeesCount;
                $arr1[$ii]['isWhat'] = 0;
                $arr1[$ii]['Count'] =  round(($newcount) < 10 ? $newcount + 10 : $newcount);
                //(($sk->EmployeesCount > 0 && $sk->EmployeesCount < 5) ? $sk->EmployeesCount * 80 : (($sk->EmployeesCount > 5 && $sk->EmployeesCount < 20) ? $sk->EmployeesCount * 50 : (($sk->EmployeesCount >= 20 && $sk->EmployeesCount < 40) ? $sk->EmployeesCount * 10 : ($sk->EmployeesCount > 500 ? ($sk->EmployeesCount / 2) + 70 : $sk->EmployeesCount))));

                $arr2 = [];
                $test2 = [];
                $jj = 0;
                foreach ($notiArgs as $va => $vk) {

                    $getRoles = DB::select(DB::raw("SELECT GROUP_CONCAT(DISTINCT sector_id) as sector_id, GROUP_CONCAT(DISTINCT department_id) as department_id, GROUP_CONCAT(DISTINCT business_id) as business_id FROM `role_work_on_behalf_sectors` WHERE role_id='" . auth()->user()->roles->first()->id . "'"));

                    $notiArgsData = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('GROUP_CONCAT(DISTINCT kic_customerInfo_investments.BusinessId) as dept'))
                        ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                        ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                        ->where('kic_customerInfo_investments.SectorId', '=', $vk->KICSectorid)
                        ->where('kic_customerInfo_investments.KICDeptId', '=', $vk->KICDeptId)
                        ->where('ClientType', '!=', '')
                        ->get();

                    $intersection = !!array_intersect(explode(",", $notiArgsData[0]->dept), explode(",", $getRoles[0]->business_id));

                    //var_dump($getRoles[0]->sector_id);
                    //die;

                    if ($sk->JobDescription ==  $vk->JobDescription) {
                        //echo auth()->user()->sector.' =='. $vk->KICSectorid.' &&'. auth()->user()->department.' == '.$vk->KICDeptId.PHP_EOL;
                        $newcount = ($vk->EmployeesCount / $sk->EmployeesCount) * 100;
                        //$arr2[$jj]['JobDescription'] = $vk->JobDescription;
                        //$arr2[$jj]['departments'] = $vk->departments;
                        $arr2[$jj]['Name'] = $vk->JobGroup . ' [' . $vk->EmployeesCount . ']';
                        $arr2[$jj]['KICSectorid'] = $vk->KICSectorid;
                        $arr2[$jj]['dept'] = $vk->KICDeptId;
                        //$arr2[$jj]['business_id'] = $vk->dept;
                        $arr2[$jj]['isWhat'] = 1;
                        $arr2[$jj]['check']  = in_array((int)$vk->KICDeptId, explode(",", $getRoles[0]->department_id));
                        //in_array($vk->KICSectorid, [$getRoles[0]->sector_id]);
                        // && in_array($vk->KICDeptId, [$getRoles[0]->department_id]) && in_array($vk->dept, [$getRoles[0]->business_id])

                        // $arr2[$jj]['link'] = (in_array('0', explode(",", $getRoles[0]->sector_id)) && in_array('0', explode(",", $getRoles[0]->department_id))) ? 'customerList/' . $vk->KICSectorid . '/' . $vk->KICDeptId . '/' . $vk->dept : (in_array($vk->KICSectorid, explode(",", $getRoles[0]->sector_id)) && in_array($vk->KICDeptId, explode(",", $getRoles[0]->department_id)) && in_array($vk->dept, explode(",", $getRoles[0]->business_id)) ? 'customerList/' . $vk->KICSectorid . '/' . $vk->KICDeptId . '/' . $vk->dept : '#');

                        $arr2[$jj]['link'] = auth()->user()->isWhat == 0 ? 'customerList/' . $vk->KICSectorid . '/' . $vk->KICDeptId . '/' . $vk->dept : (auth()->user()->isWhat == 1 && $intersection == true ? 'customerList/' . $vk->KICSectorid . '/' . $vk->KICDeptId . '/' . $vk->dept : (auth()->user()->isWhat == 2 && $intersection == true ? '##' : '#'));

                        $arr2[$jj]['Population'] = $vk->EmployeesCount;
                        $arr2[$jj]['Count'] = round(($newcount) < 10 ? $newcount + 10 : $newcount);


                        $businessArgs = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('kic_customerInfo_investments.BusinessId as dept, kic_customerInfo_investments.Business as JobGroup, kic_customerInfo_investments.SectorId as KICSectorid, kic_customerInfo_investments.KICDeptId, COUNT(*) AS EmployeesCount'))
                            ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                            ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                            ->where('ClientType', '!=', '')
                            ->where('kic_customerInfo_investments.KICDeptId', '=', $vk->KICDeptId)
                            ->groupBy('kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.BusinessId', 'kic_customerInfo_investments.Business')
                            ->orderBy('EmployeesCount', 'DESC')
                            ->get();

                        $dataArray[$ij]['TaskID'] = $vk->KICDeptId;
                        $dataArray[$ij]['TaskCount'] = number_format($vk->EmployeesCount);
                        $dataArray[$ij]['TaskName'] = $vk->JobGroup;
                        $dataArray[$ij]['ParentItem'] = $sk->KICSectorid;
                        $dataArray[$ij]['deptCount'] = count($businessArgs) > 1 ? count($businessArgs) : '-';
                        $dataArray[$ij]['isParent'] = false;
                        $ij++;
                        $arr3 = [];
                        $kk = 0;

                        if (count($businessArgs) > 1) {
                            foreach ($businessArgs as $va => $dk) {
                                $newcount = ($dk->EmployeesCount / $vk->EmployeesCount) * 100;
                                $arr3[$kk]['Name'] = $dk->JobGroup . ' [' . $dk->EmployeesCount . ']';
                                $arr3[$kk]['KICSectorid'] = $dk->KICSectorid;
                                $arr3[$kk]['dept'] = $dk->dept;
                                $arr3[$kk]['isWhat'] = 2;
                                // $arr3[$kk]['link'] = (auth()->user()->sector == '0' && auth()->user()->department == '0') ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : (auth()->user()->sector == $vk->KICSectorid && auth()->user()->department == $vk->KICDeptId && auth()->user()->business == '0' ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : (auth()->user()->sector == $vk->KICSectorid && auth()->user()->department == $vk->KICDeptId  && auth()->user()->business ==  $dk->dept ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : '#'));

                                // $arr3[$kk]['link'] = (in_array('0', explode(",", $getRoles[0]->sector_id)) && in_array('0', explode(",", $getRoles[0]->department_id))) ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : (in_array($vk->KICSectorid, explode(",", $getRoles[0]->sector_id)) && in_array($vk->KICDeptId, explode(",", $getRoles[0]->department_id)) && in_array($vk->dept, explode(",", $getRoles[0]->business_id)) ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : '#');

                                $arr3[$kk]['link'] = auth()->user()->isWhat == 0 ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : (auth()->user()->isWhat == 1 && $intersection == true ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : (auth()->user()->isWhat == 2 && in_array($dk->dept, explode(",", $getRoles[0]->business_id)) ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : '#'));


                                // (auth()->user()->sector == '0' ||  auth()->user()->sector == $dk->KICSectorid && auth()->user()->department == $dk->KICDeptId) ? 'customerList/' . $dk->KICSectorid . '/' . $dk->KICDeptId . '/' . $dk->dept : '#';

                                $arr3[$kk]['Population'] = $dk->EmployeesCount;
                                $arr3[$kk]['Count'] = round(($newcount) < 10 ? $newcount + 10 : $newcount);
                                $kk++;

                                $dataArray[$ij]['TaskID'] = $dk->dept;
                                $dataArray[$ij]['TaskCount'] = number_format($dk->EmployeesCount);
                                $dataArray[$ij]['TaskName'] = $dk->JobGroup;
                                $dataArray[$ij]['ParentItem'] = $dk->KICDeptId;
                                $dataArray[$ij]['deptCount'] = '-';
                                $dataArray[$ij]['isParent'] = false;
                                $ij++;
                            }
                            //$arr1[$ii]['Region']['va'] = $arr3;
                        }


                        $arr2[$jj]['new'] = $arr3;
                        $jj++;
                    }
                }
                $arr1[$ii]['Region'] = $arr2;
                $test[] = $sk->JobDescription;
                $ii++;
            }
        }


        //var_dump($dataArray);
        $new['Continent'][] = [
            'Name' => "Kuwait Investment Company [" . $countAll[0]->realCount . "] [" . $countAll[0]->EmployeesCount . "]",
            'Population' => $countAll[0]->EmployeesCount,
            'Count' => 100, //$countAll[0]->EmployeesCount,
            'States' => $arr1
        ];
        // $new[0]['sectors'] = '';
        // $new[0]['JobDescription'] = '';
        // $new[0]['departments'] = '';
        // $new[0]['JobGroup'] = 'Kuwait Investment Company';


        return response()->json([
            "code" => 200,
            "data" => $new,
            "sectorCount" => $sectorCount,
            "countAll" => $countAll[0],
            "newArray" => $dataArray,
            "arr" => $notiArgs
        ]);
    }

    public function getSectorDeptArray(Request $request, $from)
    {

        $sectorCount = KicImportSector::get();
        $arr1 = $test = [];
        $ii = 0;
        $ij = 0;
        $dataArray = [];
        foreach ($sectorCount as $sa => $sk) {

            $arr1[$ii]['name'] = $sk->SectorEn;
            $arr1[$ii]['id'] = (int)$sk->SectorId;

            $arr2 = [];
            $jj = 0;
            $notiArgs = KicImportDepartment::where('SectorId', '=', $sk->SectorId)->get();
            foreach ($notiArgs as $va => $vk) {
                $arr2[$jj]['name'] = $vk->NameEn;
                $arr2[$jj]['id'] = (int)$vk->KICDeptId;

                $businessArgs = KicImportBusiness::where('sectorid', '=', $sk->SectorId)->where('KICDeptId', '=', $vk->KICDeptId)->get();

                $arr3 = [];
                $kk = 0;

                if (count($businessArgs) > 1) {
                    foreach ($businessArgs as $va => $dk) {
                        $arr3[$kk]['name'] = $dk->NAME;
                        $arr3[$kk]['id'] = (int)$dk->deptid;
                        $kk++;
                    }
                }

                if (count($arr3) > 0) {
                    $arr2[$jj]['tree'] = $arr3;
                }
                $jj++;
            }
            $arr1[$ii]['tree'] = $arr2;
            $test[] = $sk->JobDescription;
            $ii++;
        }
        $arra = [];
        if ($from == 'role') {
            $arra[0]['name'] = 'KIC All Sectors';
            $arra[0]['id'] = 0;
            $arra[0]['tree'] = $arr1;
        } else {
            $arra = $arr1;
        }

        return response()->json([
            "code" => 200,
            "data" => $arra
        ]);
    }

    public function getEmailLogs(Request $request)
    {
        $getAccess = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where `role_id`='" . auth()->user()->roles->first()->id . "'"));
        if ($getAccess[0]->sector_id != 0) {
            /*$logs = EmailLogs::join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'email_log.KICSectorId')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'email_log.KICDeptId')
                    ->orOn('role_work_on_behalf_sectors.business_ide', '=', 'email_log.KICDeptId');
            })->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->orderBy('id', 'DESC')->get();*/
            $logs = EmailLogs::orderBy('id', 'DESC')->get();
        } else {
            $logs = EmailLogs::orderBy('id', 'DESC')->get();
        }
        $newData = [];
        $i = 0;
        foreach ($logs as $val) {
            $logs[$i]['send_date'] = date('Y-m-d H:i A', strtotime($val->created_at));

            $i++;
            //echo
            //$newData[$key]->created_at = date('Y-m-d', strtotime($val->created_at));
        }
        return response()->json([
            "code" => 200,
            "data" => $logs,
        ]);
    }

    public function getSectorDept($sectorId = null, $deptId = null, $businessId = null, $fromScreen = null)
    {

        //echo gettype($sectorId);
        if (gettype($sectorId) === 'string') {
            if ($sectorId == null) {
                $sectorId = explode(",", $sectorId);
            }
        }
        if (gettype($deptId) === 'string') {
            if ($deptId == null) {
                $deptId = explode(",", $deptId);
            }
            //$deptId = [$deptId];
        }


        // $sector = KicCustomerImport::select(DB::raw('KICSectorEn as name, KICSectorid as id'))
        //     ->where('KICSectorid', '!=', '38')
        //     ->where('dept', '!=', '500')
        //     ->where('ClientType', '=', 'Individual')
        //     ->groupBy('KICSectorEn')
        //     ->groupBy('KICSectorid')
        //     ->get();

        if (auth()->user()->sector != 0 &&  $sectorId != 'null') {
            $sector = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_customerInfo_investments.SectorId')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_customerInfo_investments.KICDeptId')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_customerInfo_investments.BusinessId');
            })->select(DB::raw('kic_customerInfo_investments.KICSector as name, kic_customerInfo_investments.SectorId as id'))
                ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                ->where('ClientType', '!=', '')
                ->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)
                //->where('kic_customerInfo_investments.SectorId', '=', $sectorId)
                //->groupBy('KICSectorid', 'KICSectorEn', 'dept', 'dept_name')
                ->groupBy('kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.SectorId')
                //->orderBy('EmployeesCount', 'DESC')
                ->get();
        } else {
            $sector = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('kic_customerInfo_investments.KICSector as name, kic_customerInfo_investments.SectorId as id'))
                ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                ->where('ClientType', '!=', '')
                //->groupBy('KICSectorid', 'KICSectorEn', 'dept', 'dept_name')
                ->groupBy('kic_customerInfo_investments.KICSector', 'kic_customerInfo_investments.SectorId')
                //->orderBy('EmployeesCount', 'DESC')
                ->get();
        }
        $department = [];
        $sqlAdd = '';
        if ($sectorId != 'null') {
            // $department = KicCustomerImport::select(DB::raw('KICDeptId as id, KICDepartment as name'))
            //     ->where('KICSectorid', '!=', '38')
            //     ->where('dept', '!=', '500')
            //     ->whereIN('KICSectorid', $sectorId)
            //     ->where('ClientType', '=', 'Individual')
            //     ->groupBy('KICDepartment')
            //     ->groupBy('KICDeptId')
            //     ->get();

            if ($fromScreen == 'assigndata' && $deptId != 'null') {
                $department = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->join('role_work_on_behalf_sectors', function ($join) {
                    $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_customerInfo_investments.SectorId')
                        ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_customerInfo_investments.KICDeptId')
                        ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_customerInfo_investments.BusinessId');
                })->select(DB::raw('kic_customerInfo_investments.KICDeptId  as id, kic_customerInfo_investments.KICDepartment as name'))
                    ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                    ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                    ->whereIN('kic_customerInfo_investments.SectorId', explode(",", $sectorId))
                    //->where('kic_customerInfo_investments.KICDeptId', '=', explode(",", $deptId))
                    ->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)
                    ->where('ClientType', '!=', '')
                    ->groupBy('kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment')
                    //->orderBy('EmployeesCount', 'DESC')
                    ->get();
            } else {
                $department = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('kic_customerInfo_investments.KICDeptId  as id, kic_customerInfo_investments.KICDepartment as name'))
                    ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                    ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                    ->whereIN('kic_customerInfo_investments.SectorId', explode(",", $sectorId))
                    ->where('ClientType', '!=', '')
                    ->groupBy('kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.KICDepartment')
                    //->orderBy('EmployeesCount', 'DESC')
                    ->get();
            }

            $sqlAdd .= " and kic_customerInfo_investments.SectorId IN ($sectorId)";
        }

        $business = [];
        if ($deptId != 'null') {
            // $business = KicCustomerImport::select(DB::raw('dept_name as name, dept as id, KICSectorid, KICDeptId, COUNT(dept) AS EmployeesCount'))
            //     ->where('KICSectorid', '!=', '38')
            //     ->where('dept', '!=', '500')
            //     ->where('KICDeptId', '=', $deptId)
            //     ->where('ClientType', '=', 'Individual')
            //     ->groupBy('dept_name', 'KICSectorid', 'KICDeptId')
            //     ->groupBy('dept')
            //     ->having('EmployeesCount', '>', 1)
            //     ->get();

            // $business = KicCustomerImport::select(DB::raw('dept as id, dept_name as name, KICSectorid, KICDeptId, COUNT(*) AS EmployeesCount'))
            //     ->where('KICSectorid', '!=', '38')
            //     ->where('dept', '!=', '500')
            //     ->where('ClientType', '=', 'Individual')
            //     ->whereIN('KICDeptId', $deptId)
            //     ->groupBy('KICSectorid', 'KICDeptId', 'dept', 'dept_name')
            //     ->orderBy('EmployeesCount', 'DESC')
            //     ->get();
            if ($fromScreen == 'assigndata' && $deptId != 'null') {
                $business = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->join('role_work_on_behalf_sectors', function ($join) {
                    $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_customerInfo_investments.SectorId')
                        ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_customerInfo_investments.KICDeptId')
                        ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_customerInfo_investments.BusinessId');
                })->select(DB::raw('kic_customerInfo_investments.BusinessId as id, kic_customerInfo_investments.Business as name, kic_customerInfo_investments.SectorId as KICSectorid, kic_customerInfo_investments.KICDeptId, COUNT(*) AS EmployeesCount'))
                    ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                    ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                    ->where('ClientType', '!=', '')
                    ->where('kic_customerInfo_investments.KICDeptId', '=', explode(",", $deptId))
                    ->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)
                    ->groupBy('kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.BusinessId', 'kic_customerInfo_investments.Business')
                    ->orderBy('EmployeesCount', 'DESC')
                    ->get();
            } else {
                $business = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->select(DB::raw('kic_customerInfo_investments.BusinessId as id, kic_customerInfo_investments.Business as name, kic_customerInfo_investments.SectorId as KICSectorid, kic_customerInfo_investments.KICDeptId, COUNT(*) AS EmployeesCount'))
                    ->where('kic_customerInfo_investments.SectorId', '!=', '38')
                    ->where('kic_customerInfo_investments.BusinessId', '!=', '500')
                    ->where('ClientType', '!=', '')
                    ->where('kic_customerInfo_investments.KICDeptId', '=', explode(",", $deptId))
                    ->groupBy('kic_customerInfo_investments.SectorId', 'kic_customerInfo_investments.KICDeptId', 'kic_customerInfo_investments.BusinessId', 'kic_customerInfo_investments.Business')
                    ->orderBy('EmployeesCount', 'DESC')
                    ->get();
            }
            $sqlAdd .= " and kic_customerInfo_investments.KICDeptId IN ($deptId)";
        }

        if ($businessId != 'null' && $businessId != 'undefined') {
            $sqlAdd .= " and kic_customerInfo_investments.BusinessId IN ($businessId)";
        }

        $statement = null;
        $setId = null;
        if ($sectorId != 'null') {
            $statement = 'sector';
            $setId = $sectorId;
        }
        if ($deptId != 'null') {
            $statement = 'department';
            $setId = $deptId;
        }
        if ($businessId != 'null' && $businessId != 'undefined') {
            $statement = 'business';
            $setId = $businessId;
        }

        if (auth()->user()->sector != 0 &&  $sectorId != 'null') {
            $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->join('role_work_on_behalf_sectors', function ($join) {
                $join->on('role_work_on_behalf_sectors.sector_id', '=', 'kic_customerInfo_investments.SectorId')
                    ->on('role_work_on_behalf_sectors.department_id', '=', 'kic_customerInfo_investments.KICDeptId')
                    ->on('role_work_on_behalf_sectors.business_id', '=', 'kic_customerInfo_investments.BusinessId');
            })->with('todos')->select(
                'kic_customerInfo_investments.id',
                'kic_customerInfo_investments.CustomerId',
                'FullNameEn as name',
                'FullNameAr as name_ar',
                DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                'Email as email',
                'CivilId',
                'Mobile',
                'AddInfo_SMS_LangId',
                'CivilIdExpiry',
                'kic_customerInfo_investments.SectorId as KICSectorid',
                'kic_customerInfo_investments.BusinessId as dept',
                'kic_customerInfo_investments.KICDeptId',
                'UpdatedOn'
            )->where('ClientType', '!=', '')->where('role_work_on_behalf_sectors.role_id', auth()->user()->roles->first()->id)->get();
        } else {
            $users = KicCustomerImport::JOIN('kic_customerInfo_investments', 'kic_customerinfo_import.CustomerId', '=', 'kic_customerInfo_investments.CustomerId')->with('todos')->select(
                'kic_customerInfo_investments.id',
                'kic_customerInfo_investments.CustomerId',
                'FullNameEn as name',
                'FullNameAr as name_ar',
                DB::raw("CONCAT(FirstNameEn,' ',MiddleNameEn) AS full_name_en"),
                DB::raw("CONCAT(FirstNameAr,' ',MiddleNameAr) AS full_name_ar"),
                DB::raw("CONCAT(kic_customerInfo_investments.KICSector,'-',kic_customerInfo_investments.KICDepartment,'-',kic_customerInfo_investments.Business) AS sector_name"),
                DB::raw("CONCAT(kic_customerInfo_investments.SectorId,'-',kic_customerInfo_investments.KICDeptId,'-',kic_customerInfo_investments.BusinessId) AS sectorids"),
                'Email as email',
                'CivilId',
                'Mobile',
                'AddInfo_SMS_LangId',
                'CivilIdExpiry',
                'kic_customerInfo_investments.SectorId as KICSectorid',
                'kic_customerInfo_investments.BusinessId as dept',
                'kic_customerInfo_investments.KICDeptId',
                'UpdatedOn'
            )->where('ClientType', '!=', '')->when($statement, function ($query) use ($statement, $setId) {
                if ($statement === 'sector') {
                    return $query->whereIN('kic_customerInfo_investments.SectorId', explode(",", $setId));
                } elseif ($statement === 'department') {
                    return $query->whereIN('kic_customerInfo_investments.KICDeptId', explode(",", $setId));
                } elseif ($statement == "business") {
                    return $query->whereIN('kic_customerInfo_investments.BusinessId', explode(",", $setId));
                }
            })->get();
        }

        $countTodos = 0;
        $isTodo = [];
        foreach ($users as $use) {
            if (count($use->todos) > 0 && !in_array($use->CustomerId, $isTodo)) {
                $isTodo[] = $use->CustomerId;
                $countTodos++;
            }
        }

        $active =  DB::select(DB::raw("SELECT COUNT(DISTINCT kic_customerInfo_investments.CustomerId) as active FROM `kic_customerinfo_import` Inner Join kic_customerInfo_investments on kic_customerInfo_investments.CustomerId = kic_customerinfo_import.CustomerId  where ClientType !='' $sqlAdd"));

        $updatekyc = DB::select(DB::raw("SELECT COUNT(DISTINCT kic_customerInfo_investments.CustomerId) as updatekyc FROM `kic_customerinfo_import` Inner Join kic_customerInfo_investments on kic_customerInfo_investments.CustomerId = kic_customerinfo_import.CustomerId WHERE UpdatedOn <= DATE_SUB(DATE(DATE_Add(NOW(), INTERVAL 1 DAY)), INTERVAL 1 YEAR) and ClientType !='' $sqlAdd"));

        $CivilIdExpiryComing = DB::select(DB::raw("SELECT COUNT(DISTINCT kic_customerInfo_investments.CustomerId) as CivilIdExpiryComing  FROM `kic_customerinfo_import` Inner Join kic_customerInfo_investments on kic_customerInfo_investments.CustomerId = kic_customerinfo_import.CustomerId WHERE CivilIdExpiry > CURDATE() and  CivilIdExpiry <= DATE_ADD(CURDATE(), INTERVAL '30' DAY) and ClientType !='' $sqlAdd"));

        $CivilIdExpired = DB::select(DB::raw("SELECT COUNT(DISTINCT kic_customerInfo_investments.CustomerId) as CivilIdExpired  FROM `kic_customerinfo_import` Inner Join kic_customerInfo_investments on kic_customerInfo_investments.CustomerId = kic_customerinfo_import.CustomerId WHERE CivilIdExpiry <= CURDATE() and ClientType !='' $sqlAdd"));

        $missingInfo = DB::select(DB::raw("SELECT COUNT(DISTINCT kic_customerInfo_investments.CustomerId) as missingInfo FROM `kic_customerinfo_import` Inner Join kic_customerInfo_investments on kic_customerInfo_investments.CustomerId = kic_customerinfo_import.CustomerId WHERE (Email is Null OR Mobile is Null OR CivilIdExpiry is Null OR CivilId is Null) and ClientType !='' $sqlAdd"));
        return response()->json([
            "code" => 200,
            "sector" => $sector,
            "department" => $department,
            "business" => $business,
            "sectorId" => ($sectorId),
            "deptId" => ($deptId),
            "counts" => [
                'active' => $active[0]->active,
                'updatekyc' => $updatekyc[0]->updatekyc,
                'CivilIdExpiryComing' => $CivilIdExpiryComing[0]->CivilIdExpiryComing,
                'CivilIdExpired' => $CivilIdExpired[0]->CivilIdExpired,
                'missingInfo' => $missingInfo[0]->missingInfo,
                'countTodos' => $countTodos
            ]
        ]);
    }

    public function storeWorkBehalf($workbehalfArray, $id)
    {


        if (gettype($workbehalfArray) == 'string' || gettype($workbehalfArray) == 'integer') {
            $behalf = $workbehalfArray;
            DB::table("notif_def_work_on_behalf_sectors")->Where('categoryId', $id)->delete();
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
                    $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                        $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                        $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                        $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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

                DB::table("notif_def_work_on_behalf_sectors")->Where('categoryId', $id)->delete();
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
                        foreach ($businessArgs as $key => $val) {
                            $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                                $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                                $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                        $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
                            [
                                'sector_id' => $sectorId,
                                'department_id' => $department_id,
                                'business_id' => $business_id,
                                'categoryId' => $id,
                                'isWhat' => '2'
                            ]
                        );
                    } else {
                        $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
                                $prctype = DB::table("notif_def_work_on_behalf_sectors")->insert(
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
