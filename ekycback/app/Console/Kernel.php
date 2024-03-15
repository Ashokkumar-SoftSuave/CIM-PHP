<?php

namespace App\Console;

use App\Console\Commands\kicuserCron;
use App\Models\KicCustomerImport;
use App\Models\NotificationDefination;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SoapClient;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        kicuserCron::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $checkCivilIdExpiry = NotificationDefination::where('event_id', '1')->where('channel', 'e')->where('status_active', '1')->get();
        //\DB::select(\DB::raw("SELECT * from notif_def where event_id=28 and channel='b' and status_active=1"));
        $this->commandTrigger($checkCivilIdExpiry, $schedule);

        $checkKYCExpiry = NotificationDefination::where('event_id', '2')->where('channel', 'e')->where('status_active', '1')->get();
        //\DB::select(\DB::raw("SELECT * from notif_def where event_id=28 and channel='b' and status_active=1"));
        $this->commandTrigger($checkKYCExpiry, $schedule);

        $customMessage = NotificationDefination::where('event_id', '8')->where('channel', 'e')->where('status_active', '1')->get();
        //\DB::select(\DB::raw("SELECT * from notif_def where event_id=28 and channel='b' and status_active=1"));
        $this->commandTrigger($customMessage, $schedule);

        $schedule->command('kicuser:cron')
            //->everyMinute();
            //->dailyAt('10:12');
	    ->hourlyAt('15');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    public function commandTrigger($checkInBoxEvent, Schedule $schedule)
    {
        if (count($checkInBoxEvent) > 0) {


            //echo date('H:i', strtotime($checkInBoxEvent[0]->notif_time));
            //die;
            foreach ($checkInBoxEvent as $checkEvent) {

                if ($checkEvent->is_recur == 0) {
                    $schedule->command('broadcast:cron', [$checkEvent->id])
                        //->everyMinute();//->sendOutputTo('laravel-2022-07-23.log');
                    ->dailyAt(date('H:i', strtotime($checkEvent->notif_time)));
                } else if ($checkEvent->is_recur == 1) {
                    if ($checkEvent->recur_period == 'd') {
                        $schedule->command('broadcast:cron', [$checkEvent->id])
                            //->everyMinute();//->sendOutputTo('laravel-2022-07-23.log');

                        //$checkCivilIdExpiry = NotificationDefination::where('event_id', '1')->where('channel', 'b')->where('status_active', '1')->where('id', $checkEvent->id)->get();

                        //$this->commandTrigger1($checkCivilIdExpiry);

                        ->dailyAt(date('H:i', strtotime($checkEvent->notif_time)));
                    } else if ($checkEvent->recur_period == 'w') {
                        $schedule->command('broadcast:cron', [$checkEvent->id])
                            ->weeklyOn($checkEvent->recur_dow, date('H:i', strtotime($checkEvent->notif_time)));
                    } else if ($checkEvent->recur_period == 'm') {
                        $workingDays = $this->getWorkingDays(date('Y'), date('m'));

                        if ($checkEvent->recur_m_condition == 'l') {
                            $schedule->command('broadcast:cron', [$checkEvent->id])
                                ->monthlyOn(end($workingDays), date('H:i', strtotime($checkEvent->notif_time)));
                        } else if ($checkEvent->recur_m_condition == 'f') {
                            $schedule->command('broadcast:cron', [$checkEvent->id])
                                ->monthlyOn(current($workingDays), date('H:i', strtotime($checkEvent->notif_time)));
                        } else {
                            $schedule->command('broadcast:cron', [$checkEvent->id])
                                ->monthlyOn($checkEvent->recur_dom, date('H:i', strtotime($checkEvent->notif_time)));
                        }
                    } else if ($checkEvent->recur_period == 'q') {
                        // $dateCurrent = date('d');
                        // $monthCurrent = date('m');
                        // $fiscalYears = DB::select(DB::raw("SELECT * FROM `notif_fiscal_quarter` where tenant_id=1 and CURDATE() >= start_at and CURDATE() <= end_at"));
                        $fiscal =
                            [
                                [
                                    'start_date' => date('Y') . '-01-01',
                                    'end_date' => date('Y') . '-03-' . date('t', strtotime(date('Y') . '-03-01'))
                                ],
                                [
                                    'start_date' => date('Y') . '-04-01',
                                    'end_date' => date('Y') . '-06-' . date('t', strtotime(date('Y') . '-06-01'))
                                ],
                                [
                                    'start_date' => date('Y') . '-07-01',
                                    'end_date' => date('Y') . '-09-' . date('t', strtotime(date('Y') . '-09-01'))
                                ],
                                [
                                    'start_date' => date('Y') . '-10-01',
                                    'end_date' => date('Y') . '-12-' . date('t', strtotime(date('Y') . '-12-01'))
                                ]
                            ];


                        if ($checkEvent->recur_q_condition == 'l') {
                            $endDate = '';
                            foreach ($fiscal as $val) {
                                //echo date('Y-m-d') . ' >= ' . $val['start_date'] . '&& ' . date('Y-m-d') . '<= ' . $val['end_date'] . PHP_EOL;
                                if (date('Y-m-d') >= $val['start_date'] && date('Y-m-d') <= $val['end_date']) {

                                    $endDate = $val['end_date'];
                                    $fiscalYearsMonth = date('m', strtotime($val['end_date']));
                                    $fiscalYearsDay = date('d', strtotime($val['end_date']));
                                }
                            }
                            $fiscalWorkingDays = $this->getQarterDays(date('Y'), $fiscalYearsMonth, $fiscalYearsDay);
                            if (date('m-d') == date('m', strtotime($endDate)) . '-' . sprintf("%02d", end($fiscalWorkingDays))) {
                                $schedule->command('broadcast:cron', [$checkEvent->id])
                                    ->monthlyOn(end($fiscalWorkingDays), date('H:i', strtotime($checkEvent->notif_time)));
                            }
                        } else if ($checkEvent->recur_q_condition == 'f') {
                            // $fiscalYears = DB::select(DB::raw("SELECT * FROM `notif_fiscal_quarter` where tenant_id=1 and CURDATE() <= start_at"));
                            // $fiscalYearsMonth = date('m', strtotime($fiscalYears[0]->end_at));
                            // $fiscalYearsDay = date('d', strtotime($fiscalYears[0]->end_at));

                            $endDate = '';
                            foreach ($fiscal as $val) {
                                //echo date('Y-m-d') . ' >= ' . $val['start_date'] . '&& ' . date('Y-m-d') . '<= ' . $val['end_date'] . PHP_EOL;
                                if (date('Y-m-d') <= $val['start_date']) {

                                    $endDate = $val['end_date'];
                                    $fiscalYearsMonth = date('m', strtotime($val['end_date']));
                                    $fiscalYearsDay = date('d', strtotime($val['end_date']));
                                }
                            }

                            $fiscalWorkingDays = $this->getQarterDays(date('Y'), $fiscalYearsMonth, $fiscalYearsDay);
                            if (date('m-d') == date('m', strtotime($endDate)) . '-' . sprintf("%02d", current($fiscalWorkingDays))) {
                                $schedule->command('broadcast:cron', [$checkEvent->id])
                                    ->monthlyOn(current($fiscalWorkingDays), date('H:i', strtotime($checkEvent->notif_time)));
                            }
                        } else if ($checkEvent->recur_q_condition == 'a') {
                            // $fiscalYears = DB::select(DB::raw("SELECT * FROM `notif_fiscal_quarter` where tenant_id=1 and CURDATE() <= start_at"));
                            // $fiscalYearsMonth = date('m', strtotime($fiscalYears[0]->end_at));
                            // $fiscalYearsDay = date('d', strtotime($fiscalYears[0]->end_at));

                            $endDate = '';
                            foreach ($fiscal as $val) {
                                //echo date('Y-m-d') . ' >= ' . $val['start_date'] . '&& ' . date('Y-m-d') . '<= ' . $val['end_date'] . PHP_EOL;
                                if (date('Y-m-d') <= $val['start_date']) {

                                    $endDate = $val['end_date'];
                                    $fiscalYearsMonth = date('m', strtotime($val['end_date']));
                                    $fiscalYearsDay = date('d', strtotime($val['end_date']));
                                }
                            }

                            $fiscalWorkingDays = $this->getQarterDays(date('Y'), $fiscalYearsMonth, $fiscalYearsDay);
                            $dateSend = ($checkEvent->recur_qe_diff_days) ? $checkEvent->recur_qe_diff_days :
                                current($fiscalWorkingDays);
                            if (date('m-d') == date('m', strtotime($endDate)) . '-' . sprintf(
                                "%02d",
                                $dateSend
                            )) {
                                $schedule->command('broadcast:cron', [$checkEvent->id])
                                    ->monthlyOn($dateSend, date('H:i', strtotime($checkEvent->notif_time)));
                            }
                        } else if ($checkEvent->recur_q_condition == 'b') {
                            // $fiscalYears = DB::select(DB::raw("SELECT * FROM `notif_fiscal_quarter` where tenant_id=1 and CURDATE() >= start_at and CURDATE() <= end_at"));
                            // //var_dump($fiscalYears[0]->end_at);
                            // //die;
                            // $fiscalYearsMonth = date('m', strtotime($fiscalYears[0]->end_at));
                            // $fiscalYearsDay = date('d', strtotime($fiscalYears[0]->end_at));
                            $endDate = '';
                            foreach ($fiscal as $val) {
                                //echo date('Y-m-d') . ' >= ' . $val['start_date'] . '&& ' . date('Y-m-d') . '<= ' . $val['end_date'] . PHP_EOL;
                                if (date('Y-m-d') >= $val['start_date'] && date('Y-m-d') <= $val['end_date']) {

                                    $endDate = $val['end_date'];
                                    $fiscalYearsMonth = date('m', strtotime($val['end_date']));
                                    $fiscalYearsDay = date('d', strtotime($val['end_date']));
                                }
                            }

                            $fiscalWorkingDays = $this->getQarterDays(date('Y'), $fiscalYearsMonth, $fiscalYearsDay);
                            $dateSend = ($checkEvent->recur_qe_diff_days) ? $checkEvent->recur_qe_diff_days :
                                end($fiscalWorkingDays);
                            if (date('m-d') == date('m', strtotime($endDate)) . '-' . sprintf(
                                "%02d",
                                $dateSend
                            )) {

                                $schedule->command('broadcast:cron', [$checkEvent->id])
                                    ->monthlyOn($dateSend, date('H:i', strtotime($checkEvent->notif_time)));
                            }
                        }
                    }
                }
            }
        }
    }

    public function commandTrigger1($checkInBoxEvent)
    {

        if (count($checkInBoxEvent) > 0) {

            $notiId = $checkInBoxEvent[0]->id;
            $notificUsers = DB::select(DB::raw("SELECT * from notif_to_user where notif_id=$notiId"));

            $userNoti = [];
            $token = array();
            foreach ($notificUsers as $usernoti) {

                $data = KicCustomerImport::where('id', $usernoti->user_id)->first();



                $token = array(
                    'CLIENT_NAME' => $data->AddInfo_SMS_LangId == 1 ? $data->FullNameEn : $data->FullNameAr,
                    'CIVIL_ID_EXPIRY' => $data->CivilIdExpiry,
                    'KYC_EXPIRY' => $data->UpdatedOn,
                    'SERVICES' => null,
                    'CIVIL_ID' => $data->CivilId,
                );

                //$userNoti[] = $usernoti->user_id;
                $pattern = '[%s]';
                foreach ($token as $key => $val) {
                    $varMap[sprintf($pattern, $key)] = $val;
                }

                $notiContent = $data->AddInfo_SMS_LangId == 1 ? strtr($checkInBoxEvent[0]->contents_en, $varMap) : strtr($checkInBoxEvent[0]->contents_ar, $varMap);
                $notiSubject = $data->AddInfo_SMS_LangId == 1 ? strtr($checkInBoxEvent[0]->subject_en, $varMap) : strtr($checkInBoxEvent[0]->subject_ar, $varMap);

                //$users = User::whereIn('id', $userNoti)->get();
                //foreach ($users as $user) {
                //Mail::raw($notiContent, function ($mail) use ($data) {
                Mail::send('emails.broadcastemailreports', [
                    'Description' => $checkInBoxEvent[0]->description,
                    'Subject' => $checkInBoxEvent[0]->subject,
                    'CONTENT' => $notiContent,

                ], function ($mail) use ($data, $notiSubject) {
                    //$mail->from('bhaveshdarji386@gmail.com');
                    $mail->to($data->Email)
                        ->subject(strip_tags($notiSubject));
                    //->attachData($pdf->output(), "text.pdf");
                });
                //}
            }
        }
    }

    public function getWorkingDays($year, $month)
    {

        $workdays = [];
        for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $month, $year); $i++) {

            $date = $year . '/' . $month . '/' . $i; //format date
            $get_name = date('l', strtotime($date)); //get week day
            $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars

            //if not a weekend add day to array
            if ($day_name != 'Fri' && $day_name != 'Sat') {
                $workdays[] = $i;
            }
        }
        return $workdays;
    }

    public function getQarterDays($year, $month, $day)
    {
        $getDays = $this->getWorkingDays($year, $month);
        return $getDays;
    }
}
