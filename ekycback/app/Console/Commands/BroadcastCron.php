<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\KicCustomerImport;
use App\Models\NotificationDefination;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BroadcastCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:cron {notificationId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notificationId = $this->argument('notificationId');
        $checkCivilIdExpiry = NotificationDefination::where('event_id', '1')->where('channel', 'e')->where('status_active', '1')->where('id', $notificationId)->get();
        $this->commandTrigger($checkCivilIdExpiry);

        $checkKYCExpiry = NotificationDefination::where('event_id', '2')->where('channel', 'e')->where('status_active', '1')->where('id', $notificationId)->get();
        $this->commandTrigger($checkKYCExpiry);

        $customMessage = NotificationDefination::where('event_id', '8')->where('channel', 'e')->where('status_active', '1')->where('id', $notificationId)->get();
        //\DB::select(\DB::raw("SELECT * from notif_def where event_id=28 and channel='b' and status_active=1"));
        $this->commandTrigger($customMessage);

        $this->info('Successfully sent daily quote to everyone.');
        /*
           Write your database logic we bellow:
           Item::create(['name'=>'hello new']);
        */

        $this->info('Demo:Cron Cummand Run successfully!');
    }

    public function commandTrigger($checkInBoxEvent)
    {
        if (count($checkInBoxEvent) > 0) {


            $notiId = $checkInBoxEvent[0]->id;
            dispatch(new SendEmailJob($users = [], 'Test', $notiId));

            // $notificUsers = DB::select(DB::raw("SELECT * from notif_to_user where notif_id=$notiId"));

            // $userNoti = [];
            // $token = array();
            // foreach ($notificUsers as $usernoti) {

            //     $data = KicCustomerImport::where('id', $usernoti->user_id)->first();



            //     $token = array(
            //         'CLIENT_NAME' => $data->AddInfo_SMS_LangId == 1 ? $data->FullNameEn : $data->FullNameAr,
            //         'CIVIL_ID_EXPIRY' => $data->CivilIdExpiry,
            //         'KYC_EXPIRY' => $data->UpdatedOn,
            //         'SERVICES' => null,
            //         'CIVIL_ID' => $data->CivilId,
            //     );

            //     $pattern = '[%s]';
            //     foreach ($token as $key => $val) {
            //         $varMap[sprintf($pattern, $key)] = $val;
            //     }

            //     $notiContent = $data->AddInfo_SMS_LangId == 1 ? strtr($checkInBoxEvent[0]->contents_en, $varMap) : strtr($checkInBoxEvent[0]->contents_ar, $varMap);
            //     $notiSubject = $data->AddInfo_SMS_LangId == 1 ? strtr($checkInBoxEvent[0]->subject_en, $varMap) : strtr($checkInBoxEvent[0]->subject_ar, $varMap);

            //     Mail::send('emails.broadcastemailreports', [
            //         'Description' => $checkInBoxEvent[0]->description,
            //         'Subject' => $checkInBoxEvent[0]->subject,
            //         'CONTENT' => $notiContent,

            //     ], function ($mail) use ($data, $notiSubject) {

            //         $mail->to($data->Email)
            //             ->subject(strip_tags($notiSubject));

            //     });

            // }
        }
    }
}
