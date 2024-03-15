<?php

namespace App\Console\Commands;

use App\Models\KicCustomerImport;
use App\Models\KicCustomerImportInvesement;
use App\Models\KicImportSector;
use App\Models\KicImportDepartment;
use App\Models\KicImportBusiness;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SoapClient;

class kicuserCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kicuser:cron';

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
        Log::info("Cron is working fine!");
        $this->commandTrigger();
    }

    public function commandTrigger()
    {
        // $soap = new SoapClient(
        //     'http://kicpaci/ClientService.svc?singleWsdl',
        //     array(
        //         'soap_version' => 'SOAP_1_2',
        //         'location' => 'http://kicpaci/ClientService.svc',
        //     )
        // );

        // $getResults = $soap->GetCustomerForNotification()->GetCustomerForNotificationResult;
        // $getResultsDecoded = json_decode($getResults);
        // if (count($getResultsDecoded) > 0) {
        //     KicCustomerImport::query()->update(['checkStatus' => '0']);

        //     //DB::table('table')->update(array('confirmed' => 1));
        //     foreach ($getResultsDecoded as $key => $val) {
        //         //if ($val->CivilId != '274082500049') {
        //             $kiccustomer = KicCustomerImport::where('CivilId', $val->CivilId)->first();
        //             if (!$kiccustomer) {
        //                 $kiccustomer = new KicCustomerImport();
        //             }

        //             foreach ($val as $k => $v) {
        //                 $kiccustomer[$k] = $v;
        //                 $kiccustomer['checkStatus'] = '1';
        //             }
        //             $kiccustomer->save();
        //         //}

        //     }
        //     KicCustomerImport::where('checkStatus', '0')->delete();
        // }

        $soap = new SoapClient(
            'http://kicpaci/ClientService.svc?singleWsdl',
            array(
                'soap_version' => 'SOAP_1_2',
                'location' => 'http://kicpaci/ClientService.svc',
            )
        );

        $getResults = $soap->GetAllCustomers()->GetAllCustomersResult;
        $getResultsDecoded = json_decode($getResults);

        if (count((array)$getResultsDecoded) > 0) {
            //var_dump($getResultsDecoded->CustomerInfo);
            //die;
            if (count($getResultsDecoded->CustomerInfo) > 0) {
                KicCustomerImport::query()->update(['checkStatus' => '0']);

                //DB::table('table')->update(array('confirmed' => 1));
                foreach ($getResultsDecoded->CustomerInfo as $key => $val) {
                    //if ($val->CivilId != '274082500049') {
                    $kiccustomer = KicCustomerImport::where('CivilId', $val->CivilId)->Where('CustomerId', $val->CustomerId)->first();
                    if (!$kiccustomer) {
                        $kiccustomer = new KicCustomerImport();
                    }

                    foreach ($val as $k => $v) {
                        $kiccustomer[$k] = $v;
                        $kiccustomer['checkStatus'] = '1';
                    }
                    $kiccustomer->save();
                    //}

                }
                KicCustomerImport::where('checkStatus', '0')->delete();
            }

            if (count($getResultsDecoded->CustomerInvestments) > 0) {
                KicCustomerImportInvesement::query()->update(['checkStatus' => '0']);

                //DB::table('table')->update(array('confirmed' => 1));
                foreach ($getResultsDecoded->CustomerInvestments as $key => $val) {
                    //if ($val->CivilId != '274082500049') {
                    $kiccustomer = KicCustomerImportInvesement::where('BusinessId', $val->BusinessId)->Where('SectorId', $val->SectorId)->where('KICDeptId', $val->KICDeptId)->Where('CustomerId', $val->CustomerId)->first();
                    if (!$kiccustomer) {
                        $kiccustomer = new KicCustomerImportInvesement();
                    }

                    foreach ($val as $k => $v) {
                        $kiccustomer[$k] = $v;
                        $kiccustomer['checkStatus'] = '1';
                    }
                    $kiccustomer->save();
                    //}

                }
                KicCustomerImportInvesement::where('checkStatus', '0')->delete();
            }

            if (count($getResultsDecoded->KICSectors) > 0) {
                KicImportSector::query()->update(['checkStatus' => '0']);

                foreach ($getResultsDecoded->KICSectors as $key => $val) {
                    $kicimportsector = KicImportSector::where('SectorId', $val->SectorId)->first();
                    if (!$kicimportsector) {
                        $kicimportsector = new KicImportSector();
                    }

                    foreach ($val as $k => $v) {
                        $kicimportsector[$k] = $v;
                        $kicimportsector['checkStatus'] = '1';
                    }
                    $kicimportsector->save();
                }
                KicImportSector::where('checkStatus', '0')->delete();
            }

            if (count($getResultsDecoded->KICDepartments) > 0) {
                KicImportDepartment::query()->update(['checkStatus' => '0']);

                foreach ($getResultsDecoded->KICDepartments as $key => $val) {

                    $kicimportdepartment = KicImportDepartment::where('SectorId', $val->SectorId)->where('KICDeptId', $val->KICDeptId)->first();
                    if (!$kicimportdepartment) {
                        $kicimportdepartment = new KicImportDepartment();
                    }

                    foreach ($val as $k => $v) {
                        $kicimportdepartment[$k] = $v;
                        $kicimportdepartment['checkStatus'] = '1';
                    }
                    $kicimportdepartment->save();
                }
                KicImportDepartment::where('checkStatus', '0')->delete();
            }

            if (count($getResultsDecoded->Businesses) > 0) {
                KicImportBusiness::query()->update(['checkStatus' => '0']);

                foreach ($getResultsDecoded->Businesses as $key => $val) {
                    $kicimportbusiness = KicImportBusiness::where('deptid', $val->deptid)->where('sectorid', $val->sectorid)->where('KICDeptId', $val->KICDeptId)->first();
                    if (!$kicimportbusiness) {
                        $kicimportbusiness = new KicImportBusiness();
                    }

                    foreach ($val as $k => $v) {
                        $kicimportbusiness[$k] = $v;
                        $kicimportbusiness['checkStatus'] = '1';
                    }
                    $kicimportbusiness->save();
                }
                KicImportBusiness::where('checkStatus', '0')->delete();
            }
        }
        //Log::info("Cron is working fine!".json_encode($soap->GetCustomerForNotification()));
        ///var_dump($soap->GetCustomerForNotification()->GetCustomerForNotificationResult);
        //die;
    }
}
