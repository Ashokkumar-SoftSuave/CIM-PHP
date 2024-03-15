<?php

namespace App\Jobs;

use App\Models\EmailLogs;
use App\Models\KicCustomerImport;
use App\Models\KicReportsManagement;
use App\Models\KicReportsUpload;
use App\Models\NotificationDefination;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailto;
    protected $subject;
    protected $message;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emailto, $subject, $message)
    {
        $this->emailto = $emailto;
        $this->subject = $subject;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //  var_dump(count($this->emailto));
        // die;
        info('Demo:Cron Cummand Run successfully!');
        $notifDef = NotificationDefination::where('id', $this->message)->get();

        $getAttachment = [];
        $getAttachment = DB::table('notif_def_attachment')->select('name')->where('noti_id', $notifDef[0]->id)->get();
        if (count($this->emailto) == 0) {
            //echo 'in';
            if ($notifDef[0]->event_id == 1) {
                //SELECT * FROM `kic_customer_import` WHERE CivilIdExpiry <= DATE_SUB(CURDATE(), INTERVAL '10' DAY)
                $notificUsers = DB::select(DB::raw("SELECT * FROM `kic_customerinfo_import` WHERE CivilIdExpiry <= DATE_ADD(CURDATE(), INTERVAL '" . $notifDef[0]->civil_expiry_days . "' DAY)"));
                $this->emailto = [];
                $filesAttach = [];
                foreach ($notificUsers as $usernoti) {
                    $this->emailto[] = $usernoti->CustomerId;
                    if ($notifDef[0]->kicmanagement) {
                        $filesAttach[$usernoti->CustomerId] = $this->getFiles($notifDef, $usernoti);
                        if (count($filesAttach[$usernoti->CustomerId]) == 0) {
                            if (($key = array_search($usernoti->CustomerId, $this->emailto)) !== false) {
                                unset($this->emailto[$key]);
                            }
                        }
                    }
                }
            } else if ($notifDef[0]->event_id == 2) {
                $notificUsers = DB::select(DB::raw("SELECT * FROM `kic_customerinfo_import` WHERE UpdatedOn <= DATE_SUB(DATE(DATE_Add(NOW(), INTERVAL 1 DAY)), INTERVAL 1 YEAR)"));
                $this->emailto = [];
                $filesAttach = [];
                foreach ($notificUsers as $usernoti) {
                    $this->emailto[] = $usernoti->CustomerId;
                    if ($notifDef[0]->kicmanagement) {
                        $filesAttach[$usernoti->CustomerId] = $this->getFiles($notifDef, $usernoti);
                        if (count($filesAttach[$usernoti->CustomerId]) == 0) {
                            if (($key = array_search($usernoti->CustomerId, $this->emailto)) !== false) {
                                unset($this->emailto[$key]);
                            }
                        }
                    }
                }
            } else if ($notifDef[0]->event_id == 8) {
                $notiId = $notifDef[0]->id;
                $notificUsers = DB::select(DB::raw("SELECT * from notif_to_user where notif_id=$notiId"));

                $userNoti = [];
                $token = array();
                foreach ($notificUsers as $usernoti) {
                    $data = KicCustomerImport::where('CustomerId', $usernoti->user_id)->first();
                    $this->emailto[] = $data->CustomerId;
                    if ($notifDef[0]->kicmanagement) {
                        $filesAttach[$data->CustomerId] = $this->getFiles($notifDef, $data);
                        if (count($filesAttach[$data->CustomerId]) == 0) {
                            if (($key = array_search($data->CustomerId, $this->emailto)) !== false) {
                                unset($this->emailto[$key]);
                            }
                        }
                    }
                }
            }

            //  $getAttachment = DB::table('notif_def_attachment')->select('name')->where('noti_id', $notifDef[0]->id)->get();
            //$attachIdData = [];
            $at = 0;

            // else {
            //     $notificUsers = DB::select(DB::raw("SELECT * from notif_to_user where notif_id=$this->message"));
            // }



        } else {
            //echo 'out';
            $users = $this->emailto;


            $this->emailto = [];
            foreach ($users as $usernoti) {
                $usernoti = json_decode(json_encode($usernoti));

                $this->emailto[] = $usernoti->CustomerId; //['id'];
                if ($notifDef[0]->kicmanagement) {
                    $filesAttach[$usernoti->CustomerId] = $this->getFiles($notifDef, $usernoti);
                }
            }
        }

        //var_dump($this->emailto);
        //var_dump($filesAttach);
        //die;
        foreach ($this->emailto as $id) {


            //echo $id;
            //echo $id.PHP_EOL;
            $files = isset($filesAttach[$id]) ? $filesAttach[$id] : [];
            $data = KicCustomerImport::where('CustomerId', $id)->first();
            //echo $email = $data->Email . PHP_EOL;
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

            $notiContent = $data->AddInfo_SMS_LangId == 1 ? strtr($notifDef[0]->contents_en, $varMap) : strtr($notifDef[0]->contents_ar, $varMap);
            $notiSubject = $data->AddInfo_SMS_LangId == 1 ? strtr($notifDef[0]->subject_en, $varMap) : strtr($notifDef[0]->subject_ar, $varMap);


            $mail = Mail::send('emails.broadcastemailreports', [
                // 'USER_NAME' => 'Bhavesh',
                'Description' => $notiSubject, //$notifDef[0]->description,
                'Subject' => $notiSubject,
                'CONTENT' => $notiContent,

            ], function ($message) use ($data, $notiSubject, $getAttachment, $files) {
                $message->to($data->Email)
                    ->subject(date('dMY H:i') . strip_tags($notiSubject));

                if (count($getAttachment) > 0) {
                    foreach ($getAttachment as $attach) {
                        $message->attach($attach->name);
                    }
                }

                if (count($files) > 0) {
                    foreach ($files  as $key =>  $file) {
                        $message->attach(public_path($file));
                    }
                }
            });
            //echo '===AA' . $mail . 'BB===';
            //if($mail) {

            $emailLog = new EmailLogs();
            $emailLog->fromemail = env('MAIL_FROM_ADDRESS');
            $emailLog->toemail = $data->Email;
            $emailLog->subject = $notiSubject;
            $emailLog->body = $notiContent;
            $emailLog->channel = $notifDef[0]->channel;
            $emailLog->CustomerId = $data->CustomerId;
            $emailLog->FullNameEn = $data->FullNameEn;
            $emailLog->KICSectorId = $data->KICSectorid;
            $emailLog->KICSectorName = $data->KICSectorEn;
            $emailLog->KICDeptId = $data->dept;
            $emailLog->KICDeptName = $data->dept_name;
            $emailLog->attachments = json_encode($getAttachment);
            $emailLog->reports = json_encode($files);
            $emailLog->is_send = '1';

            $emailLog->save();
            //}
        }
    }

    public function getFiles($notifDef, $usernoti)
    {
        $kicmanagement = KicReportsManagement::with('category')->whereIn('id', explode(",", $notifDef[0]->kicmanagement))->get();
        $management = [];
        //echo count($kicmanagement);
        $ij = 0;
        $filesAttach = [];
        foreach ($kicmanagement as $k => $v) {


            if ($v->isLatestDate === 0 && $v->isUseSendDate === 0) {
                if ($v->reportSetting === 4 || $v->reportSetting === 2) {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->where('date', '<=', date('Y-m-d'))->whereBetween('date', [$v->report_from, $v->report_to])->orderBy('date', 'DESC')->get();
                } else {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->orderBy('id', 'DESC')->get();
                }
            } else if ($v->isLatestDate === 1 && $v->isUseSendDate === 0) {
                if ($v->reportSetting === 2) {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->where('date', '<=', date('Y-m-d'))->orderBy('date', 'DESC')->limit(1)->get();
                } else if ($v->reportSetting === 4) {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->where('date', '<=', date('Y-m-d'))->orderBy('date', 'DESC')->get();
                } else {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->orderBy('id', 'DESC')->limit(1)->get();
                }
            } else  if ($v->isLatestDate === 2 && $v->isUseSendDate === 0) {
                if ($v->reportSetting === 2) {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->where('date', '<=', date('Y-m-d'))->whereBetween('date', [$v->report_from, $v->report_to])->orderBy('date', 'DESC')->limit(1)->get();
                } else if ($v->reportSetting === 4) {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->where('date', '<=', date('Y-m-d'))->whereBetween('date', [$v->report_from, $v->report_to])->orderBy('date', 'DESC')->get();
                } else {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->orderBy('id', 'DESC')->limit(1)->get();
                }
            } else {
                if ($v->reportSetting === 1 || $v->reportSetting === 3) {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->get();
                } else {
                    $position = KicReportsUpload::with('category', 'reportsetting', 'reports')->where('reportId', $v->id)->where('categoryId', $v->categoryId)->where('date', '<=', date('Y-m-d'))->get();
                }
            }

            $i = 0;
            foreach ($position as $pk => $pv) {
                //var_dump($pv->reportsetting->name);
                if (strpos($pv->reportsetting->name, 'civil') !== false && strpos($pv->reportsetting->name, 'date') !== false) {
                    // echo '1' . $pv->filename . PHP_EOL;
                    $beforeName = (explode("_", $pv->filename));
                    if (count($beforeName) == 3) {
                        if ($usernoti->CivilId == $pv->civilid) {
                            if ($v->isLatestDate === 1 && $v->isUseSendDate === 0) {
                                if ($pv->date <= date('Y-m-d')) {
                                    if ($i == 0) {
                                        $filesAttach[] = $pv->filename;
                                        $i++;
                                    }
                                }
                            } else if ($v->isLatestDate === 0 && $v->isUseSendDate === 0) {
                                //echo $sendDate = explode('.', $beforeName[2])[0];
                                $sendDate = $pv->send_date;
                                $fileDate = $pv->date; //explode('.', $beforeName[2])[0];
                                // if ($sendDate == $fileDate && $sendDate == date('Y-m-d')) {
                                //     //echo 'in';
                                //     $filesAttach[] = $pv->filename;
                                // } else
                                //echo $v->report_from.'=='.date('Y-m-d').'=='.$v->report_to.'Fil'.$fileDate.PHP_EOL;
                                if (($fileDate <= date('Y-m-d')) && ($v->report_from <= $fileDate && $v->report_to >= $fileDate)) {
                                    //echo 'sss';
                                    // echo ($v->report_from <= $fileDate && $v->report_to >= $fileDate) . '--' . $v->report_from . '=' . $fileDate . '=' . $v->report_to;
                                    $filesAttach[] = $pv->filename;
                                }
                            } else if ($v->isLatestDate === 2 && $v->isUseSendDate === 0) {
                                if ($pv->date <= date('Y-m-d')) {
                                    //$filesAttach[] = $pv->filename;
                                    if ($i == 0) {
                                        $filesAttach[] = $pv->filename;
                                        $i++;
                                    }
                                }
                            } else {
                                $sendDate = $pv->send_date;
                                $fileDate = $pv->date;
                                if ($sendDate == $fileDate && $sendDate == date('Y-m-d')) {
                                    //echo 'in';
                                    $filesAttach[] = $pv->filename;
                                }
                            }
                        }
                    }
                    //die;
                } else if (strpos($pv->reportsetting->name, 'date') != false) {
                    //echo '2' . $pv->filename . PHP_EOL;;
                    $beforeName = (explode("_", $pv->filename));
                    if (count($beforeName) == 2) {
                        if ($v->isLatestDate === 1 && $v->isUseSendDate === 0) {
                            if ($pv->date <= date('Y-m-d')) {
                                if ($i == 0) {
                                    $filesAttach[] = $pv->filename;
                                    $i++;
                                }
                            }
                        } else if ($v->isLatestDate === 0 && $v->isUseSendDate === 0) {
                            $sendDate = $pv->send_date;
                            $fileDate = $pv->date; //explode('.', $beforeName[1])[0];
                            // if ($sendDate == $fileDate && $sendDate == date('Y-m-d')) {
                            //     //echo 'in';
                            //     $filesAttach[] = $pv->filename;
                            // } else
                            if (($fileDate <= date('Y-m-d')) && ($v->report_from <= $fileDate && $v->report_to >= $fileDate)) {
                                // echo ($v->report_from <= $fileDate && $v->report_to >= $fileDate) . '--' . $v->report_from . '=' . $fileDate . '=' . $v->report_to;
                                $filesAttach[] = $pv->filename;
                            }
                        } else if ($v->isLatestDate === 2 && $v->isUseSendDate === 0) {
                            if ($pv->date <= date('Y-m-d')) {
                                if ($i == 0) {
                                    $filesAttach[] = $pv->filename;
                                    $i++;
                                }
                            }
                        } else {
                            $sendDate = $pv->send_date;
                            $fileDate = $pv->date;
                            if ($sendDate == $fileDate && $sendDate == date('Y-m-d')) {
                                //echo 'in';
                                $filesAttach[] = $pv->filename;
                            }
                        }
                        //echo ($v->report_from <= $fileDate && $v->report_to >= $fileDate) . '--' . $v->report_from . '=' . $fileDate . '=' . $v->report_to;
                    }
                    //die;
                } else if (strpos($pv->reportsetting->name, 'civil') != false) {
                    // echo '3' . $pv->filename . PHP_EOL;;
                    $beforeName = (explode("_", $pv->filename));
                    if (count($beforeName) == 2) {
                        if ($usernoti->CivilId == $pv->civilid) {
                            $filesAttach[] = $pv->filename;
                        }
                    }
                } else {
                    // echo '4' . $pv->filename . PHP_EOL;
                    $filesAttach[] = $pv->filename;
                }
            }
            $ij++;
            //$management[$k]['id'] = $v->id;
            //$management[$k]['name'] = ($lang == 'en') ? $v->category->name . ' -> ' . $v->name : $v->category->name_ar . ' -> ' . $v->name_ar;
            //$management[$k]['name_ar'] = $v->category->name_ar . ' -> ' . $v->name_ar;
        }
        return $filesAttach;
    }
}
