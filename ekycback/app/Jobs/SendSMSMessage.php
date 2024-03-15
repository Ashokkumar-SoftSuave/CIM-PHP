<?php

namespace App\Jobs;

use App\Models\EmailLogs;
use App\Models\KicCustomerImport;
use App\Models\NotificationDefination;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendSMSMessage implements ShouldQueue
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
        info('Demo:Cron Cummand Run successfully!');
        $notifDef = NotificationDefination::where('id', $this->message)->get();

        //$getAttachment = [];
        //$getAttachment = DB::table('notif_def_attachment')->select('name')->where('noti_id', $notifDef[0]->id)->get();
        if (count($this->emailto) == 0) {
            //echo 'in';
            if ($notifDef[0]->event_id == 1) {
                //SELECT * FROM `kic_customer_import` WHERE CivilIdExpiry <= DATE_SUB(CURDATE(), INTERVAL '10' DAY)
                $notificUsers = DB::select(DB::raw("SELECT * FROM `kic_customerinfo_import` WHERE CivilIdExpiry <= DATE_ADD(CURDATE(), INTERVAL '" . $notifDef[0]->civil_expiry_days . "' DAY)"));
                $this->emailto = [];
                $filesAttach = [];
                foreach ($notificUsers as $usernoti) {
                    $this->emailto[] = $usernoti->CustomerId;
                }
            } else if ($notifDef[0]->event_id == 2) {
                $notificUsers = DB::select(DB::raw("SELECT * FROM `kic_customerinfo_import` WHERE UpdatedOn <= DATE_SUB(DATE(DATE_Add(NOW(), INTERVAL 1 DAY)), INTERVAL 1 YEAR)"));
                $this->emailto = [];
                $filesAttach = [];
                foreach ($notificUsers as $usernoti) {
                    $this->emailto[] = $usernoti->CustomerId;
                }
            } else if ($notifDef[0]->event_id == 8) {
                $notiId = $notifDef[0]->id;
                $notificUsers = DB::select(DB::raw("SELECT * from notif_to_user where notif_id=$notiId"));

                $userNoti = [];
                $token = array();
                foreach ($notificUsers as $usernoti) {
                    $data = KicCustomerImport::where('CustomerId', $usernoti->user_id)->first();
                    $this->emailto[] = $data->CustomerId;
                }
            }
        } else {
            //echo 'out';
            $users = $this->emailto;
            $this->emailto = [];
            foreach ($users as $usernoti) {
                $usernoti = json_decode(json_encode($usernoti));
                $this->emailto[] = $usernoti->CustomerId; //['id'];
            }
        }

        //var_dump($this->emailto);
        //var_dump($filesAttach);
        //die;
        $counter = 0;
        foreach ($this->emailto as $id) {


            $data = KicCustomerImport::where('CustomerId', $id)->where('ClientType', '=', 'Individual')->first();

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
            $dataSms = [
                'UID' => 'kuincuser',
                'P' => 'kinc153',
                'S' => 'KICTRADE',
                'G' => '965'.$data->Mobile, //'96551303993',
                'M' => urlencode($again),
                'L' => ($data->AddInfo_SMS_LangId == 1) ? 'L' : 'A'
            ];

            $postvars = '';
            $i = 1;
            foreach ($dataSms as $key => $value) {
                $postvars .= $key . "=" . $value . ((count($dataSms) > $i) ? "&" : '');
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

            $emailLog = new EmailLogs();
            $emailLog->fromemail = env('MAIL_FROM_ADDRESS');
            $emailLog->toemail = $data->Email;
            $emailLog->subject = $notiSubject;
            $emailLog->body = '';
            $emailLog->channel = $notifDef[0]->channel;
            $emailLog->CustomerId = $data->CustomerId;
            $emailLog->FullNameEn = $data->FullNameEn;
            $emailLog->KICSectorId = $data->KICSectorid;
            $emailLog->KICSectorName = $data->KICSectorEn;
            $emailLog->KICDeptId = $data->dept;
            $emailLog->KICDeptName = $data->dept_name;
            $emailLog->attachments = '';
            $emailLog->reports = '';
            $emailLog->is_send = '1';

            $emailLog->save();
            //}
        }
    }
}
