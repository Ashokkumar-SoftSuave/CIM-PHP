<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailCustomer;
use App\Jobs\SendEmailJob;
use App\Jobs\SendSmsCustomer;
use App\Models\Dynamic;
use App\Models\KicCustomerDocument;
use App\Models\KicCustomerinfoBusiness;
use App\Models\KicCustomerinfoCif;
use App\Models\KicCustomerSignature;
use App\Models\KicCustomerUserImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use PDO;
use SoapClient;

class formGeneratorController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // }

    public function index($type)
    {
        # code...

        if ($type == '0') {
            $ekyc = DB::table('kic_customerinfo')
                ->join('kic_titles', 'kic_titles.TitleId', '=', 'kic_customerinfo.TitleId')
                ->leftJoin('kic_customerinfo_cif', 'kic_customerinfo_cif.customerId', '=', 'kic_customerinfo.CustomerId')
                ->leftJoin('kic_customerinfo_signature', 'kic_customerinfo_signature.customerId', '=', 'kic_customerinfo.CustomerId')
                ->leftJoin('kic_customerinfo_document', 'kic_customerinfo_document.customerId', '=', 'kic_customerinfo.CustomerId')
                ->select('kic_customerinfo.CustomerId', DB::raw("CONCAT(kic_customerinfo.FullNameEn) AS FullNameEn"), DB::raw("CONCAT(kic_customerinfo.FullNameAr) AS FullNameAr"), DB::raw("CONCAT(kic_customerinfo.FirstNameEn,' ',kic_customerinfo.MiddleNameEn, ' ', kic_customerinfo.FamilyNameEn) AS full_name"), 'kic_customerinfo.CivilId', 'kic_customerinfo.ekycstatus as kicstatus', 'kic_customerinfo.created_at', DB::raw('(case when kic_customerinfo_cif.customerId is not null then 1 else 0 end) as cif'), DB::raw('(case when kic_customerinfo_signature.customerId is not null then 1 else 0 end) as sign'), DB::raw('(case when kic_customerinfo_document.customerId is not null then 1 else 0 end) as document'))
                //->whereNull('deleted_at')
                //DB::raw("(SELECT COUNT(1) FROM kic_customerinfo_cif where kic_customerinfo_cif.customerId = kic_customerinfo.CustomerId) as kiccif")
                ->distinct()
                ->get();
            if ($ekyc) {

                $ekycsectors = DB::table('t24sectors')->select('DESCRIPTION as name', 'sectorid as id')->get();

                $ekycdepartment = DB::table('kic_import_business')->select('NAME as name', 'deptid as id')->whereNotIn('deptid', [850, 920])->get();

                $ekycindustry = DB::table('kic_industry')->select('IndustryEn as name', 'id')->get();

                $ekyctargets = DB::table('kic_targets')->select('TargetEn as name', 'TargetId as id')->get();

                $ekyccustometstatus = DB::table('kic_customer_status')->select('StatusEn as name', 'id')->get();

                $counts = DB::select(DB::raw("SELECT SUM(if(ekycstatus = '0', 1, 0)) AS pending, SUM(if(ekycstatus = '1', 1, 0)) AS approved, SUM(if(ekycstatus = '2', 1, 0)) AS rejected, SUM(if(ekycstatus = '3', 1, 0)) AS inform, SUM(if(ekycstatus = '4', 1, 0)) AS uncompleted, SUM(if(ekycstatus = '5', 1, 0)) AS pendingsign  FROM `kic_customerinfo`"));
                return response()->json([
                    "code" => 200,
                    "ekyc" => $ekyc,
                    "counts" => $counts,
                    "ekycsectors" => $ekycsectors,
                    "ekycdepartment" => $ekycdepartment,
                    "ekycindustry" => $ekycindustry,
                    "ekyctargets" => $ekyctargets,
                    "ekyccustometstatus" => $ekyccustometstatus
                ]);
            }
        } else {
            $ekyc = DB::table('kic_customerinfo')
                ->join('kic_titles', 'kic_titles.TitleId', '=', 'kic_customerinfo.TitleId')
                ->leftJoin('kic_customerinfo_cif', 'kic_customerinfo_cif.customerId', '=', 'kic_customerinfo.CustomerId')
                ->leftJoin('kic_customerinfo_signature', 'kic_customerinfo_signature.customerId', '=', 'kic_customerinfo.CustomerId')
                ->leftJoin('kic_customerinfo_document', 'kic_customerinfo_document.customerId', '=', 'kic_customerinfo.CustomerId')
                ->select('kic_customerinfo.CustomerId', DB::raw("CONCAT(kic_customerinfo.FullNameEn) AS FullNameEn"), DB::raw("CONCAT(kic_customerinfo.FullNameAr) AS FullNameAr"), DB::raw("CONCAT(kic_customerinfo.FirstNameEn,' ',kic_customerinfo.MiddleNameEn, ' ', kic_customerinfo.FamilyNameEn) AS full_name"), 'kic_customerinfo.CivilId', 'kic_customerinfo.ekycstatus as kicstatus', 'kic_customerinfo.created_at', DB::raw('(case when kic_customerinfo_cif.customerId is not null then 1 else 0 end) as cif'), DB::raw('(case when kic_customerinfo_signature.customerId is not null then 1 else 0 end) as sign'), DB::raw('(case when kic_customerinfo_document.customerId is not null then 1 else 0 end) as document'))->where('ismenual', $type)
                ->distinct()
                //->whereNull('deleted_at')
                ->get();
            if ($ekyc) {
                $counts = DB::select(DB::raw("SELECT SUM(if(ekycstatus = '0', 1, 0)) AS pending, SUM(if(ekycstatus = '1', 1, 0)) AS approved, SUM(if(ekycstatus = '2', 1, 0)) AS rejected, SUM(if(ekycstatus = '3', 1, 0)) AS inform, SUM(if(ekycstatus = '4', 1, 0)) AS uncompleted, SUM(if(ekycstatus = '5', 1, 0)) AS pendingsign  FROM `kic_customerinfo` WHERE ismenual='" . $type . "' "));
                return response()->json([
                    "code" => 200,
                    "ekyc" => $ekyc,
                    "counts" => $counts
                ]);
            }
        }
        // DB::raw("CONCAT(kic_titles.TitleEN, ' ', kic_customerinfo.FirstNameEn,' ',kic_customerinfo.MiddleNameEn, ' ', kic_customerinfo.FamilyNameEn) AS full_name")


        return response()->json([
            "code" => 404,
            "msg" => "not found"
        ]);



        return response()->json(compact('ekyc', 'counts'));
    }

    public function ekycSend(Request $request, $id)
    {
        echo $id;
        die;
        # code...
    }

    public function split_name($string)
    {

        $arr = explode(' ', $string);
        $num = count($arr);
        $first_name = $middle_name = $last_name = null;

        if ($num > 3) {

            $a = explode(" ", $string);
            $first_name = array_shift($a);
            $last_name =  array_pop($a);
            $middle_name = (implode(" ", $a));
            list($first_name, $middle_name, $last_name) = [$first_name, $middle_name, $last_name];
        } else
        if ($num == 2) {
            list($first_name, $last_name) = $arr;
        } else {
            list($first_name, $middle_name, $last_name) = $arr;
        }

        return (empty($first_name)) ? false : compact(
            'first_name',
            'middle_name',
            'last_name'
        );
    }


    public function loadformgenerate(Request $request, $id, $ekyc, $clientid, $lang, $pacitoken, $from, $isShowApprove)
    {

        // echo base64_decode($clientid);
        // die;
        $from = isset($from) && $from != 'null' ? base64_decode($from) : 'null';

        $id = base64_decode($id);
        $customerId = isset($ekyc) && $ekyc != 'null' ? base64_decode($ekyc) : null;
        $clientId = isset($clientid) && $clientid != 'null' ? base64_decode($clientid) : 'null';

        $response = null;
        $isKicCif = false;
        $isKicDoc = false;
        $isKicDocApprove = false;
        if ($from != 'null') {
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
        }

        if ($isShowApprove == 'true') {
            $soap = new SoapClient(
                'http://kicpaci/ClientService.svc?wsdl',
                array(
                    'soap_version' => 'SOAP_1_2',
                    "trace" => 1,
                    "exceptions" => 1,
                    'location' => 'http://kicpaci/ClientService.svc',
                )
            );
            $from = 'true';
        }



        $models = [];

        $ekycData = [];
        $PriorityType = DB::table('kic_form')
            ->join('kic_form_section', 'kic_form_section.form_id', '=', 'kic_form.id')
            ->join('kic_form_control', 'kic_form_control.section_id', '=', 'kic_form_section.id')
            ->join('kic_form_control_type', 'kic_form_control_type.id', '=', 'kic_form_control.type_id')
            ->select('kic_form_section.icon', 'kic_form_control.validator', 'kic_form_control.defaultVal', 'kic_form_control.store_table', 'kic_form_control.ref_db_name', 'kic_form_control.is_label', 'kic_form_control.child_table', 'kic_form_section.id as section_id', 'kic_form.label as title', 'kic_form.is_linked', 'kic_form.next_form_id', 'kic_form.next_form_arg', 'kic_form.type', 'kic_form_control.styleClasses', 'kic_form.db_table', 'kic_form_section.label as section_title', 'kic_form_section.show_label', 'kic_form_section.sort_no', 'kic_form_control.label as input_label', 'kic_form_control.sort_no as input_sort', 'kic_form_control.type_id', 'kic_form_control.input_type', 'kic_form_control_type.name as field_type', 'kic_form_control.is_mandatory', 'kic_form_control.db_column', 'kic_form_control.expression', 'kic_form_control.is_visible', 'kic_form_control.mask')
            ->where('kic_form.id', $id)
            ->where('kic_form_control.is_visible', 1)
            ->orderByRaw('kic_form_section.sort_no, input_sort ASC')
            ->get();

        $ekycstatus = null;
        $isNameDiffEn = $isNameDiffAr = false;
        if (count($PriorityType) > 0) {
            $categories = array();
            foreach ($PriorityType as $data) {
                $categories[$data->sort_no][] = $data;
            }

            $db_name = $data->db_table;
            $All = array();
            $type = '';

            $ekycNotes = $ekycReasons = [];
            if ($customerId) {

                if ($clientId == 'null') {
                    $ekycData = DB::table('kic_customerinfo')
                        ->where('kic_customerinfo.CivilId', '=', $customerId)
                        //->where('kic_customerinfo.CustomerId', '=', $clientId)
                        //->whereNull('responseAfterPost')
                        ->get();
                } else {
                    $ekycData = DB::table('kic_customerinfo')
                        ->where('kic_customerinfo.CivilId', '=', $customerId)
                        ->where('kic_customerinfo.CustomerId', '=', $clientId)
                        //->whereNull('responseAfterPost')
                        ->get();
                }



                if (count($ekycData) > 0) {

                    $kiccif = DB::table('kic_customerinfo_cif')->where('customerId', $ekycData[0]->CustomerId)->get();
                    if (count($kiccif) > 0) {
                        $isKicCif = true;
                    }

                    $kicDoc = KicCustomerDocument::where('customerId', $ekycData[0]->CustomerId)->get();
                    if (count($kicDoc) > 0) {
                        $statsApprove = 0;
                        foreach ($kicDoc as $kicKey => $kicVal) {
                            $statsApprove = ($kicVal->status == 1) ? $statsApprove + 1 : $statsApprove;
                        }
                        $isKicDocApprove =  ($statsApprove == count($kicDoc)) ? true : false;
                        $isKicDoc = true;
                    }

                    $fnAr = isset($ekycData[0]->FullNameAr) ? preg_replace("/[\s]/", "", $ekycData[0]->FullNameAr) : $ekycData[0]->FullNameAr;
                    $enterFnAr = preg_replace("/[\s]/", "", $ekycData[0]->FirstNameAr . ' ' . $ekycData[0]->MiddleNameAr . ' ' . $ekycData[0]->FamilyNameAr);

                    $fnEn = isset($ekycData[0]->FullNameEn) ? preg_replace("/[\s]/", "", $ekycData[0]->FullNameEn) : $ekycData[0]->FullNameEn;
                    $enterFnEn = preg_replace("/[\s]/", "", $ekycData[0]->FirstNameEn . ' ' . $ekycData[0]->MiddleNameEn . ' ' . $ekycData[0]->FamilyNameEn);

                    $isNameDiffAr = strcmp($fnAr, $enterFnAr) === 0 ? true : false;
                    $isNameDiffEn = strcmp($fnEn, $enterFnEn) === 0 ? true : false;

                    $ekycNotes = DB::table('kic_ekyc_customer_notes')
                        ->where('CustomerId', '=', (string)$ekycData[0]->CustomerId)
                        ->get();

                    $ekycReasons = DB::table('kic_ekyc_customer_reject_reason')
                        ->where('CustomerId', '=', (string)$ekycData[0]->CustomerId)
                        ->get();

                    if (!in_array($ekycData[0]->ekycstatus, ['1', '2'])) {
                        $models['sessionId'] = (string)$ekycData[0]->CustomerId;
                    }
                }



                //$kicModel = [];
                if ($from == 'true') {
                    $customerDetails = $soap->GetCustomerDetail(['CivilId' => $customerId]);
                    $dataArray = (array)json_decode($customerDetails->GetCustomerDetailResult)->CustomerInfo;
                    foreach ($dataArray as $key => $val) {
                        foreach ($val as $k => $v) {

                            if (in_array($k, ['UpdatedOn'])) {
                                $models['date'] =  $v;
                            }

                            if (in_array($k, ['PlaceOfBirthId', 'NationalityId', 'PlaceOfIssueId', 'PIPNationalityId', 'OtherNationalityId', 'Contact_Country', 'Other_Country', 'Actual_Ben_Country', 'CRS_Curr_CountyrId', 'CRS_Mail_CountryId', 'CRS_BirthPlace', 'CRS_BirthCountryId'])) {
                                $kicCountry = DB::table('kic_country')
                                    ->where('kic_country.CountryId', '=', $v)
                                    ->get();

                                if (count($kicCountry) > 0) {

                                    $v = [
                                        'id' => $kicCountry[0]->CountryId,
                                        'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                    ];
                                } else {
                                    $v = [
                                        'id' => 149,
                                        'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                    ];
                                }
                            }

                            if (in_array($k, ['AccountType'])) {
                                if ($v) {
                                    $kicAccountType = DB::select(DB::raw("select * from kic_account_types s where  s.id in ($v)"));
                                    if (count($kicAccountType) > 0) {

                                        $ji = 0;
                                        $v = [];
                                        foreach ($kicAccountType as $typeVal) {
                                            $v[$ji] = [
                                                'id' => $typeVal->id,
                                                'name' => ($lang == 'en') ? $typeVal->name : $typeVal->name_ar
                                            ];
                                            $ji++;
                                        }
                                    } else {
                                        $v = null;
                                    }
                                } else {
                                    $v = null;
                                }
                            }

                            if (in_array($k, ['Income_Source'])) {
                                $models['income_source_custom'] = $v;
                                if ($v) {
                                    $kicIncomeSource = DB::select(DB::raw("select * from kic_income_source s where  s.id in ($v)"));
                                    if (count($kicIncomeSource) > 0) {

                                        $ji = 0;
                                        $v = [];
                                        foreach ($kicIncomeSource as $typeVal) {
                                            $v[$ji] = [
                                                'id' => $typeVal->id,
                                                'name' => ($lang == 'en') ? $typeVal->name : $typeVal->name_ar
                                            ];
                                            $ji++;
                                        }
                                    } else {
                                        $v = null;
                                    }
                                } else {
                                    $v = null;
                                }
                            }

                            $models[$k] = $v;

                            if (in_array($k, ['HavingKICAccount', 'Bank_Other_Account', 'BeneficiaryTypeId', 'AddInfo_Trade_KSE', 'AddInfo_SMSService', 'FATCA_POA', 'PIP_Relative', 'PIP', 'AddInfo_Trade_Stock', 'AddInfo_CollectIntrest', 'Other_Int_address'])) {
                                $models[$k] =  ($v == true || $v == 1) ? '1' : '0';
                            }

                            if (in_array($k, ['KICAccountRelationship'])) {
                                unset($models['KICAccountRelationship']);
                                $models['KICAccountRelationshipType'] = $v;
                            }

                            if (in_array($k, ['ekycstatus'])) {
                                //$models['ekycstatus'] = '0';
                                $ekycstatus = $v;
                            }

                            if (in_array($k, ['NoofChildren'])) {
                                $models['NoofChildren'] = (int)$v;
                            }

                            /* comment bcoz not loading updated details
                            
                            if (in_array($k, ['FullNameAr', 'GenderId']) && $response != null) {
                                if (strlen(($response->GetResponseDetailResult)) > 1) {
                                    // var_dump($response->GetResponseDetailResult);
                                    // die;
                                    $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                    $models['FullNameAr'] = $paciData->FullNameAr;
                                    $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';
                                    // $nameSplit = $this->split_name($paciData->FullNameAr);

                                    // $models['FirstNameAr'] = $nameSplit['first_name'];
                                    // $models['MiddleNameAr'] = $nameSplit['middle_name'];
                                    // $models['FamilyNameAr'] = $nameSplit['last_name'];
                                    //$models['CivilId'] = $paciData->CivilID;

                                    //$models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                                    //$models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));
                                }
                            }

                            // if (in_array($k, ['PlaceOfBirthId', 'NationalityId', 'PlaceOfIssueId'])) {
                            //     if (strlen(($response->GetResponseDetailResult)) > 1) {
                            //         $kicCountry = DB::table('kic_country')
                            //             ->where('kic_country.COUNTRY_CODE', '=', $paciData->NationalityEn)
                            //             ->get();

                            //         if (count($kicCountry) > 0) {

                            //             $v = [
                            //                 'id' => $kicCountry[0]->CountryId,
                            //                 'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                            //             ];
                            //         } else {
                            //             $v = [
                            //                 'id' => 149,
                            //                 'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                            //             ];
                            //         }
                            //         $models[$k] = $v;
                            //     }
                            // }

                            if (in_array($k, ['FullNameEn']) && $response != null) {
                                if (strlen(($response->GetResponseDetailResult)) > 1) {
                                    $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                    $models['FullNameEn'] = $paciData->FullNameEn;
                                    $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';
                                    $models['client_name'] = $lang == 'en' ? $paciData->FullNameEn : $paciData->FullNameAr;
                                    // $nameSplit = $this->split_name($paciData->FullNameAr);

                                    // $models['FirstNameAr'] = $nameSplit['first_name'];
                                    // $models['MiddleNameAr'] = $nameSplit['middle_name'];
                                    // $models['FamilyNameAr'] = $nameSplit['last_name'];
                                    $models['CivilId'] = $paciData->CivilID;
                                    $models['PassportNumber'] = $paciData->PassportNumber;

                                    $models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                                    $models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));
                                }
                            } */

                            if (in_array($k, ['FullNameAr', 'FullNameEn', 'GenderId', 'PassportNumber', 'CivilId', 'DOB', 'CivilIdExpiry']) && $response != null) {
                                if (strlen(($response->GetResponseDetailResult)) > 1) {
                                    // var_dump($response->GetResponseDetailResult);
                                    // die;
                                    $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                    $models['FullNameAr'] = $paciData->FullNameAr;
                                    $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';

                                    $models['FullNameEn'] = $paciData->FullNameEn;
                                    $models['client_name'] = $lang == 'en' ? $paciData->FullNameEn : $paciData->FullNameAr;


                                    $models['CivilId'] = $paciData->CivilID;
                                    $models['PassportNumber'] = $paciData->PassportNumber;

                                    $models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                                    $models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));
                                }
                            } else if (count($ekycData) > 0) {
                                $models['client_name'] = $lang == 'en' ? $ekycData[0]->FullNameEn : $ekycData[0]->FullNameAr;
                            }

                            if (in_array($k, ['Ver', 'T24Approved', 'T24ApprovedOn', 'T24Result'])) {
                                unset($models['Ver']);
                                unset($models['T24Approved']);
                                unset($models['T24ApprovedOn']);
                                unset($models['T24Result']);
                            }

                            if (in_array($k, ['FATCA_US', 'FATCA_Mailing_USA', 'FATCA_TAX_Residency'])) {
                                $models[$k] = strval($v);
                            }

                            if (in_array($k, ['Contact_Area', 'Other_Area', 'Actual_Ben_Area'])) {
                                $kicCountryArea = DB::table('kic_governorate')
                                    ->where('kic_governorate.governorateId', '=', $v)
                                    ->get();

                                if (count($kicCountryArea) > 0) {

                                    $v = [
                                        'id' => $kicCountryArea[0]->governorateId,
                                        'name' => ($lang == 'en') ? $kicCountryArea[0]->governorate_en : $kicCountryArea[0]->governorate_ar
                                    ];
                                } else {
                                    $v = null;
                                }
                                $models[$k] = $v;
                            }

                            if (in_array($k, ['Contact_City', 'Other_City', 'Actual_Ben_City'])) {
                                $kicCountryCity = DB::table('kic_city')
                                    ->where('kic_city.cityId', '=', $v)
                                    ->get();

                                if (count($kicCountryCity) > 0) {

                                    $v = [
                                        'id' => $kicCountryCity[0]->cityId,
                                        'name' => ($lang == 'en') ? $kicCountryCity[0]->city_en : $kicCountryCity[0]->city_ar
                                    ];
                                } else {
                                    $v = null;
                                }
                                $models[$k] = $v;
                            }

                            //$models[$k] = $v;
                        }
                    }
                    $CustomerBeneficiary = json_decode($customerDetails->GetCustomerDetailResult)->CustomerBeneficiary;

                    if (count($CustomerBeneficiary) == 0) {
                        $data = DB::table('kic_beneficary_form_control')
                            ->select('ref_db_name')
                            ->where('is_visible', '=', '1')
                            ->where('is_label', '=', '0')
                            ->get();
                        $new = [];
                        foreach ($data as $dk => $dv) {
                            $new[$dv->ref_db_name] = null;
                        }

                        $models['kic_customerbeneficiary'][] = $new;
                    } else {
                        foreach ($CustomerBeneficiary as $dk => $dv) {
                            $new = [];
                            foreach ($dv as $ddk => $ddv) {
                                //var_dump($ddk) . '===';
                                if (in_array($ddk, ['BeneficiaryCivilId', 'BeneficiaryName', 'NationalityId', 'Address', 'RelationShip'])) {

                                    if (in_array($ddk, ['NationalityId'])) {
                                        $kicCountry = DB::table('kic_country')
                                            ->where('kic_country.CountryId', '=', $ddv)
                                            ->get();

                                        if (count($kicCountry) > 0) {

                                            $ddv = [
                                                'id' => $kicCountry[0]->CountryId,
                                                'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                            ];
                                        } else {
                                            $ddv = [
                                                'id' => 149,
                                                'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                            ];
                                        }
                                        //$models['kic_customerrelatives'][$ddk] = $ddv;
                                    }

                                    $new[$ddk] = $ddv;
                                }
                            }
                            $models['kic_customerbeneficiary'][] = $new;
                        }
                    }

                    $Customer_Relatives = json_decode($customerDetails->GetCustomerDetailResult)->Customer_Relatives;
                    if (count($Customer_Relatives) == 0) {
                        $data = DB::table('kic_political_form_control')
                            ->select('ref_db_name')
                            ->where('is_visible', '=', '1')
                            ->where('is_label', '=', '0')
                            ->get();
                        $new = [];
                        foreach ($data as $dk => $dv) {
                            $new[$dv->ref_db_name] = null;
                        }
                        $models['kic_customerrelatives'][] = $new;
                    } else {
                        foreach ($Customer_Relatives as $dk => $dv) {
                            $new = [];
                            foreach ($dv as $ddk => $ddv) {
                                if (in_array($ddk, ['PIPName', 'PIPRelationship', 'PIPPostion', 'PIPNationalityId'])) {

                                    if (in_array($ddk, ['PIPNationalityId'])) {
                                        $kicCountry = DB::table('kic_country')
                                            ->where('kic_country.CountryId', '=', $ddv)
                                            ->get();

                                        if (count($kicCountry) > 0) {

                                            $ddv = [
                                                'id' => $kicCountry[0]->CountryId,
                                                'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                            ];
                                        } else {
                                            $ddv = [
                                                'id' => 149,
                                                'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                            ];
                                        }
                                        //$models['kic_customerrelatives'][$ddk] = $ddv;
                                    }
                                    // else {
                                    //     //var_dump($ddk) . '===';
                                    //     $models['kic_customerrelatives'][$ddk] = $ddv;
                                    // }
                                    $new[$ddk] = $ddv;
                                }
                            }
                            $models['kic_customerrelatives'][] = $new;
                        }
                    }
                    $CustomerTIN = json_decode($customerDetails->GetCustomerDetailResult)->CustomerTIN;
                    if (count($CustomerTIN) == 0) {
                        $data = DB::table('kic_customer_tin_form_control')
                            ->select('ref_db_name')
                            ->where('is_visible', '=', '1')
                            ->where('is_label', '=', '0')
                            ->get();

                        $new = [];
                        foreach ($data as $dk => $dv) {
                            $new[$dv->ref_db_name] = null;
                        }
                        $models['kic_customer_tin'][] = $new;
                    } else {
                        foreach ($CustomerTIN as $dk => $dv) {
                            $new = [];
                            foreach ($dv as $ddk => $ddv) {
                                //var_dump($ddk) . '===';
                                if (in_array($ddk, ['Tax_No', 'Tax_Reason', 'TIN_Reasons', 'Tax_Reason_Detail'])) {
                                    $new[$ddk] = $ddv;
                                }
                            }
                            $models['kic_customer_tin'][] = $new;
                        }
                    }

                    $CustomerShares = json_decode($customerDetails->GetCustomerDetailResult)->CustomerShares;
                    if (count($CustomerShares) == 0) {
                        $data = DB::table('kic_customer_share_form_control')
                            ->select('ref_db_name')
                            ->where('is_visible', '=', '1')
                            ->where('is_label', '=', '0')
                            ->get();

                        $new = [];
                        foreach ($data as $dk => $dv) {
                            $new[$dv->ref_db_name] = null;
                        }
                        $models['kic_customershares'][] = $new;
                    } else {
                        foreach ($CustomerShares as $dk => $dv) {
                            $new = [];
                            foreach ($dv as $ddk => $ddv) {
                                //var_dump($ddk) . '===';
                                if (in_array($ddk, [`CompanyName`, `FinancialMarket`, `Postion`, `NumberofShares`])) {
                                    $new[$ddk] = $ddv;
                                }
                            }
                            $models['kic_customershares'][] = $new;
                        }
                    }


                    //var_dump($kicModel);
                    //die;

                } else {
                    if (count($ekycData) > 0) {
                        foreach ($ekycData as $key => $val) {
                            foreach ($val as $k => $v) {


                                if (in_array($k, ['created_at'])) {
                                    $models['date'] =  $v;
                                }

                                if (in_array($k, ['PIPNationalityId', 'PlaceOfBirthId', 'NationalityId', 'OtherNationalityId', 'PlaceOfIssueId', 'Contact_Country', 'Other_Country', 'Actual_Ben_Country', 'CRS_Curr_CountyrId', 'CRS_Mail_CountryId', 'CRS_BirthPlace', 'CRS_BirthCountryId'])) {
                                    $kicCountry = DB::table('kic_country')
                                        ->where('kic_country.CountryId', '=', $v)
                                        ->get();

                                    if (count($kicCountry) > 0) {

                                        $v = [
                                            'id' => $kicCountry[0]->CountryId,
                                            'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                        ];
                                    } else {
                                        $v = [
                                            'id' => 149,
                                            'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                        ];
                                    }
                                }

                                if (in_array($k, ['AccountType'])) {
                                    if ($v) {
                                        $kicAccountType = DB::select(DB::raw("select * from kic_account_types s where  s.id in ($v)"));
                                        if (count($kicAccountType) > 0) {

                                            $ji = 0;
                                            $v = [];
                                            foreach ($kicAccountType as $typeVal) {
                                                $v[$ji] = [
                                                    'id' => $typeVal->id,
                                                    'name' => ($lang == 'en') ? $typeVal->name : $typeVal->name_ar
                                                ];
                                                $ji++;
                                            }
                                        } else {
                                            $v = null;
                                        }
                                    } else {
                                        $v = null;
                                    }
                                }

                                if (in_array($k, ['Income_Source'])) {
                                    $models['income_source_custom'] = $v;
                                    if ($v) {
                                        $kicIncomeSource = DB::select(DB::raw("select * from kic_income_source s where  s.id in ($v)"));
                                        if (count($kicIncomeSource) > 0) {

                                            $ji = 0;
                                            $v = [];
                                            foreach ($kicIncomeSource as $typeVal) {
                                                $v[$ji] = [
                                                    'id' => $typeVal->id,
                                                    'name' => ($lang == 'en') ? $typeVal->name : $typeVal->name_ar
                                                ];
                                                $ji++;
                                            }
                                        } else {
                                            $v = null;
                                        }
                                    } else {
                                        $v = null;
                                    }
                                }

                                $models[$k] = $v;

                                if (in_array($k, ['ekycstatus'])) {
                                    //$models['ekycstatus'] = '0';
                                    $ekycstatus = $v;
                                }

                                if (in_array($k, ['NoofChildren'])) {
                                    $models['NoofChildren'] = (int)$v;
                                }

                                /* Comment bcoz not loading new details

                                // if (in_array($k, ['FullNameAr'])) {
                                //     var_dump($response->GetResponseDetailResult);
                                //     die;
                                //     if (strlen(($response->GetResponseDetailResult)) > 1) {
                                //         $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                //         $models['FullNameAr'] = $paciData->FullNameAr;
                                //     }
                                // }

                                // if (in_array($k, ['FullNameEn'])) {
                                //     if (strlen(($response->GetResponseDetailResult)) > 1) {
                                //         $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                //         $models['FullNameEn'] = $paciData->FullNameEn;
                                //     }
                                // }

                                if (in_array($k, ['FullNameAr', 'GenderId']) && $response != null) {
                                    if (strlen(($response->GetResponseDetailResult)) > 1) {
                                        // var_dump($response->GetResponseDetailResult);
                                        // die;
                                        $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                        $models['FullNameAr'] = $paciData->FullNameAr;
                                        $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';
                                        //$models['client_name'] = $lang == 'en' ? $paciData->FullNameEn : $paciData->FullNameAr;
                                        // $nameSplit = $this->split_name($paciData->FullNameAr);

                                        // $models['FirstNameAr'] = $nameSplit['first_name'];
                                        // $models['MiddleNameAr'] = $nameSplit['middle_name'];
                                        // $models['FamilyNameAr'] = $nameSplit['last_name'];
                                        //$models['CivilId'] = $paciData->CivilID;

                                        //$models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                                        //$models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));
                                    }
                                }

                                // if (in_array($k, ['PlaceOfBirthId', 'NationalityId', 'PlaceOfIssueId'])) {
                                //     if (strlen(($response->GetResponseDetailResult)) > 1) {
                                //         $kicCountry = DB::table('kic_country')
                                //             ->where('kic_country.COUNTRY_CODE', '=', $paciData->NationalityEn)
                                //             ->get();

                                //         if (count($kicCountry) > 0) {

                                //             $v = [
                                //                 'id' => $kicCountry[0]->CountryId,
                                //                 'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                //             ];
                                //         } else {
                                //             $v = [
                                //                 'id' => 149,
                                //                 'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                //             ];
                                //         }
                                //         $models[$k] = $v;
                                //     }
                                // }

                                if (in_array($k, ['FullNameEn']) && $response != null) {
                                    if (strlen(($response->GetResponseDetailResult)) > 1) {
                                        $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                        $models['FullNameEn'] = $paciData->FullNameEn;
                                        $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';
                                        $models['client_name'] = $lang == 'en' ? $paciData->FullNameEn : $paciData->FullNameAr;
                                        // $nameSplit = $this->split_name($paciData->FullNameAr);

                                        // $models['FirstNameAr'] = $nameSplit['first_name'];
                                        // $models['MiddleNameAr'] = $nameSplit['middle_name'];
                                        // $models['FamilyNameAr'] = $nameSplit['last_name'];
                                        $models['CivilId'] = $paciData->CivilID;
                                        $models['PassportNumber'] = $paciData->PassportNumber;

                                        $models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                                        $models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));
                                    }
                                } else {
                                    $models['client_name'] = $lang == 'en' ? $ekycData[0]->FullNameEn : $ekycData[0]->FullNameAr;
                                }*/

                                if (in_array($k, ['FullNameAr', 'FullNameEn', 'GenderId', 'PassportNumber', 'CivilId', 'DOB', 'CivilIdExpiry']) && $response != null) {
                                    if (strlen(($response->GetResponseDetailResult)) > 1) {
                                        // var_dump($response->GetResponseDetailResult);
                                        // die;
                                        $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                                        $models['FullNameAr'] = $paciData->FullNameAr;
                                        $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';

                                        $models['FullNameEn'] = $paciData->FullNameEn;
                                        $models['client_name'] = $lang == 'en' ? $paciData->FullNameEn : $paciData->FullNameAr;


                                        $models['CivilId'] = $paciData->CivilID;
                                        $models['PassportNumber'] = $paciData->PassportNumber;

                                        $models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                                        $models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));
                                    }
                                } else if (count($ekycData) > 0) {
                                    $models['client_name'] = $lang == 'en' ? $ekycData[0]->FullNameEn : $ekycData[0]->FullNameAr;
                                }

                                if (in_array($k, ['FATCA_US', 'FATCA_Mailing_USA', 'FATCA_TAX_Residency'])) {
                                    $models[$k] = strval($v);
                                }

                                if ($k == 'BeneficiaryTypeId') {

                                    $models[$k] = strval($v);
                                    $ekycDataBeni = DB::table('kic_customerbeneficiary')
                                        ->select('BeneficiaryCivilId', 'BeneficiaryName', 'NationalityId', 'RelationShip', 'Address')
                                        ->where('kic_customerbeneficiary.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                                        ->get();

                                    foreach ($ekycDataBeni as $benkey => $benval) {

                                        $new = [];
                                        foreach ($benval as $benk => $benv) {
                                            if (in_array($benk, ['NationalityId'])) {
                                                $kicCountry = DB::table('kic_country')
                                                    ->where('kic_country.CountryId', '=', $benv)
                                                    ->get();

                                                if (count($kicCountry) > 0) {

                                                    $benv = [
                                                        'id' => $kicCountry[0]->CountryId,
                                                        'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                                    ];
                                                } else {
                                                    $benv = [
                                                        'id' => 149,
                                                        'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                                    ];
                                                }
                                            }
                                            if ($v == '1') {
                                                $new[$benk] = $benv;
                                            } else {
                                                $new[$benk] = null;
                                            }
                                        }

                                        $models['kic_customerbeneficiary'][] = $new;
                                    }
                                }

                                if ($k == 'PIP_Relative') {

                                    $models[$k] = strval($v);
                                    $ekycDataBeni = DB::table('kic_customerrelatives')
                                        ->select('PIPName', 'PIPRelationship', 'PIPPostion', 'PIPNationalityId')
                                        ->where('kic_customerrelatives.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                                        ->get();

                                    foreach ($ekycDataBeni as $benkey => $benval) {

                                        $new = [];
                                        foreach ($benval as $benk => $benv) {

                                            if (in_array($benk, ['PIPNationalityId'])) {
                                                $kicCountry = DB::table('kic_country')
                                                    ->where('kic_country.CountryId', '=', $benv)
                                                    ->get();

                                                if (count($kicCountry) > 0) {

                                                    $benv = [
                                                        'id' => $kicCountry[0]->CountryId,
                                                        'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                                    ];
                                                } else {
                                                    $benv = [
                                                        'id' => 149,
                                                        'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                                    ];
                                                }
                                            }

                                            if ($v == '1') {
                                                $new[$benk] = $benv;
                                            } else {
                                                $new[$benk] = null;
                                            }
                                        }
                                        $models['kic_customerrelatives'][] = $new;
                                    }
                                }

                                if (in_array($k, ['Contact_Area', 'Other_Area', 'Actual_Ben_Area'])) {
                                    $kicCountryArea = DB::table('kic_governorate')
                                        ->where('kic_governorate.governorateId', '=', $v)
                                        ->get();

                                    if (count($kicCountryArea) > 0) {

                                        $v = [
                                            'id' => $kicCountryArea[0]->governorateId,
                                            'name' => ($lang == 'en') ? $kicCountryArea[0]->governorate_en : $kicCountryArea[0]->governorate_ar
                                        ];
                                    } else {
                                        $v = null;
                                    }
                                    $models[$k] = $v;
                                }

                                if (in_array($k, ['Contact_City', 'Other_City', 'Actual_Ben_City'])) {
                                    $kicCountryCity = DB::table('kic_city')
                                        ->where('kic_city.cityId', '=', $v)
                                        ->get();

                                    if (count($kicCountryCity) > 0) {

                                        $v = [
                                            'id' => $kicCountryCity[0]->cityId,
                                            'name' => ($lang == 'en') ? $kicCountryCity[0]->city_en : $kicCountryCity[0]->city_ar
                                        ];
                                    } else {
                                        $v = null;
                                    }
                                    $models[$k] = $v;
                                }
                            }

                            $ekycDataTin = DB::table('kic_customer_tin')
                                ->select('Tax_No', 'Tax_Reason', 'TIN_Reasons', 'Tax_Reason_Detail')
                                ->where('kic_customer_tin.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                                ->get();

                            foreach ($ekycDataTin as $benkey => $benval) {

                                $new = [];
                                foreach ($benval as $benk => $benv) {
                                    $new[$benk] = $benv;
                                }
                                $models['kic_customer_tin'][] = $new;
                            }

                            $ekycDataShare = DB::table('kic_customershares')
                                ->select('CompanyName', 'FinancialMarket', 'Postion', 'NumberofShares')
                                ->where('kic_customershares.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                                ->get();

                            foreach ($ekycDataShare as $benkey => $benval) {

                                $new = [];
                                foreach ($benval as $benk => $benv) {
                                    $new[$benk] = $benv;
                                }
                                $models['kic_customershares'][] = $new;
                            }
                        }
                    } else {
                        if (strlen(($response->GetResponseDetailResult)) > 1 && count($ekycData) == 0  && $response != null) {
                            $paciData = (json_decode($response->GetResponseDetailResult)->MIDAuthSignResponse->PersonalData);
                            $models['FullNameAr'] = $paciData->FullNameAr;
                            $models['GenderId'] = $paciData->Gender == 'M' ? '1' : '2';
                            $nameSplitAr = $this->split_name($paciData->FullNameAr);

                            $models['FirstNameAr'] = $nameSplitAr['first_name'];
                            $models['MiddleNameAr'] = $nameSplitAr['middle_name'];
                            $models['FamilyNameAr'] = $nameSplitAr['last_name'];

                            $models['FullNameEn'] = $paciData->FullNameEn;
                            $nameSplitEn = $this->split_name($paciData->FullNameEn);

                            $models['FirstNameEn'] = $nameSplitEn['first_name'];
                            $models['MiddleNameEn'] = $nameSplitEn['middle_name'];
                            $models['FamilyNameEn'] = $nameSplitEn['last_name'];

                            $models['CivilId'] = $paciData->CivilID;
                            $models['PassportNumber'] = $paciData->PassportNumber;


                            $models['DOB'] = date('Y-m-d', strtotime($paciData->BirthDate));
                            $models['CivilIdExpiry'] = date('Y-m-d', strtotime($paciData->CardExpiryDate));

                            $kicCountry = DB::table('kic_country')
                                ->where('kic_country.COUNTRY_CODE', '=', $paciData->NationalityEn)
                                ->get();

                            if (count($kicCountry) > 0) {

                                $v = [
                                    'id' => $kicCountry[0]->CountryId,
                                    'name' => ($lang == 'en') ? $kicCountry[0]->COUNTRY_NAME_EN : $kicCountry[0]->COUNTRY_NAME_AR
                                ];
                            } else {
                                $v = [
                                    'id' => 149,
                                    'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                ];
                            }
                            $models['PlaceOfBirthId'] = $v;
                            $models['NationalityId'] = $v;
                            $models['PlaceOfIssueId'] = $v;
                            $models['client_name'] = $lang == 'en' ? $paciData->FullNameEn : $paciData->FullNameAr;
                        }
                    }
                }

                //if (in_array($k, ['FullNameEn'])) {

                //}

                if (count($ekycData) > 0) {
                    if (in_array($ekycData[0]->ekycstatus, ['1', '2'])) {
                        unset($models['CustomerId']);
                    }
                }
            }

            foreach ($categories as $key => $valData) {
                $vals = [];
                $i = 0;
                $j = 0;
                $checkDataArray = [];
                foreach ($valData as $item) {
                    $demo = $item->defaultVal;
                    if ($item->is_label != 1) {

                        if (in_array($item->db_column, ['political_nationality', 'crs_birth_place', 'place_of_birth', 'nationality', 'place_of_issue', 'other_nationality', 'country', 'int_other_country', 'actual_beneficiary_country', 'crs_country', 'mailing_country', 'crs_birth_country'])) {
                            $demo = [
                                'id' => 149,
                                'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                            ];
                        }
                        if (!isset($models[$item->ref_db_name])) {
                            $models[$item->ref_db_name] = $demo;
                        }
                    }
                    if (isset($models['Contact_Country'])) {

                        if ($models['Contact_Country']['id'] == 149 && ($item->db_column === 'cityother' || $item->db_column === 'areaother')) {
                            //echo 'inn'.$models['Contact_Country']['id'].'=='.$item->db_column;
                            $vals['groups'][$j]['fields'][$i]['disabled'] = true;
                        }
                    }
                    if (isset($models['Actual_Ben_Country'])) {
                        if ($models['Actual_Ben_Country']['id'] == 149 && ($item->db_column == 'actual_beneficiary_area_other' || $item->db_column == 'actual_beneficiary_city_other')) {
                            $vals['groups'][$j]['fields'][$i]['disabled'] = true;
                        }
                    }

                    if ($item->db_column == 'relative_relationship_other') {
                        $vals['groups'][$j]['fields'][$i]['disabled'] = true;
                    }

                    if ($item->db_column == 'date') {
                        $demo =  date('Y-m-d');
                    }

                    $type = $item->type;

                    $vals['groups'][$j]['legends'] = $item->section_title;
                    $vals['groups'][$j]['icon'] = $item->icon;
                    $vals['groups'][$j]['fields'][$i]['type'] = $item->field_type;
                    $vals['groups'][$j]['fields'][$i]['inputType'] = $item->input_type;

                    if ($item->field_type == 'masked')
                        $vals['groups'][$j]['fields'][$i]['mask'] = "(99) 999-9999";

                    $vals['groups'][$j]['fields'][$i]['label'] = $item->input_label;
                    $vals['groups'][$j]['fields'][$i]['model'] = $item->ref_db_name;
                    $vals['groups'][$j]['fields'][$i]['placeholder'] = $item->input_label;
                    $vals['groups'][$j]['fields'][$i]['selectOptions']['noneSelectedText'] = "please_select";
                    if (isset($item->expression)) {
                        $exp = explode(":", $item->expression);
                        if ($exp[0] == 'txt') {
                            $val = explode("=", $exp[1]);
                            $option = [];
                            foreach (explode(",", $val[1]) as $ke => $val) {
                                if (in_array($item->db_column, ['trade_for_own_or_behalf', 'holding_us_passport_british_usa', 'have_mailing_address_telephone_usa', 'have_residency_usa_tax_purpose'])) {
                                    $opVlaus = explode("->", $val);
                                    $option[$ke]['value'] = trim($opVlaus[0]);
                                    $option[$ke]['name'] = trim($opVlaus[1]);
                                } else {

                                    $opVlaus = explode("->", $val);
                                    if (count($opVlaus) > 1) {
                                        $option[$ke]['id'] = trim($ke);
                                        $option[$ke]['name'] = trim($opVlaus[1]);
                                    } else {
                                        $option[$ke]['id'] = $ke;
                                        $option[$ke]['name'] = $val;
                                    }
                                }
                            }
                            $vals['groups'][$j]['fields'][$i]['values'] = $option; //explode(",", $val[1]);
                        } elseif ($exp[0] == 'sql') {
                            $val = explode("==", $exp[1]);

                            if ($item->db_column == 'address_city_id') {
                                $vals['groups'][$j]['fields'][$i]['values'] = [];
                            } else if ($item->db_column == 'city') {

                                if ($models['Contact_Country']['id'] == 149 && isset($models['Contact_Area']['id'])) {
                                    $kicCountryCity = DB::table('kic_city')
                                        ->select('kic_city.cityId as id', 'kic_city.city_en as name')
                                        ->where('kic_city.governorate_code', '=', $models['Contact_Area']['id'])
                                        ->get();
                                    $vals['groups'][$j]['fields'][$i]['values'] = $kicCountryCity;
                                } else {
                                    $vals['groups'][$j]['fields'][$i]['values'] = [];
                                }
                            } else if ($item->db_column == 'area') {
                                if ($models['Contact_Country']['id'] == 149) {
                                    $country = DB::select(DB::raw($val[1]));
                                    $vals['groups'][$j]['fields'][$i]['values'] = $country;
                                } else {
                                    $vals['groups'][$j]['fields'][$i]['values'] = [];
                                }
                            } else if ($item->db_column == 'int_other_city') {

                                if ($models['Other_Country']['id'] == 149 && isset($models['Other_Area']['id'])) {
                                    $kicCountryCity = DB::table('kic_city')
                                        ->select('kic_city.cityId as id', 'kic_city.city_en as name')
                                        ->where('kic_city.governorate_code', '=', $models['Other_Area']['id'])
                                        ->get();
                                    $vals['groups'][$j]['fields'][$i]['values'] = $kicCountryCity;
                                } else {
                                    $vals['groups'][$j]['fields'][$i]['values'] = [];
                                }
                            } else if ($item->db_column == 'int_other_area') {
                                if ($models['Other_Country']['id'] == 149) {
                                    $country = DB::select(DB::raw($val[1]));
                                    $vals['groups'][$j]['fields'][$i]['values'] = $country;
                                } else {
                                    $vals['groups'][$j]['fields'][$i]['values'] = [];
                                }
                            } else if ($item->db_column == 'actual_beneficiary_city') {

                                if ($models['Actual_Ben_Country']['id'] == 149 && isset($models['Actual_Ben_Area']['id'])) {
                                    $kicCountryCity = DB::table('kic_city')
                                        ->select('kic_city.cityId as id', 'kic_city.city_en as name')
                                        ->where('kic_city.governorate_code', '=', $models['Actual_Ben_Area']['id'])
                                        ->get();
                                    $vals['groups'][$j]['fields'][$i]['values'] = $kicCountryCity;
                                } else {
                                    $vals['groups'][$j]['fields'][$i]['values'] = [];
                                }
                            } else if ($item->db_column == 'actual_beneficiary_area') {
                                if ($models['Actual_Ben_Country']['id'] == 149) {
                                    $country = DB::select(DB::raw($val[1]));
                                    $vals['groups'][$j]['fields'][$i]['values'] = $country;
                                } else {
                                    $vals['groups'][$j]['fields'][$i]['values'] = [];
                                }
                            } else {
                                $country = DB::select(DB::raw($val[1]));
                                $vals['groups'][$j]['fields'][$i]['values'] = $country;
                            }
                            $vals['groups'][$j]['fields'][$i]['sql'] = $val[1];
                            //$vals['groups'][$j]['fields'][$i]['onChanged'] = 'test()';
                        }
                    }
                    if ($item->db_column === 'specify_the_clint_type' || $item->db_column === 'any_potical_position' || $item->db_column == 'customer_declatation_status') {
                        $vals['groups'][$j]['fields'][$i]['buttons'][] = [
                            "classes" => $item->db_column == 'customer_declatation_status' ? "wizardCustomTerms" : "wizardCustom",
                            "label" => "information/معلومة",
                        ];
                    }

                    $vals['groups'][$j]['fields'][$i]['styleClasses'] = $item->styleClasses;
                    if (in_array($item->db_column, ['others', 'inheritance'])) {
                        $vals['groups'][$j]['fields'][$i]['styleClasses'] = $item->styleClasses . ' hide' . $item->db_column;
                    }
                    $vals['groups'][$j]['fields'][$i]['fieldClasses'] = $item->db_column;
                    $vals['groups'][$j]['fields'][$i]['validator'] = array("required");
                    $vals['groups'][$j]['fields'][$i]['validators'] = ($item->validator);
                    $vals['groups'][$j]['fields'][$i]['required'] = (($item->is_mandatory) ? true : false);
                    $vals['groups'][$j]['fields'][$i]['defaultVal'] = ($demo) ? $demo : $item->defaultVal;

                    if ($item->child_table) {
                        $checkData = DB::table('kic_form')
                            ->join('kic_form_section', 'kic_form_section.form_id', '=', 'kic_form.id')
                            ->join('kic_form_control', 'kic_form_control.section_id', '=', 'kic_form_section.id')
                            ->join($item->child_table, 'kic_form_control.is_arg', '=', $item->child_table . '.is_arg')
                            ->join('kic_form_control_type', 'kic_form_control_type.id', '=', $item->child_table . '.type_id')
                            ->select($item->child_table . '.defaultVal', $item->child_table . '.ref_db_name AS refdbname', 'kic_form_control.ref_db_name', 'kic_form_section.id as section_id', 'kic_form.label as title', 'kic_form.is_linked', 'kic_form.next_form_id', 'kic_form.next_form_arg', 'kic_form.type', $item->child_table . '.styleClasses', 'kic_form.db_table', 'kic_form_section.label as section_title', 'kic_form_section.show_label', 'kic_form_section.sort_no', $item->child_table . '.label as input_label', $item->child_table . '.sort_no as input_sort', $item->child_table . '.type_id', $item->child_table . '.input_type', 'kic_form_control_type.name as field_type', $item->child_table . '.is_mandatory', $item->child_table . '.db_column', $item->child_table . '.expression', $item->child_table . '.is_visible', $item->child_table . '.mask')
                            ->where('kic_form.id', $id)
                            ->where($item->child_table . '.is_arg', $item->section_id)
                            ->where('kic_form_control.is_visible', 1)
                            ->where($item->child_table . '.is_visible', 1)
                            //->orderBy('kic_form_section.id', 'kic_form_section.sort_no')
                            ->orderByRaw('kic_form_section.sort_no, input_sort ASC')
                            ->get();

                        if (count($checkData) > 0) {
                            if (!isset($checkDataArray[$item->section_id])) {

                                $KK = 0;
                                $vals['groups'][$j]['fields'][count($valData)]['type'] = "array";
                                //$vals['groups'][$j]['fields'][$i]['inputType'] = $valss->input_type;
                                $vals['groups'][$j]['fields'][count($valData)]['label'] = 'Beneciary';
                                $vals['groups'][$j]['fields'][count($valData)]['model'] = $item->store_table; //'object'.$item->section_id;
                                $vals['groups'][$j]['fields'][count($valData)]['inputName'] = 'columns';
                                $vals['groups'][$j]['fields'][count($valData)]['showRemoveButton'] = true;
                                $vals['groups'][$j]['fields'][count($valData)]['styleClasses'] = 'removeBtn addBorderClassNew';
                                $vals['groups'][$j]['fields'][count($valData)]['newElementButtonLabelClasses'] = 'btn btn-outline-primary mt-2 addMoreClass' . $item->section_id;
                                $vals['groups'][$j]['fields'][count($valData)]['items']['type'] = 'object';

                                $kl = 0;
                                foreach ($checkData as $valss) {
                                    //$models['object'.$item->section_id][$KK][$valss->refdbname] = null;
                                    $demo = $valss->defaultVal;
                                    // if (in_array($valss->refdbname, ['PIPNationalityId'])) {
                                    //     $demo = [
                                    //         'id' => 149,
                                    //         'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                    //     ];
                                    // }

                                    // if (in_array($valss->refdbname, ['NationalityId'])) {
                                    //     $demo = [
                                    //         'id' => 149,
                                    //         'name' => ($lang == 'en') ? 'Kuwait' : 'الكويت'
                                    //     ];
                                    // }

                                    if (!isset($models[$item->store_table][$KK][$valss->refdbname])) {
                                        $models[$item->store_table][$KK][$valss->refdbname] = $demo;
                                    }

                                    $checkDataArray[$item->section_id][] = $item->section_id;

                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['defaultVal'] = $valss->defaultVal;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['type'] = $valss->field_type;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['inputType'] = $valss->input_type;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['label'] = $valss->input_label;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['model'] = $valss->refdbname;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['placeholder'] = $valss->db_column;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['styleClasses'] = $valss->styleClasses;
                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['fieldClasses'] = $valss->db_column;

                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['validator'] = array("required");

                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['required'] = (($valss->is_mandatory) ? true : false);

                                    $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['selectOptions']['noneSelectedText'] = "please_select";

                                    if ($valss->refdbname == 'Tax_Reason_Detail') {
                                        $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['disabled'] = true;
                                    }

                                    if (isset($valss->expression)) {

                                        $exp = explode(":", $valss->expression);
                                        if ($exp[0] == 'txt') {
                                            $val = explode("=", $exp[1]);
                                            $option = [];

                                            foreach (explode(",", $val[1]) as $ke => $val) {

                                                if (in_array($valss->db_column, ['TIN_Reasons', 'trade_for_own_or_behalf', 'holding_us_passport_british_usa', 'have_mailing_address_telephone_usa', 'have_residency_usa_tax_purpose'])) {
                                                    $opVlaus = explode("->", $val);
                                                    $option[$ke]['value'] = trim($opVlaus[0]);
                                                    $option[$ke]['name'] = trim($opVlaus[1]);
                                                } else {
                                                    $opVlaus = explode("->", $val);
                                                    if (count($opVlaus) > 1) {
                                                        $option[$ke]['id'] = trim($ke);
                                                        $option[$ke]['name'] = trim($opVlaus[1]);
                                                    } else {
                                                        $option[$ke]['id'] = $ke;
                                                        $option[$ke]['name'] = $val;
                                                    }
                                                }
                                            }
                                            $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['values'] = $option; //explode(",", $val[1]);
                                        } elseif ($exp[0] == 'sql') {
                                            $val = explode("==", $exp[1]);

                                            if ($valss->db_column == 'address_city_id') {
                                                $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['values'] = [];
                                            } else {
                                                $country = DB::select(DB::raw($val[1]));
                                                $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['values'] = $country;
                                            }
                                            $vals['groups'][$j]['fields'][count($valData)]['items']['schema']['fields'][$kl]['sql'] = $val[1];
                                            //$vals['groups'][$j]['fields'][$i]['onChanged'] = 'test()';
                                        }
                                    }
                                    $kl++;
                                }
                            }
                        }
                    }
                    $i++;
                }
                $ftype = [];
                $ftype1 = [];
                if ($item->type == 'w') {
                    $All[$key] = $vals;
                } else {
                    $ftype1['type'] = "submit";
                    $ftype1['buttonText'] = "cancel";

                    $ftype['type'] = "submit";
                    $ftype['buttonText'] = "submit";
                    $ftype['validateBeforeSubmit'] = true;
                    array_push($vals['groups'][0]['fields'], $ftype1);
                    array_push($vals['groups'][0]['fields'], $ftype);
                    $All[1] = $vals;
                }
                $j++;
            }

            return response()->json([
                "code" => 200,
                'propTitle' => (
                    ($db_name == 'kpi_def') ? "kpi definition" : (($db_name == 'kpi_target') ? "add target wizard" : 'Wizard')
                ),
                'propSubTitle' => (
                    ($db_name == 'kpi_def') ? "kpi wizard title" : (($db_name == 'kpi_target') ? "target wizard title" : 'wizard title')
                ),
                "data" => (array)$All,
                "type" => $type,
                "dataValues" => [],
                "db_name" => 'kic_customerinfo',
                "model" => $models,
                //"kicModel" => $kicModel,
                "ekycstatus" => $ekycstatus,
                "notes" => count($ekycNotes) > 0 ? $ekycNotes : [],
                "reasons" => count($ekycReasons) > 0 ? $ekycReasons : [],
                "isNameDiffEn" => $isNameDiffEn,
                "isNameDiffAr" => $isNameDiffAr,
                "isKicCif" => $isKicCif,
                "isKicDoc" => $isKicDoc,
                "isKicDocApprove" => $isKicDocApprove,
                "isMenual" => (count($ekycData) > 0) ? $ekycData[0]->ismenual : null
            ]);
        }
    }

    public function loadtranslation(Request $request)
    {
        $translations = DB::table("kic_trans_table")
            ->select(DB::raw('*'))
            ->get();
        $enarray = $ararray = [];
        foreach ($translations as $key => $value) {
            if (isset($value->key_pos)) {
                $keyname = $value->key_name . '@' . $value->key_pos . '@' . $value->key_type;
            } else {
                $keyname = $value->key_name . '@' . $value->key_type;
            }
            $ararray[$keyname] = $value->value_ar;
            $enarray[$keyname] = $value->value_en;
        }

        $translationsCountry  = DB::table("kic_country")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsCountry as $key => $value) {
            $keyname = $value->COUNTRY_NAME_EN . '@l';
            $ararray[$keyname] = $value->COUNTRY_NAME_AR;
            $enarray[$keyname] = $value->COUNTRY_NAME_EN;
        }

        $translationsMarital  = DB::table("kic_maritalstatus")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsMarital as $key => $value) {
            $keyname = $value->DESC_EN . '@l';
            $ararray[$keyname] = $value->DESC_AR;
            $enarray[$keyname] = $value->DESC_EN;
        }

        $translationsGender  = DB::table("kic_gender")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsGender as $key => $value) {
            $keyname = $value->GenderEn . '@l';
            $ararray[$keyname] = $value->GenderAr;
            $enarray[$keyname] = $value->GenderEn;
        }

        $translationsTitle  = DB::table("kic_titles")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsTitle as $key => $value) {
            $keyname = $value->TitleEN . '@l';
            $ararray[$keyname] = $value->TitleAR;
            $enarray[$keyname] = $value->TitleEN;
        }

        $translationsClientType  = DB::table("kic_clienttype")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsClientType as $key => $value) {
            $keyname = $value->ClientTypeEn . '@l';
            $ararray[$keyname] = $value->ClientTypeAr;
            $enarray[$keyname] = $value->ClientTypeEn;
        }

        $translationsAccountType  = DB::table("kic_account_types")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsAccountType as $key => $value) {
            $keyname = $value->name . '@l';
            $ararray[$keyname] = $value->name_ar;
            $enarray[$keyname] = $value->name;
        }

        $translationsAnnualType  = DB::table("kic_annualincometypes")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsAnnualType as $key => $value) {
            $keyname = $value->AnnualIncomeEn . '@l';
            $ararray[$keyname] = $value->AnnualIncomeAr;
            $enarray[$keyname] = $value->AnnualIncomeEn;
        }

        $translationsEducation  = DB::table("kic_education")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsEducation as $key => $value) {
            $keyname = $value->EducationEn . '@l';
            $ararray[$keyname] = $value->EducationAr;
            $enarray[$keyname] = $value->EducationEn;
        }

        $translationsIncomeSource  = DB::table("kic_income_source")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsIncomeSource as $key => $value) {
            $keyname = $value->name . '@l';
            $ararray[$keyname] = $value->name_ar;
            $enarray[$keyname] = $value->name;
        }

        $translationsInvExperience  = DB::table("kic_inv_experience")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsInvExperience as $key => $value) {
            $keyname = $value->Inv_ExperienceEn . '@l';
            $ararray[$keyname] = $value->Inv_ExperienceAr;
            $enarray[$keyname] = $value->Inv_ExperienceEn;
        }

        $translationsInvObjectives  = DB::table("kic_inv_objectives")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsInvObjectives as $key => $value) {
            $keyname = $value->Inv_Objectives_En . '@l';
            $ararray[$keyname] = $value->Inv_Objective_Ar;
            $enarray[$keyname] = $value->Inv_Objectives_En;
        }

        $translationsInvPeriod  = DB::table("kic_inv_period")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsInvPeriod as $key => $value) {
            $keyname = $value->Inv_PeriodEn . '@l';
            $ararray[$keyname] = $value->Inv_PeriodAr;
            $enarray[$keyname] = $value->Inv_PeriodEn;
        }

        $translationsInvRisk  = DB::table("kic_inv_risk")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsInvRisk as $key => $value) {
            $keyname = $value->Inv_Risken . '@l';
            $ararray[$keyname] = $value->Inv_RiskAr;
            $enarray[$keyname] = $value->Inv_Risken;
        }

        $translationsLanguage  = DB::table("kic_languages")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsLanguage as $key => $value) {
            $keyname = $value->LanguageEn . '@l';
            $ararray[$keyname] = $value->LanguageAr;
            $enarray[$keyname] = $value->LanguageEn;
        }

        $translationsNetworth  = DB::table("kic_networthtype")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsNetworth as $key => $value) {
            $keyname = $value->NetWorthEn . '@l';
            $ararray[$keyname] = $value->NetWorthAr;
            $enarray[$keyname] = $value->NetWorthEn;
        }

        $translationsPortfolio  = DB::table("kic_portfoliotypes")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsPortfolio as $key => $value) {
            $keyname = $value->PortfolioTypeEn . '@l';
            $ararray[$keyname] = $value->PortfolioTypeAr;
            $enarray[$keyname] = $value->PortfolioTypeEn;
        }

        $translationsProfession  = DB::table("kic_profession")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsProfession as $key => $value) {
            $keyname = $value->name . '@l';
            $ararray[$keyname] = $value->name_ar;
            $enarray[$keyname] = $value->name;
        }

        $translationsPurpose  = DB::table("kic_purpose_of_Investment")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsPurpose as $key => $value) {
            $keyname = $value->name . '@l';
            $ararray[$keyname] = $value->name_ar;
            $enarray[$keyname] = $value->name;
        }

        $translationsTradingValue  = DB::table("kic_tradingvalues")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsTradingValue as $key => $value) {
            $keyname = $value->TradingValue_YEn . '@l';
            $ararray[$keyname] = $value->TradingValueYAr;
            $enarray[$keyname] = $value->TradingValue_YEn;
        }

        $translationsRelationship  = DB::table("kic_relation")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsRelationship as $key => $value) {
            $keyname = $value->relation_en . '@l';
            $ararray[$keyname] = $value->relation_ar;
            $enarray[$keyname] = $value->relation_en;
        }

        $translationsCity  = DB::table("kic_city")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsCity as $key => $value) {
            $keyname = $value->city_en . '@l';
            $ararray[$keyname] = $value->city_ar;
            $enarray[$keyname] = $value->city_en;
        }

        $translationsGovernate  = DB::table("kic_governorate")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsGovernate as $key => $value) {
            $keyname = $value->governorate_en . '@l';
            $ararray[$keyname] = $value->governorate_ar;
            $enarray[$keyname] = $value->governorate_en;
        }

        $translationsPoliticalPosition  = DB::table("kic_political_position")
            ->select(DB::raw('*'))
            ->get();
        foreach ($translationsPoliticalPosition as $key => $value) {
            $keyname = $value->name . '@l';
            $ararray[$keyname] = $value->name_ar;
            $enarray[$keyname] = $value->name;
        }


        if ($translations) {
            $trans1 = array("en" => $enarray);
            $trans2 = array("ar" => $ararray);
        }

        return response()->json(
            [
                "code" => 200,
                'translations' => array_merge($trans1, $trans2)
            ]
        );
    }

    public function store(Request $request)
    {
        $table = $request->get('db_name');
        $sessionId = $request->get('sessionId');

        $isSubmitted = ($request->get('isSubmitted')) ? $request->get('isSubmitted') : 0;

        $datetimeFormat = 'Y-m-d';
        $date = new \DateTime();

        $lang = ($request->get('language')) ? $request->get('language') : 'en';

        $request['DOB'] = is_numeric($request->get('DOB')) ? $date->setTimestamp(($request->get('DOB') / 1000))->format($datetimeFormat) : $request->get('DOB');
        $request['CivilIdExpiry'] = is_numeric($request->get('CivilIdExpiry')) ? $date->setTimestamp(($request->get('CivilIdExpiry') / 1000))->format($datetimeFormat) : $request->get('CivilIdExpiry');
        $request['PassportExpiry'] = is_numeric($request->get('PassportExpiry')) ?  $date->setTimestamp(($request->get('PassportExpiry') / 1000))->format($datetimeFormat) : $request->get('PassportExpiry');
        $request['FATCA_ExpiryDate'] = is_numeric($request->get('FATCA_ExpiryDate')) ? $date->setTimestamp(($request->get('FATCA_ExpiryDate') / 1000))->format($datetimeFormat) : $request->get('FATCA_ExpiryDate');
        $request['CRS_BirthDate'] = is_numeric($request->get('CRS_BirthDate')) ? $date->setTimestamp(($request->get('CRS_BirthDate') / 1000))->format($datetimeFormat) : $request->get('CRS_BirthDate');

        $request['AccountType'] = $request->get('AccountType') ? implode(",", array_column($request->get('AccountType'), 'id')) : $request->get('AccountType');
        $request['Income_Source'] = $request->get('Income_Source') ? implode(",", array_column($request->get('Income_Source'), 'id')) : $request->get('Income_Source');

        $request['PlaceOfBirthId'] = $request->get('PlaceOfBirthId') ? $request->get('PlaceOfBirthId')['id'] : $request->get('PlaceOfBirthId');
        $request['NationalityId'] = $request->get('NationalityId') ? $request->get('NationalityId')['id'] : $request->get('NationalityId');
        $request['OtherNationalityId'] = $request->get('OtherNationalityId') ? $request->get('OtherNationalityId')['id'] : $request->get('OtherNationalityId');
        $request['PlaceOfIssueId'] = $request->get('PlaceOfIssueId') ? $request->get('PlaceOfIssueId')['id'] : $request->get('PlaceOfIssueId');
        $request['CRS_Curr_CountyrId'] = $request->get('CRS_Curr_CountyrId') ? $request->get('CRS_Curr_CountyrId')['id'] : $request->get('CRS_Curr_CountyrId');
        $request['CRS_Mail_CountryId'] = $request->get('CRS_Mail_CountryId') ? $request->get('CRS_Mail_CountryId')['id'] : $request->get('CRS_Mail_CountryId');
        $request['CRS_BirthCountryId'] = $request->get('CRS_BirthCountryId') ? $request->get('CRS_BirthCountryId')['id'] : $request->get('CRS_BirthCountryId');
        $request['Contact_Country'] = $request->get('Contact_Country') ? $request->get('Contact_Country')['id'] : $request->get('Contact_Country');
        $request['Other_Country'] = $request->get('Other_Country') ? $request->get('Other_Country')['id'] : $request->get('Other_Country');
        $request['Actual_Ben_Country'] = $request->get('Actual_Ben_Country') ? $request->get('Actual_Ben_Country')['id'] : $request->get('Actual_Ben_Country');

        $request['Contact_Area'] = $request->get('Contact_Area') ? $request->get('Contact_Area')['id'] : $request->get('Contact_Area');
        $request['Contact_City'] = $request->get('Contact_City') ? $request->get('Contact_City')['id'] : $request->get('Contact_City');

        $request['Other_Area'] = $request->get('Other_Area') ? $request->get('Other_Area')['id'] : $request->get('Other_Area');
        $request['Other_City'] = $request->get('Other_City') ? $request->get('Other_City')['id'] : $request->get('Other_City');
        $request['NoofChildren'] = $request->get('NoofChildren') ? $request->get('NoofChildren') : 0;

        $request['Actual_Ben_Area'] = $request->get('Actual_Ben_Area') ? $request->get('Actual_Ben_Area')['id'] : $request->get('Actual_Ben_Area');
        $request['Actual_Ben_City'] = $request->get('Actual_Ben_City') ? $request->get('Actual_Ben_City')['id'] : $request->get('Actual_Ben_City');

        $request['customer_declatation_status'] = $request->get('customer_declatation_status') == '0' ? NULL : '1';

        $request['PIP_Relative'] = $request['PIP_Relative'] ? (int)$request['PIP_Relative'] : $request['PIP_Relative'];
        unset($request['client_name']);
        unset($request['date']);
        unset($request['db_name']);
        unset($request['sessionId']);
        unset($request['token']);
        unset($request['income_source_custom']);
        unset($request['language']);
        unset($request['Sector']);
        unset($request['CustomerId']);
        unset($request['industryid']);
        unset($request['Targetid']);
        unset($request['StatusId']);
        unset($request['isSubmitted']);

        $newArray = [];
        $newObjectArray = [];
        foreach ($request->all() as $key => $val) {
            if (is_array($val)) {
                $newObjectArray[$key] = $val;
            } else {
                $newArray[$key] = $val;
            }
        }

        if (empty($sessionId) || !isset($sessionId) || $sessionId === 0) {
            $model = new Dynamic($newArray);
            $model->setTable($table);
            if ($model->save($newArray)) {

                foreach ($newObjectArray  as $okey => $oval) {
                    $checkTable = DB::select(DB::raw("SELECT * FROM information_schema.tables WHERE table_schema = 'ekyc' AND table_name = '$okey' LIMIT 1"));
                    if (count($checkTable) > 0) {
                        foreach ($oval as $values) {
                            $values['CustomerId'] = $model->latest()->first()->CustomerId;

                            if (isset($values['PIPNationalityId'])) {
                                $values['PIPNationalityId'] = $values['PIPNationalityId'] ? $values['PIPNationalityId']['id'] : $values['PIPNationalityId'];
                            }
                            if (isset($values['NationalityId'])) {
                                $values['NationalityId'] = $values['NationalityId'] ? $values['NationalityId']['id'] : $values['NationalityId'];
                            }
                            if (isset($values['TIN_Reasons'])) {
                                $values['TIN_Reasons'] = $values['TIN_Reasons'] ? (int)$values['TIN_Reasons'] : $values['TIN_Reasons'];
                            }
                            $model = new Dynamic($values);
                            $model->setTable($okey);
                            $model->save($values);
                        }
                    }
                }

                $getTitle = DB::table('kic_titles')
                    ->where('TitleID', '=', $request['TitleId'])
                    ->get();

                $titleName = $fullName = '';
                if (count($getTitle) > 0) {

                    $titleName = (isset($lang) && $lang == 'ar') ? $getTitle[0]->TitleAR : $getTitle[0]->TitleEN;
                    $fullName = (isset($lang) && $lang == 'ar') ? $request['FirstNameAr'] . ' ' . $request['MiddleNameAr'] . ' ' . $request['FamilyNameAr'] : $request['FirstNameEn'] . ' ' . $request['MiddleNameEn'] . ' ' . $request['FamilyNameEn'];
                }

                //inform customer
                if ($request['ekycstatus'] == 3 && $isSubmitted == '1') {
                    $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=7 and channel='e' and status_active=1"));
                    if (count($checkEmailEvent) > 0) {
                        $notiId = $checkEmailEvent[0]->id;
                        //echo $model->latest()->first()->CustomerId . '=' . $notiId;
                        //die;
                        //$notiContent = strtr($checkEmailEvent[0]->contents, $varMap);

                        //$notiId = $checkInBoxEvent[0]->id;
                        $this->dispatch(new SendEmailCustomer($users = [$model->latest()->first()->CustomerId], 'Test', $notiId));

                        //$notificUsers = \DB::select(\DB::raw("SELECT * from notif_to_user where notif_id=$notiId"));
                        //$sendMail = true;
                        //$this->dispatch(new ProcessNotificationEmail($notificUsers, 'Test', $notiContent));
                    }
                } else if ($isSubmitted == '1') {

                    $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=3 and channel='e' and status_active=1"));
                    if (count($checkEmailEvent) > 0) {
                        $notiId = $checkEmailEvent[0]->id;
                        $this->dispatch(new SendEmailCustomer($users = [$model->latest()->first()->CustomerId], 'Test', $notiId));
                    }
                }
                if ($isSubmitted == '1') {
                    $checkSmsEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=3 and channel='s' and status_active=1"));
                    if (count($checkSmsEvent) > 0 && $model->latest()->first()->AddInfo_SMSService == '1') {
                        $notiId = $checkSmsEvent[0]->id;
                        $this->dispatch(new SendSmsCustomer($users = [$model->latest()->first()->CustomerId], 'Test', $notiId));
                    }
                }
                $messageRedis = [];
                $messageRedis['msg'] = 'data added';
                Redis::publish('loadEkyc', json_encode($messageRedis, true));

                return response()->json([
                    "code" => 200,
                    "id" => $model->latest()->first()->CustomerId,
                    "msg" => "data added",
                    "name" => $titleName . ' ' . $fullName
                ]);
            } else {
                return response()->json([
                    "code" => 400,
                    "id" => null,
                    "msg" => "data not inserted"
                ]);
            }
        } else {
            unset($newArray['token']);
            $update = DB::table($table);
            $update->where('CustomerId', (int)$sessionId);
            $update->update($newArray);

            if ($update) {

                foreach ($newObjectArray  as $okey => $oval) {
                    $checkTable = DB::select(DB::raw("SELECT * FROM information_schema.tables WHERE table_schema = 'ekyc' AND table_name = '$okey' LIMIT 1"));
                    if (count($checkTable) > 0) {
                        DB::select(DB::raw("delete from $okey  where CustomerId=$sessionId"));
                        foreach ($oval as $values) {
                            $values['CustomerId'] = $sessionId;
                            if (isset($values['PIPNationalityId'])) {
                                $values['PIPNationalityId'] = $values['PIPNationalityId'] ? $values['PIPNationalityId']['id'] : $values['PIPNationalityId'];
                            }
                            if (isset($values['NationalityId'])) {
                                $values['NationalityId'] = $values['NationalityId'] ? $values['NationalityId']['id'] : $values['NationalityId'];
                            }
                            if (isset($values['TIN_Reasons'])) {
                                $values['TIN_Reasons'] = $values['TIN_Reasons'] ? (int)$values['TIN_Reasons'] : $values['TIN_Reasons'];
                            }

                            $updatemodel = new Dynamic($values);
                            $updatemodel->setTable($okey);
                            $updatemodel->save($values);
                        }
                    }
                }

                $getTitle = DB::table('kic_titles')
                    ->where('TitleID', '=', $request['TitleId'])
                    ->get();

                $titleName = $fullName = '';
                if (count($getTitle) > 0) {

                    $titleName = (isset($lang) && $lang == 'ar') ? $getTitle[0]->TitleAR : $getTitle[0]->TitleEN;
                    $fullName = (isset($lang) && $lang == 'ar') ? $request['FirstNameAr'] . ' ' . $request['MiddleNameAr'] . ' ' . $request['FamilyNameAr'] : $request['FirstNameEn'] . ' ' . $request['MiddleNameEn'] . ' ' . $request['FamilyNameEn'];
                }

                //inform customer
                if ($request['ekycstatus'] == 3 && $isSubmitted == '1') {
                    $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=7 and channel='e' and status_active=1"));
                    if (count($checkEmailEvent) > 0) {
                        $notiId = $checkEmailEvent[0]->id;
                        $this->dispatch(new SendEmailCustomer($users = [$sessionId], 'Test', $notiId));
                    }
                } else if ($isSubmitted == '1') {

                    $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=4 and channel='e' and status_active=1"));
                    if (count($checkEmailEvent) > 0) {
                        $notiId = $checkEmailEvent[0]->id;
                        $this->dispatch(new SendEmailCustomer($users = [$sessionId], 'Test', $notiId));
                    }
                }
                if ($isSubmitted == '1') {
                    $checkSmsEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=4 and channel='s' and status_active=1"));
                    if (count($checkSmsEvent) > 0 && $newArray['AddInfo_SMSService'] == '1') {
                        $notiId = $checkSmsEvent[0]->id;
                        $this->dispatch(new SendSmsCustomer($users = [$sessionId], 'Test', $notiId));
                    }
                }
                $messageRedis = [];
                $messageRedis['msg'] = 'data updated';
                Redis::publish('loadEkyc', json_encode($messageRedis, true));

                return response()->json([
                    "code" => 200,
                    "id" => $sessionId,
                    "msg" => "data updated",
                    "name" => $titleName . ' ' . $fullName
                ]);
            } else {
                return response()->json([
                    "code" => 400,
                    "id" => $sessionId,
                    "msg" => "data not updated"
                ]);
            }
        }
    }

    public function fetchAreas(Request $request, $id)
    {
        $areas = DB::table("kic_governorate")
            ->select(DB::raw('governorateId as id, governorate_en as name'))
            ->where('Country_ISO', $id)
            ->get();

        return response()->json([
            "code" => 200,
            "areas" => $areas
        ]);
    }
    public function fetchCity(Request $request, $id)
    {
        $areas = DB::table("kic_city")
            ->select(DB::raw('cityId as id, city_en as name'))
            ->where('governorate_code', $id)
            ->get();

        return response()->json([
            "code" => 200,
            "areas" => $areas
        ]);
    }

    public function fetchRelatives(Request $request, $id)
    {
        # code...
        $relative = DB::table("kic_relation")
            ->select(DB::raw('relationId as id, relation_en as name'))
            ->where('relation_level', $id)
            ->get();

        return response()->json([
            "code" => 200,
            "relative" => $relative
        ]);
    }

    public function loadSectionFields(Request $request, $id)
    {

        $customerId = base64_decode($request->get('customerId'));

        $fields = DB::table('kic_form_control')
            ->join('kic_form_section', 'kic_form_section.id', '=', 'kic_form_control.section_id')
            ->select('kic_form_control.db_column', 'kic_form_control.child_table')
            ->where('kic_form_section.sort_no', '=', $id)
            ->where('kic_form_control.is_label', '=', '0')
            ->where('kic_form_control.is_visible', '!=', 0)
            ->orderBy('kic_form_control.sort_no')
            ->get();

        $fieldArray = [];
        foreach ($fields as $fieldKey => $fieldVal) {

            foreach ($fieldVal as $fk => $fv) {
                if (isset($fv) && $fv != '') {
                    if ($fk != 'child_table') {
                        $fieldArray[] = $fv;
                    }
                }

                if ($fk == 'child_table' && isset($fv) && $fv != '') {
                    $subFields = DB::table($fv)
                        ->join('kic_form_section', 'kic_form_section.id', '=', $fv . '.section_id')
                        ->select($fv . '.db_column')
                        ->where('kic_form_section.sort_no', '=', $id)
                        ->where($fv . '.is_label', '=', '0')
                        ->where($fv . '.is_visible', '!=', 0)
                        ->orderBy($fv . '.sort_no')
                        ->get();
                    foreach ($subFields as $subKey => $subVal) {

                        foreach ($subVal as $sk => $sv) {
                            $fieldArray[] = $sv;
                        }
                    }
                }
            }
        }

        $getData = DB::table('kic_ekyc_customer_notes')
            ->select('fieldName', 'fieldComment')
            ->where('customerId', '=', $customerId)
            ->where('sectionId', $id)
            ->get();

        $getDataDocument = DB::table('kic_ekyc_customer_notes')
            ->select('fieldName', 'fieldComment')
            ->where('customerId', '=', $customerId)
            ->where('sectionId', 0)
            ->get();

        return response()->json([
            "code" => 200,
            "fieldArray" => $fieldArray,
            "getData" => $getData,
            "getDataDocument" => $getDataDocument
        ]);
    }

    public function saveSectionFields(Request $request, $id, $sectionId)
    {

        $id = base64_decode($id);

        if (count($request->all()) > 0) {
            DB::select(DB::raw("delete from kic_ekyc_customer_notes where CustomerId=$id and sectionId=($sectionId + 1) OR sectionId = 0"));

            foreach ($request->all() as $keys => $values) {
                //echo count($values);
                if (isset($values['columns'])) {
                    foreach ($values['columns'] as $ke => $vl) {
                        if (isset($vl['fieldComment']) && $vl['fieldComment'] != '') {
                            $vl['customerId'] = $id;
                            $vl['sectionId'] = $sectionId + 1;

                            //var_dump($vl);
                            DB::table('kic_ekyc_customer_notes')->insert($vl);
                        }
                    }
                } else {
                    $vl['customerId'] = $id;
                    $vl['sectionId'] = 0;
                    $vl['fieldName'] = 'file';
                    $vl['fieldComment'] = $values['documentComment'];
                    DB::table('kic_ekyc_customer_notes')->insert($vl);
                }
            }
        }

        return response()->json([
            "code" => 200,
            "msg" => 'notes_added'
        ]);
        //var_dump($request->all());
        //die;
        # code...
    }

    public function updateKyc(Request $request, $formId, $customerId, $status)
    {
        $formId = base64_decode($formId);
        $customerId = base64_decode($customerId);
        $isKicDocApprove = false;

        $ekycData = DB::table('kic_customerinfo')
            ->where('kic_customerinfo.CustomerId', '=', $customerId)
            ->get();
        if ($status == 1) {
            $kicDoc = KicCustomerDocument::where('customerId', $customerId)->get();
            if (count($kicDoc) > 0) {
                $statsApprove = 0;
                foreach ($kicDoc as $kicKey => $kicVal) {
                    $statsApprove = ($kicVal->status == 1) ? $statsApprove + 1 : $statsApprove;
                }
                $isKicDocApprove =  ($statsApprove == count($kicDoc)) ? true : false;
            }
            if ($isKicDocApprove == true) {

                foreach ($ekycData as $key => $val) {
                    foreach ($val as $k => $v) {
                        $models[$k] = $v;
                        if ($k == 'BeneficiaryTypeId') {

                            $models[$k] = strval($v);
                            $ekycDataBeni = DB::table('kic_customerbeneficiary')
                                ->select('BeneficiaryCivilId', 'BeneficiaryName', 'NationalityId', 'RelationShip', 'Address')
                                ->where('kic_customerbeneficiary.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                                ->get();

                            foreach ($ekycDataBeni as $benkey => $benval) {

                                $new = [];
                                foreach ($benval as $benk => $benv) {

                                    if ($v == '1') {
                                        $new[$benk] = $benv;
                                    } else {
                                        $new[$benk] = null;
                                    }
                                }

                                $models['kic_customerbeneficiary'][] = $new;
                            }
                        }

                        if ($k == 'PIP_Relative') {

                            $models[$k] = strval($v);
                            $ekycDataBeni = DB::table('kic_customerrelatives')
                                ->select('PIPName', 'PIPRelationship', 'PIPPostion', 'PIPNationalityId')
                                ->where('kic_customerrelatives.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                                ->get();

                            foreach ($ekycDataBeni as $benkey => $benval) {

                                $new = [];
                                foreach ($benval as $benk => $benv) {

                                    if ($v == '1') {
                                        $new[$benk] = $benv;
                                    } else {
                                        $new[$benk] = null;
                                    }
                                }
                                $models['kic_customerrelatives'][] = $new;
                            }
                        }
                    }

                    $ekycDataTin = DB::table('kic_customer_tin')
                        ->select('Tax_No', 'Tax_Reason', 'TIN_Reasons', 'Tax_Reason_Detail')
                        ->where('kic_customer_tin.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                        ->get();

                    foreach ($ekycDataTin as $benkey => $benval) {

                        $new = [];
                        foreach ($benval as $benk => $benv) {
                            $new[$benk] = $benv;
                        }
                        $models['kic_customer_tin'][] = $new;
                    }

                    $ekycDataShare = DB::table('kic_customershares')
                        ->select('CompanyName', 'FinancialMarket', 'Postion', 'NumberofShares')
                        ->where('kic_customershares.CustomerId', '=', (string)$ekycData[0]->CustomerId)
                        ->get();

                    foreach ($ekycDataShare as $benkey => $benval) {

                        $new = [];
                        foreach ($benval as $benk => $benv) {
                            $new[$benk] = $benv;
                        }
                        $models['kic_customershares'][] = $new;
                    }
                }

                $getData = DB::table('kic_customerinfo_cif')->select('sector_id', 'industryId', 'targetId', 'customerstatusId')->where('customerId', $ekycData[0]->CustomerId)->get();
                if (count($getData) > 0 && count($ekycData) > 0) {

                    $models['kic_cif_info'] = (array)$getData[0];
                    $getDataObject = DB::table('kic_customerinfo_business')->select(DB::raw('GROUP_CONCAT(businessId) as businessId'))->where('customerId', $ekycData[0]->CustomerId)->get();
                    if (count($getDataObject) > 0) {
                        $models['kic_cif_info']['businessId'] = $getDataObject[0]->businessId;
                    }
                }

                // var_dump(json_encode($models));
                // die;
                $soap = new SoapClient(
                    'http://kicpaci/ClientService.svc?singleWsdl',
                    array(
                        'soap_version' => 'SOAP_1_2',
                        'location' => 'http://kicpaci/ClientService.svc',
                    )
                );
                $response1 = $soap->PostCustomerDetail(['StringValue' => json_encode($models)]);

                if (isset($response1) && $response1->PostCustomerDetailResult == 1 && $status == 1) {
                    $affected = DB::table('kic_customerinfo')
                        ->where('CustomerId', $customerId)
                        ->update(['ekycstatus' => $status, 'responseAfterPost' => $response1->PostCustomerDetailResult]);

                    DB::select(DB::raw("delete from kic_ekyc_customer_notes  where CustomerId='" . (string)$ekycData[0]->CustomerId . "'"));
                    DB::select(DB::raw("delete from kic_ekyc_customer_reject_reason  where CustomerId='" . (string)$ekycData[0]->CustomerId . "'"));

                    $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=5 and channel='e' and status_active=1"));
                    if (count($checkEmailEvent) > 0) {
                        $notiId = $checkEmailEvent[0]->id;
                        $this->dispatch(new SendEmailCustomer($users = [$ekycData[0]->CustomerId], 'Test', $notiId));
                    }

                    $checkSmsEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=5 and channel='s' and status_active=1"));
                    if (count($checkSmsEvent) > 0 && $ekycData[0]->AddInfo_SMSService == '1') {
                        $notiId = $checkSmsEvent[0]->id;
                        $this->dispatch(new SendSmsCustomer($users = [$ekycData[0]->CustomerId], 'Test', $notiId));
                    }

                    $messageRedis = [];
                    $messageRedis['msg'] = 'kyc_approved';
                    Redis::publish('loadEkyc', json_encode($messageRedis, true));

                    return response()->json([
                        "code" => 200,
                        "msg" => 'kyc_approved'
                    ]);
                } else {
                    return response()->json([
                        "code" => 400,
                        "msg" => 'not_updated'
                    ]);
                }
            } else {
                return response()->json([
                    "code" => 201,
                    "msg" => 'please_approve_document'
                ]);
            }
        }
        if ($status != 1) {
            $affected = DB::table('kic_customerinfo')
                ->where('CustomerId', $customerId)
                ->update(['ekycstatus' => $status]);
        }
        // die;
        // $response = $soap->GetCustomerDetail(['CivilId' => '279051603654']);
        // var_dump($response);
        // die;


        if ($affected && $status == 2) {

            DB::select(DB::raw("delete from kic_ekyc_customer_notes  where CustomerId='" . (string)$ekycData[0]->CustomerId . "'"));

            DB::table('kic_ekyc_customer_reject_reason')
                ->insert([
                    'customerId' => (string)$ekycData[0]->CustomerId,
                    'reason' => $request->get('rejectNote'),
                ]);

            $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=6 and channel='e' and status_active=1"));
            if (count($checkEmailEvent) > 0) {
                $notiId = $checkEmailEvent[0]->id;
                $this->dispatch(new SendEmailCustomer($users = [$ekycData[0]->CustomerId], $request->get('rejectNote'), $notiId));
            }

            $checkSmsEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=6 and channel='s' and status_active=1"));
            if (count($checkSmsEvent) > 0 && $ekycData[0]->AddInfo_SMSService == '1') {
                $notiId = $checkSmsEvent[0]->id;
                $this->dispatch(new SendSmsCustomer($users = [$ekycData[0]->CustomerId], 'Test', $notiId));
            }

            $messageRedis = [];
            $messageRedis['msg'] = 'kyc_rejected';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));

            return response()->json([
                "code" => 200,
                "msg" => 'kyc_rejected'
            ]);
        } else if ($affected && $status == 3) {
            DB::select(DB::raw("delete from kic_ekyc_customer_reject_reason  where CustomerId='" . (string)$ekycData[0]->CustomerId . "'"));

            $checkEmailEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=7 and channel='e' and status_active=1"));
            if (count($checkEmailEvent) > 0) {
                $notiId = $checkEmailEvent[0]->id;
                $this->dispatch(new SendEmailCustomer($users = [$ekycData[0]->CustomerId], 'Test', $notiId));
            }

            $checkSmsEvent = DB::select(DB::raw("SELECT * from notif_def where event_id=7 and channel='s' and status_active=1"));
            if (count($checkSmsEvent) > 0 && $ekycData[0]->AddInfo_SMSService == '1') {
                $notiId = $checkSmsEvent[0]->id;
                $this->dispatch(new SendSmsCustomer($users = [$ekycData[0]->CustomerId], 'Test', $notiId));
            }

            $messageRedis = [];
            $messageRedis['msg'] = 'kyc_informed';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));

            return response()->json([
                "code" => 200,
                "msg" => 'kyc_informed'
            ]);
        } else if ($affected && $status == 5) {
            //DB::select(DB::raw("delete from kic_ekyc_customer_reject_reason  where CustomerId='" . (string)$ekycData[0]->CustomerId . "'"));

            $messageRedis = [];
            $messageRedis['msg'] = 'kyc_panding_for_sign';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));
            return response()->json([
                "code" => 200,
                "msg" => 'kyc_panding_for_sign'
            ]);
        } else {
            return response()->json([
                "code" => 400,
                "msg" => 'not_updated'
            ]);
        }

        return response()->json([
            "code" => 400,
            "msg" => 'not_updated'
        ]);
    }

    public function getInfo(Request $request, $id)
    {

        $ekyc = DB::table('kic_customerinfo')
            ->where('CivilId', '=', $id)
            ->get();

        if (count($ekyc) > 0) {

            // $ekycNotes = DB::table('kic_ekyc_customer_notes')
            //     ->where('CustomerId', '=', (string)$ekyc[0]->CustomerId)
            //     ->get();

            // $ekycReasons = DB::table('kic_ekyc_customer_reject_reason')
            //     ->where('CustomerId', '=', (string)$ekyc[0]->CustomerId)
            //     ->get();

            return response()->json([
                "code" => 200,
                "msg" => 'user_found',
                "CustomerId" => (string)$ekyc[0]->CustomerId,
                // "notes" => count($ekycNotes) > 0 ? $ekycNotes : null,
                // "reasons" => count($ekycReasons) > 0 ? $ekycReasons : null,

            ]);
        } else {
            return response()->json([
                "code" => 201,
                "msg" => 'user_not_exist',
                // "notes" => null,
                // "reasons" => null
            ]);
        }
    }

    public function checkCivilIdExist(Request $request, $civilId)
    {

        $checkData = DB::table('kic_customerinfo')
            ->where('CivilId', '=', $civilId)
            ->orderBy('CustomerId', 'DESC')
            ->limit(1)
            ->get();
        if (count($checkData) > 0) {
            return response()->json([
                "code" => 201,
                "msg" => 'user_exist',
                'status' => true,
                "status_code" => $checkData[0]->ekycstatus,
                "id" => $checkData[0]->CustomerId,
                "CivilId" => $checkData[0]->CivilId,
            ]);
        } else {
            return response()->json([
                "code" => 201,
                'status' => false,
                "msg" => 'user_not_exist',
            ]);
        }
    }

    public function ekycChangeStatus(Request $request, $civilId, $status)
    {
        if ($status == 0) {
            $result = DB::table('kic_customerinfo')
                ->where('CivilId', '=', $civilId)
                ->orderBy('CustomerId', 'DESC')
                ->take(1)
                ->update(['ekycstatus' => '4']);

            $messageRedis = [];
            $messageRedis['msg'] = 'status changed';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));

            return response()->json([
                "code" => 200,
                'msg' => 'status changed'
            ]);
        } else {
            $result = DB::table('kic_customerinfo')
                ->where('CivilId', '=', $civilId)
                ->orderBy('CustomerId', 'DESC')
                ->take(1)
                ->delete();
            $messageRedis = [];
            $messageRedis['msg'] = 'deleted';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));
            return response()->json([
                "code" => 200,
                'msg' => 'deleted'
            ]);
        }
    }

    public function fetchBusiness($deptid)
    {
        $ekycbusiness = DB::table('kic_import_business')->select('NAME as name', 'deptid as id')->where('KICDeptId', $deptid)->get();
        return response()->json([
            "code" => 200,
            'ekycbusiness' => $ekycbusiness
        ]);
    }

    public function ekyccustomercif(Request $request)
    {


        $customerId = base64_decode($request->get('customerId'));
        $getData = DB::table('kic_customerinfo_cif')->where('customerId', $customerId)->get();
        if (count($getData) > 0) {
            KicCustomerinfoCif::where('customerId', $customerId)->delete();
            KicCustomerinfoBusiness::where('customerId', $customerId)->delete();
        }


        foreach ($request->get('data')['object'] as $key => $val) {

            foreach (($val) as $k => $v) {

                $getData = DB::table('kic_import_business')->where('deptid', $v)->first();
                $kicbusiness = new KicCustomerinfoBusiness();
                $kicbusiness->customerId = $customerId;
                $kicbusiness->sectorId = $getData->sectorid;
                $kicbusiness->departmentId = $getData->KICDeptId;
                $kicbusiness->businessId = $v;
                $kicbusiness->created_by = auth()->user()->id;
                $kicbusiness->save();
            }
        }

        $kiccif = new KicCustomerinfoCif();
        $kiccif->customerId = $customerId;
        $kiccif->sector_id = $request->get('data')['sector']['id'];
        $kiccif->industryId = $request->get('data')['ekycindustry']['id'];
        $kiccif->targetId = $request->get('data')['ekyctargets']['id'];
        $kiccif->customerstatusId = $request->get('data')['ekyccustometstatus']['id'];
        $kiccif->created_by = auth()->user()->id;
        $kiccif->save();

        $messageRedis = [];
        $messageRedis['msg'] = 'business_added';
        Redis::publish('loadEkyc', json_encode($messageRedis, true));

        return response()->json([
            "code" => 200,
            'msg' => 'business_added'
        ]);
    }

    public function getEkycCustomerCif($customerId)
    {
        $customerId = base64_decode($customerId);
        $getData = DB::table('kic_customerinfo_cif')->where('customerId', $customerId)->get();
        if (count($getData) > 0) {


            $t24Sector = DB::select(DB::raw("select * from t24sectors s where  s.sectorid in (" . $getData[0]->sector_id . ")"));
            if (count($t24Sector) > 0) {
                $ji = 0;
                $t24sec = [];
                foreach ($t24Sector as $typeVal) {
                    $t24sec = [
                        'id' => (int)$typeVal->sectorid,
                        'name' => $typeVal->DESCRIPTION
                    ];
                    $ji++;
                }
            } else {
                $t24sec = null;
            }

            $kiccustometstatus = DB::select(DB::raw("select * from kic_customer_status s where  s.id in (" . $getData[0]->customerstatusId . ")"));
            if (count($kiccustometstatus) > 0) {
                $ji = 0;
                $kiccuststatus = [];
                foreach ($kiccustometstatus as $typeVal) {
                    $kiccuststatus = [
                        'id' => $typeVal->id,
                        'name' => $typeVal->StatusEn
                    ];
                    $ji++;
                }
            } else {
                $kiccuststatus = null;
            }

            $kicindustry = DB::select(DB::raw("select * from kic_industry s where  s.id in (" . $getData[0]->industryId . ")"));
            if (count($kicindustry) > 0) {
                $ji = 0;
                $kicind = [];
                foreach ($kicindustry as $typeVal) {
                    $kicind = [
                        'id' => $typeVal->id,
                        'name' => $typeVal->IndustryEn
                    ];
                    $ji++;
                }
            } else {
                $kicind = null;
            }

            $kictarget = DB::select(DB::raw("select * from kic_targets s where  s.TargetId in (" . $getData[0]->targetId . ")"));
            if (count($kictarget) > 0) {
                $ji = 0;
                $kictarg = [];
                foreach ($kictarget as $typeVal) {
                    $kictarg = [
                        'id' => $typeVal->TargetId,
                        'name' => $typeVal->TargetEn
                    ];
                    $ji++;
                }
            } else {
                $kictarg = null;
            }

            $getDataObject = DB::table('kic_customerinfo_business')->where('customerId', $customerId)->get();
            if (count($getDataObject) > 0) {
                $i = 0;
                foreach ($getDataObject as $typeVal) {
                    $newArray['object'][$i]['business'] = $typeVal->businessId;
                    $i++;
                }
            } else {
                $newArray['object'][0]['business'] = null;
            }

            $newArray['sector'] = $t24sec;
            $newArray['ekycindustry'] = $kicind;
            $newArray['ekyctargets'] = $kictarg;
            $newArray['ekyccustometstatus'] = $kiccuststatus;
            return response()->json([
                "code" => 200,
                'getData' => $newArray
            ]);
        }
        return response()->json([
            "code" => 201,
            'getData' => []
        ]);
    }

    public function saveUserImage(Request $request)
    {
        if ($request->get('isEmpty') == 'false') {
            $getSign = KicCustomerUserImage::Where('customerId', $request->get('id'))->first();
            if (!$getSign) {
                $getSign = new KicCustomerUserImage();
                $getSign->image = $request->get('userImage');
                $getSign->customerId = $request->get('id');
                $getSign->save();
            } else {
                $req = [];
                $req['image'] = $request->get('userImage');
                $req['id'] = $request->get('id');
                $getSign->update($req);
            }

            if ($request->get('isEmpty') == 'true') {
                //echo 'in';
                $newArray = [];
                $newArray['ekycstatus'] = '4';
                $update = DB::table('kic_customerinfo');
                $update->where('CustomerId', (int)$request->get('id'));
                $update->update($newArray);

                KicCustomerUserImage::where('customerId', (int)$request->get('id'))->delete();
            } else {
                $newArray = [];
                $newArray['ekycstatus'] = '4';
                $update = DB::table('kic_customerinfo');
                $update->where('CustomerId', (int)$request->get('id'));
                $update->update($newArray);
            }

            $messageRedis = [];
            $messageRedis['msg'] = 'userImage Successfully';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));

            return response()->json([
                "code" => 200,
                "msg" => "userImage successfully"
            ]);
        } else {
            return response()->json([
                "code" => 201,
                "msg" => "please_upload_image"
            ]);
        }
    }

    public function saveSignature(Request $request)
    {

        if ($request->get('isEmpty') == false) {
            $getSign = KicCustomerSignature::Where('customerId', $request->get('id'))->first();
            if (!$getSign) {
                $getSign = new KicCustomerSignature();
                $getSign->image = $request->get('signature');
                $getSign->customerId = $request->get('id');
                $getSign->save();
            } else {
                $req = [];
                $req['image'] = $request->get('signature');
                $req['id'] = $request->get('id');
                $getSign->update($req);
            }

            $getImage = KicCustomerUserImage::Where('customerId', $request->get('id'))->first();
            $setStatus = '4';
            if ($getImage) {
                $setStatus = '4';
            }
            if ($request->get('isEmpty') == true) {
                $newArray = [];
                $newArray['ekycstatus'] = '4';
                $update = DB::table('kic_customerinfo');
                $update->where('CustomerId', (int)$request->get('id'));
                $update->update($newArray);

                KicCustomerSignature::where('customerId', (int)$request->get('id'))->delete();
            } else {
                $newArray = [];
                $newArray['ekycstatus'] = $setStatus;
                $update = DB::table('kic_customerinfo');
                $update->where('CustomerId', (int)$request->get('id'));
                $update->update($newArray);
            }

            $messageRedis = [];
            $messageRedis['msg'] = 'signature successfully';
            Redis::publish('loadEkyc', json_encode($messageRedis, true));

            return response()->json([
                "code" => 200,
                "msg" => "signature successfully"
            ]);
        } else {
            return response()->json([
                "code" => 201,
                "msg" => "please add signature"
            ]);
        }
    }

    public function saveDocument(Request $request)
    {
        //var_dump($request->file('courseFile'));

        $id = $request->get('id');
        $files = $request->file('courseFile');
        $getInfo = DB::table('kic_customerinfo')
            ->select('NationalityId', 'CustomerId')
            ->where('CustomerId', $id)->first();
        $lengthFile = ($getInfo->NationalityId == 149) ? 1 : 2;

        if (empty($files)) {
            return response()->json([
                "code" => 201,
                "msg" => "please add document"
            ]);
        } else if (!empty($files) && count($files) < $lengthFile) {
            return response()->json([
                "code" => 201,
                "msg" => "please add atleast 2 document"
            ]);
        } else {
            foreach ($files as $key => $file) {
                if(!$request->get('courseName')[$key]) {
                    return response()->json([
                        "code" => 201,
                        "msg" => "please add file name"
                    ]);
                }
            }
            $input_data = $request->all();
            //var_dump($request->file('courseFile'));
            $validator = Validator::make(
                $input_data,
                [
                    'courseFile.*' => 'required|mimes:JPG,jpg,jpeg,png,doc,dox,pdf|max:2000'
                ],
                [
                    'courseFile.*.required' => 'Please upload an file',
                    'courseFile.*.mimes' => 'Only jpeg,png,doc and pdf files are allowed',
                    'courseFile.*.max' => 'Sorry! Maximum allowed size for an image is 2MB',
                ]
            );

            if ($validator->fails()) {

                //var_dump( response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY));
                //var_dump($validator->getMessageBag()->toArray());
                $errorMsg = [];
                foreach ($validator->getMessageBag()->toArray() as $err) {
                    $errorMsg[$err[0]] = $err[0];
                }
                return response()->json([
                    "code" => 201,
                    "msg" => "please_select_right_file"
                ]);
            } else {

                if (!File::exists(public_path('documents'))) {
                    File::makeDirectory(public_path('documents'), $mode = 0777, true, true);
                }

                $getData = KicCustomerDocument::where('customerId', $id)->get();
                foreach ($getData as $data) {
                    //echo $data->filename;

                    if (File::exists(public_path($data->filename))) {
                        File::delete(public_path($data->filename));
                    }
                }
                KicCustomerDocument::where('customerId', $id)->delete();

                foreach ($files as $key => $file) {
                    $civilIdString = '';
                    $name = $request->get('courseName')[$key];
                    $filename = str_replace(" ", "", $file->getClientOriginalName());
                    $extension = $file->getClientOriginalExtension();
                    $mimetype = $file->getClientMimeType();
                    $size = $file->getSize();

                    $customerDoc  = new KicCustomerDocument();
                    $customerDoc->customerId = $id;
                    $customerDoc->filename = 'documents/' . $id . '-' . $filename;
                    $customerDoc->file = $name;
                    $customerDoc->save();

                    $file->move(public_path('documents'), $id . '-' . $filename);
                }

                $getImage = KicCustomerDocument::Where('customerId', $request->get('id'))->get();
                $setStatus = '4';
                if (count($getImage) > 0) {
                    $setStatus = '0';
                }
                if (count($getImage) == 0) {
                    $newArray = [];
                    $newArray['ekycstatus'] = '4';
                    $update = DB::table('kic_customerinfo');
                    $update->where('CustomerId', (int)$request->get('id'));
                    $update->update($newArray);
                } else {
                    $newArray = [];
                    $newArray['ekycstatus'] = $setStatus;
                    $update = DB::table('kic_customerinfo');
                    $update->where('CustomerId', (int)$request->get('id'));
                    $update->update($newArray);
                }

                $messageRedis = [];
                $messageRedis['msg'] = 'document added Successfully';
                Redis::publish('loadEkyc', json_encode($messageRedis, true));

                return response()->json([
                    "code" => 200,
                    "msg" => "document_added"
                ]);
            }
        }
    }

    public function showSignature($customerId)
    {
        $getSign = KicCustomerSignature::Where('customerId', $customerId)->first();
        $getImage = KicCustomerUserImage::Where('customerId', $customerId)->first();
        //$getInfo = DB::table('kic_customerinfo')->where('CustomerId', $customerId)->get();
        $getInfo = DB::table('kic_customerinfo')
            ->join('kic_titles', 'kic_titles.TitleId', '=', 'kic_customerinfo.TitleId')
            ->select('kic_customerinfo.CustomerId', DB::raw("CONCAT(kic_customerinfo.FullNameEn) AS FullNameEn"), DB::raw("CONCAT(kic_customerinfo.FullNameAr) AS FullNameAr"), DB::raw("CONCAT(kic_customerinfo.FirstNameEn,' ',kic_customerinfo.MiddleNameEn, ' ', kic_customerinfo.FamilyNameEn) AS full_name"), 'kic_customerinfo.CivilId', 'kic_customerinfo.DOB', 'kic_customerinfo.PassportNumber', 'kic_customerinfo.CivilIdExpiry', 'kic_customerinfo.UpdatedOn', 'kic_customerinfo.NationalityId')->where('CustomerId', $customerId)->get();
        if (count($getInfo) > 0) {
            $kicCountry = DB::table('kic_country')
                ->where('kic_country.CountryId', '=', $getInfo[0]->NationalityId)
                ->get();
        }
        if (count($kicCountry) > 0) {
        }
        return response()->json([
            "code" => 200,
            'data' => $getSign,
            "getImage" => $getImage,
            "getInfo" => count($getInfo) > 0 ? $getInfo[0] : null,
            "nationaltiy" => count($kicCountry) > 0 ? $kicCountry[0] : '',
            "msg" => "signature successfully"
        ]);
    }
    public function showDocuments($customerId)
    {
        $getDocument = KicCustomerDocument::Where('customerId', $customerId)->get();
        return response()->json([
            "code" => 200,
            'data' => $getDocument,
            "msg" => "signature successfully"
        ]);
    }

    public function approvRejectDocuments($customerId, $id, $status)
    {
        $getDocument = KicCustomerDocument::Where('customerId', $customerId)->where('id', $id)->first();
        //echo $getDocument['status'];
        $getDocument['status'] = $status;
        $statusMsg = $status == 1 ? 'approved' : 'rejected';
        $getDocument->update();
        return response()->json([
            "code" => 200,
            'data' => $getDocument,
            "msg" => "document_" . $statusMsg
        ]);
    }
    public function checkPaciAuth(Request $request)
    {
        $messageRedis = [];
        $messageRedis['requestId'] = $request->get('requestId');
        $messageRedis['jsonresp'] = $request->get('jsonresp');

        //$redis = Redis::connection();
        //var_dump(($redis));
        //$redis->publish('checkpaciauth', json_encode($messageRedis, true));
        if (Redis::publish('message', json_encode($messageRedis, true))) {
            //Redis::publish('loadEkycMenaul', json_encode($messageRedis, true));
            Log::error("this is published");
        } else {
            Log::error("this is not published");
        }
        return response()->json([
            "code" => 200,
            'success' => true,
            "response" => $request->all()
        ]);
    }
}
