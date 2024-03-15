<?php

namespace App\Jobs;

use App\Models\EmailLogs;
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

class SendSmsCustomer implements ShouldQueue
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

        $getAttachment = [];

        $getAttachment = DB::table('notif_def_attachment')->select('name')->where('noti_id', $notifDef[0]->id)->get();
        $counter = 0;
        foreach ($this->emailto as $id) {

            $data = DB::table('kic_customerinfo')
                ->where('kic_customerinfo.CustomerId', '=', $id)
                ->get();;

            $token = array(
                'CLIENT_NAME' => $data[0]->AddInfo_SMS_LangId == 1 ? $data[0]->FullNameEn : $data[0]->FullNameAr,
                'CIVIL_ID_EXPIRY' => $data[0]->CivilIdExpiry,
                'KYC_EXPIRY' => $data[0]->UpdatedOn,
                'SERVICES' => null,
                'REJECT_REASON' => $this->subject,
                'CIVIL_ID' => $data[0]->CivilId,
            );
            $pattern = '[%s]';
            foreach ($token as $key => $val) {
                $varMap[sprintf($pattern, $key)] = $val;
            }

            $notiSubject = $data[0]->AddInfo_SMS_LangId == 1 ? strtr($notifDef[0]->subject_en, $varMap) : strtr($notifDef[0]->subject_ar, $varMap);

            $cleanText = str_replace(['<br>', '</p>'], PHP_EOL, strip_tags($notiSubject, '<p><br>'));

            $again = str_replace(['<p>', '<br>'], '', $cleanText);

            $dataSms = [
                'UID' => 'kuincuser',
                'P' => 'kinc153',
                'S' => 'KICTRADE',
                'G' => '965' . $data[0]->Mobile, //'96551303993',
                'M' => urlencode($again),
                'L' => ($data[0]->AddInfo_SMS_LangId == 1) ? 'L' : 'A'
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
            $emailLog->toemail = $data[0]->Email;
            $emailLog->subject = $notiSubject;
            $emailLog->body = '';
            $emailLog->channel = $notifDef[0]->channel;
            $emailLog->CustomerId = $data[0]->CustomerId;
            $emailLog->FullNameEn = $data[0]->FullNameEn;
            $emailLog->KICSectorId = ($data[0]->KICSectorid) ? $data[0]->KICSectorid : '';
            $emailLog->KICSectorName = ($data[0]->KICSectorEn) ? $data[0]->KICSectorEn : '';
            $emailLog->KICDeptId = ($data[0]->dept) ? $data[0]->dept : '';
            $emailLog->KICDeptName = ($data[0]->dept_name) ? $data[0]->dept_name : '';
            $emailLog->attachments = '';
            $emailLog->reports = '';
            $emailLog->is_send = '1';

            $emailLog->save();
            //}
        }
    }
}
