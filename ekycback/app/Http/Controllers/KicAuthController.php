<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SoapClient;

class KicAuthController extends Controller
{
    public function authenticatePACI(Request $request)
    {
        $civilid =  $request->get('civilid');
        //'279051603654'
        //die;
        $soap = new SoapClient(
            'http://kicpaci/ClientService.svc?singleWsdl',
            array(
                'soap_version' => 'SOAP_1_2',
                'location' => 'http://kicpaci/ClientService.svc',
            )
        );
        $response = $soap->InitiateAuthenticateRequest(['civilid' => $civilid, 'serviceid' => 1]);

        return response()->json(
            [
                "code" => (strlen(json_decode($response->InitiateAuthenticateRequestResult))) > 1 ? '200' : '200',
                "msg" => 'waiting_for_authentication',
                "token" => (json_decode($response->InitiateAuthenticateRequestResult)),
                'time' => date('Y-m-d H:i:s', strtotime('+5 minutes'))
            ]
        );
    }

    public function GetResponseDetail(Request $request)
    {
        $pacitoken =  $request->get('pacitoken');
        $soap = new SoapClient(
            'http://kicpaci/ClientService.svc?wsdl',
            array(
                'soap_version' => 'SOAP_1_2',
                "trace" => 1,
                "exceptions" => 1,
                'location' => 'http://kicpaci/ClientService.svc',
            )
        );

        $response = $soap->GetResponseDetail(['RequestId' => $pacitoken]);

        $isPanding = 'false';
        $isKicCustomer = [];
        $clientId = null;
        if ((strlen(($response->GetResponseDetailResult))) > 1) {
            $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);

            $ekyc = DB::table('kic_customerinfo')
                ->select('kic_customerinfo.CustomerId', 'kic_customerinfo.ekycstatus as kicstatus')
                ->where('CivilId', '=', $paciData->CivilID)
                //->whereNull('responseAfterPost')
                ->latest()
                ->get();
            $isKicCustomer = $soap->IsKICCustomer(['CivilId' => $paciData->CivilID]);
            if (count($ekyc) > 0) {
                $clientId = $ekyc[0]->CustomerId;
                //0 pending 1 Approved 2 Rejected 3 inform customer 4 uncompleted
                //var_dump($ekyc);
                $isPanding = $ekyc[0]->kicstatus == '0' ? 'pending' : ($ekyc[0]->kicstatus == '1' ? 'Approved' : ($ekyc[0]->kicstatus == '2' ? 'Rejected' : ($ekyc[0]->kicstatus == '3' ? 'inform' : 'uncompleted')));
            }
        }
        return response()->json(
            [
                "code" => (strlen(($response->GetResponseDetailResult))) > 1 ? '200' : '201',
                "msg" => ($isPanding == 'pending') ? 'pending_for_approvel' : (($isPanding == 'uncompleted') ? 'uncompleted_form' : (($isPanding == 'Rejected') ? 'rejected' : ((strlen(($response->GetResponseDetailResult))) > 1 ? 'approved' : (($response->GetResponseDetailResult) == 4 ? 'rejected' : 'not_yet_approved')))),
                "details" => json_decode($response->GetResponseDetailResult),
                "status" => $isPanding,
                "clientId" => base64_encode($clientId),
                "isPanding" => !empty($isKicCustomer) && ($isKicCustomer->IsKICCustomerResult) ? 'iskicuncompleted' : $isPanding,
                "isKicClient" => !empty($isKicCustomer) && $isKicCustomer->IsKICCustomerResult
            ]
        );
    }

    public function IsKICCustomer(Request $request)
    {
        $soap = new SoapClient(
            'http://kicpaci/ClientService.svc?singleWsdl',
            array(
                'soap_version' => 'SOAP_1_2',
                'location' => 'http://kicpaci/ClientService.svc',
            )
        );
        $response = $soap->IsKICCustomer(['CivilId' => '279051603654']);

        return response()->json(
            [
                "code" => 200,
                "response" => $response
            ]
        );
    }

    public function GetCustomerDetail(Request $request)
    {
        $soap = new SoapClient(
            'http://kicpaci/ClientService.svc?singleWsdl',
            array(
                'soap_version' => 'SOAP_1_2',
                'location' => 'http://kicpaci/ClientService.svc',
            )
        );
        $response = $soap->GetCustomerDetail(['CivilId' => '279051603654']);

        return response()->json(
            [
                "code" => 200,
                "response" => $response
            ]
        );
    }

    public function PostCustomerDetail(Request $request)
    {
        $soap = new SoapClient(
            'http://kicpaci/ClientService.svc?singleWsdl',
            array(
                'soap_version' => 'SOAP_1_2',
                'location' => 'http://kicpaci/ClientService.svc',
            )
        );
        $response = $soap->PostCustomerDetail(['civilid' => '290050510365', 'serviceid' => 1]);

        return response()->json(
            [
                "code" => 200,
                "response" => $response
            ]
        );
    }
}
