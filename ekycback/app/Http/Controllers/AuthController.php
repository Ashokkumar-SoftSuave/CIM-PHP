<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Mail\ContactUsEmail;
use App\Models\KicImportDepartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'sendMail', 'generateTemplate']]);
    }

    /**
     * Register new user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:4|confirmed',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validate->errors()
            ], 422);
        }
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 'Active';
        $user->save();
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $data = $this->checkNTuser($request->email, $request->password);
        if ($data->getData()->code == 200) {
            $request['email'] = $data->getData()->data->mail;
            $request['password'] = $request->password;
            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return $this->respondWithToken($token, $request->email);
        } else {
            return response()->json(
                [
                    "code" => 401,
                    "message" => 'not_found'
                ],
                401
            );
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh(), auth()->user()->email);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $email)
    {
        $user = User::select('menuroles as roles')->where('email', '=', $email)->first();

        // return response()->json([
        //     'access_token' => $token,
        //     'token_type' => 'bearer',
        //     'expires_in' => auth()->factory()->getTTL() * 60,
        //     'roles' => $user->roles,
        // ]);

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
                "token" => $token,
                'expires_in' => auth()->factory()->getTTL() * 60,
                "roles" => $user->roles,
                'translations' => array_merge($trans1, $trans2)
            ],
            200,
            [
                'Access-Control-Expose-Headers' => 'Authorization',
                'Authorization' => "Bearer " . $token
            ]
        );
    }

    public function checkNTuser(
        $username,
        $password
    ) {
        $DomainName = env('LDAP_DOMAIN', "kic.com.kw");
        $ldap_server = env('LDAP_HOST', "ldap://10.10.40.14:389");

        $auth_user = $username . "@" . $DomainName;

        if ($connect = ldap_connect($ldap_server)) {
            $attributes = ['sn'];
            ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connect, LDAP_OPT_NETWORK_TIMEOUT, 10);

            if ($bind = @ldap_bind($connect, $auth_user, $password)) {

                $filter = "(sAMAccountName=$username)";
                //$attributes = ['givenname', 'cn', 'title', 'description', 'mail', 'department'];
                $attributes = ['givenname', 'cn', 'title', 'description', 'departmentNumber', 'mail', 'department', 'physicaldeliveryofficename'];
                //$attributes = [];
                $result = @ldap_search($connect, "dc=kic,dc=com,dc=kw", $filter, $attributes);
                $infoArray = [];
                if (FALSE !== $result) {



                    $info = @ldap_get_entries($connect, $result);
                    foreach ($info as $information => $infokey) {
                        if (is_array($infokey)) {
                            foreach ($infokey as $ikey => $ival) {
                                if (is_array($ival)) {
                                    $infoArray[$ikey] = $ival[0];
                                }
                            }
                        }
                    }

                    $assignRole  = DB::table("kic_role_assign")
                        ->join('roles', 'kic_role_assign.roleId', '=', 'roles.id')
                        ->select('kic_role_assign.*', 'roles.name')
                        ->where('department', '=', (($infoArray['department']) ? $infoArray['department'] : 'Top Department'))
                        ->where(function ($query) use ($infoArray) {
                            $query->where('position', '=', $infoArray['title'])
                                ->orWhere('position', '=', $infoArray['description']);
                        })
                        //->where('position', '=', $infoArray['title'])
                        //->whereOR('positions', '=', $infoArray['description'])
                        ->where('roles.status', 1)
                        ->get();

                    if (count($assignRole) == 0) {
                        return response()->json([
                            "code" => 401,
                            "msg" => "data not found"
                        ]);
                    }
                    if (count($assignRole) > 0) {
                        $roleId = $assignRole[0]->roleId;
                    } else {
                        $roleId = 3;
                    }

                    //$depart = KicImportDepartment::where('T24Code', $infoArray['departmentnumber'])->get();

                    //$getSector = DB::select(DB::raw("SELECT * FROM `role_work_on_behalf_sectors` where role_id='".$roleId."'"));

                    $work_on_behalf = DB::select(DB::raw("SELECT sector_id, department_id, business_id, isWhat FROM role_work_on_behalf_sectors where role_id= $roleId"));

                    $workvalues = [];
                    foreach ($work_on_behalf as $behalf) {
                        if ($behalf->isWhat == '2') {
                            $workvalues['sector_id'] = $behalf->sector_id;
                            $workvalues['department_id'] = $behalf->department_id;
                            $workvalues['business_id'] = $behalf->business_id;
                            $workvalues['isWhat'] = $behalf->isWhat;
                        } else if ($behalf->isWhat == '1') {
                            if (!in_array($behalf->department_id, $workvalues)) {
                                $workvalues['sector_id'] = $behalf->sector_id;
                                $workvalues['department_id'] = $behalf->department_id;
                                $workvalues['business_id'] = 0;
                                $workvalues['isWhat'] = $behalf->isWhat;
                            }
                        } else if ($behalf->isWhat == '0') {
                            if (!in_array($behalf->sector_id, $workvalues)) {
                                $workvalues['sector_id'] = $behalf->sector_id;
                                $workvalues['department_id'] = $behalf->department_id;
                                $workvalues['business_id'] = $behalf->business_id;
                                $workvalues['isWhat'] = $behalf->isWhat;
                            }
                        }

                        //$getobject = \DB::select(\DB::raw("SELECT * FROM object_model where id= $getapps->object_id"));

                    }

                    $userCheck = User::where('email', $infoArray['mail'])->get();
                    if (count($userCheck) > 0) {
                        $user = User::find($userCheck[0]->id);
                        $user->name       = $infoArray['cn'];
                        $user->email      = $infoArray['mail'];
                        $user->password    = Hash::make($password);
                        $user->menuroles = $assignRole[0]->name; //'user,admin';
                        $user->currentRole = $assignRole[0]->name;
                        $user->sector = count($workvalues) > 0 ? $workvalues['sector_id'] : '0';
                        $user->department = count($workvalues) > 0 ? $workvalues['department_id'] : '0';
                        $user->business = count($workvalues) > 0 ? $workvalues['business_id'] : '0';
                        $user->isWhat = count($workvalues) > 0 ? $workvalues['isWhat'] : '0';
                        $user->save();
                        $user->assignRole([$roleId]);
                    } else {
                        $user = User::create([
                            'name' => $infoArray['cn'],
                            'email' => $infoArray['mail'],
                            'password' => Hash::make($password),
                            'status' => 'Active',
                            'menuroles' => $assignRole[0]->name, //'user,admin',
                            'currentRole' => $assignRole[0]->name, //'admin'
                            'sector' => count($workvalues) > 0 ? $workvalues['sector_id'] : '0',
                            'department' => count($workvalues) > 0 ? $workvalues['department_id'] : '0',
                            'business' => count($workvalues) > 0 ? $workvalues['business_id'] : '0',
                            'isWhat' => count($workvalues) > 0 ? $workvalues['isWhat'] : '0'
                        ]);
                        $user->assignRole([$roleId]);
                    }
                    return response()->json(
                        [
                            "code" => 200,
                            "data" => $infoArray
                        ],
                        200
                    );

                    @ldap_close($connect);
                } else {
                    return response()->json(
                        [
                            "code" => 201,
                            "message" => 'ldap_search_failed'
                        ],
                        401
                    );
                }
                //return $infoArray;
            } else { //if bound to ldap
                return response()->json(
                    [
                        "code" => 201,
                        "message" => 'ldap_connection_failed'
                    ],
                    401
                );
            }
        } //if connected to ldap
        #echo "failed <BR>";
        //@ldap_close($connect);
        //return (false);
        return response()->json(
            [
                "code" => 201,
                "message" => 'ldap_connection_failed'
            ],
            401
        );
    }

    public function sendMail(Request $request)
    {
        # code...

        $CONTENT = $request->all();
        $sendMail = Mail::to('cs@kictrade.com')->send(new ContactUsEmail($CONTENT));
        if (count(Mail::failures()) > 0) {
            return response()->json([
                "code" => 400,
                "msg" => 'Not Send'
            ]);
        } else {
            return response()->json([
                "code" => 200,
                "msg" => 'Mail Send'
            ]);
        }
    }

    public function generateTemplate(Request $request, $id)
    {
        if ($id != 'null') {
            $user = [];
            $user['email'] = env('LDAPUSER', "xxxx");
            $user['password'] = env('LDAPPASSWORD', "xxxx");
            $data = $this->fetchLdapUsers($user['email'], $user['password'], $id);
            $infoArray = [];
            $imageArray = [];
            $primaryActions = [];

            $setFullName = '';
            $getCard = [];
            if (!empty($data)) {
                unset($data['description']);
                unset($data['givenname']);
                unset($data['useraccountcontrol']);
                $data['lname'] = '';
                foreach ($data as $key => $val) {
                    if ($key == 'cn') {
                        $setFullName = $val;
                        ($data['fname'] = $data[$key]);
                        $getCard['fname'] = $val;
                        unset($data[$key]);
                    }
                    if ($key == 'department') {
                        ($data['biz'] = $data[$key]);
                        $getCard['biz'] = $val;
                        unset($data[$key]);
                    }

                    if ($key == 'mail') {
                        $primaryActions['primaryActions'][] = [
                            'name' => 'Email',
                            'icon' => 'email',
                            'href' => 'mailto:',
                            'placeholder' => 'info@example.com',
                            'value' => $val,
                            'label' => 'Email address',
                            'order' => 4,
                        ];
                    }
                    if ($key == 'telephonenumber') {
                        $primaryActions['primaryActions'][] = [
                            'name' => 'Office',
                            'icon' => 'call',
                            'href' => 'tel:',
                            'placeholder' => 'Office number',
                            'value' => $val,
                            'label' => 'Office number',
                            'order' => 4,
                        ];
                    }
                }
                unset($data['department']);
                unset($data['cn']);
                unset($data['username'], $data['telephonenumber'], $data['mail']);

                foreach ($data as $key => $val) {
                    $getCard[$key] = $val;
                }

                $imageType = ['logo', 'cover', 'photo'];

                foreach ($imageType as $type) {
                    $getCardImages = DB::select(DB::raw("SELECT `url`, `blobimg` as `blob`, `ext`, `mime`, `resized` FROM business_card_Images where  imageOf='" . $type . "' and isActive='1'"));

                    $object = [
                        "url" => null,
                        "blob" => null,
                        "ext" => null,
                        "mime" => null,
                        "resized" => null
                    ];
                    if ($getCardImages) {
                        foreach ($getCardImages as $images) {
                            $imageArray[$type] = $images;
                        }
                    } else {
                        $imageArray[$type] = $object;
                    }
                }

                $infoType = [0 => 'primaryActions', 1 => 'secondaryActions'];
                foreach ($infoType as $infoKey => $infoVal) {
                    $getCardInfo = DB::select(DB::raw("SELECT `name`, `icon`, `href`, `placeholder`, `value`, `label`, `color`, `light`, `gradientIcon`, `orders`, `isURL` FROM business_card_info where isFor='" . $infoKey . "' and isActive='1'"));
                    if ($getCardInfo) {
                        $i = 0;
                        foreach ($getCardInfo as $infoData) {
                            $infoArray[$infoVal][$i] = $infoData;
                            $i++;
                        }

                        if ($infoVal == 'primaryActions') {
                            $infoArray =  array_merge_recursive($infoArray, array_unique($primaryActions));
                        }
                    } else {
                        $infoArray[$infoVal] = [];
                    }
                }

                $getCardTheme = DB::select(DB::raw("SELECT `logoBg`, `mainBg`, `buttonBg`, `cardBg`, `theme`, `hostedURL` FROM business_card_setup where isActive='1'"));
                foreach ($getCardTheme as $cardTheme) {
                    $theme = (int)$cardTheme->theme;
                    $hostedURL = $cardTheme->hostedURL ? $cardTheme->hostedURL : 'https://localhost';
                    $getFullName = $setFullName;

                    $infoTheme = ['logoBg', 'mainBg', 'buttonBg', 'cardBg'];
                    foreach ($infoTheme as $val) {
                        $colors[$val] = [
                            'color' => $cardTheme->$val,
                            'openPalette' => false
                        ];
                    }
                }

                return response()->json([
                    "code" => 200,
                    "getCard" => $getCard,
                    "actions" => $infoArray,
                    "images" => $imageArray,
                    "theme" => $theme,
                    "hostedURL" => $hostedURL,
                    "getFullName" => $getFullName,
                    "colors" => $colors
                ]);
            }
            return response()->json([
                "code" => 400,
                "msg" => 'Not Found'
            ]);
        } else {

            $user = [];
            $user['email'] = env('LDAPUSER', "xxxx");
            $user['password'] = env('LDAPPASSWORD', "xxxx");

            $data = $this->fetchLdapUsers($user['email'], $user['password'], $fetchUser = null);
            return response()->json(
                [
                    "code" => 200,
                    "getCard" => $data
                ],
            );
            $getCard = DB::select(DB::raw("SELECT * FROM business_card"));
            return response()->json([
                "code" => 200,
                "getCard" => $getCard
            ]);
        }
        return response()->json([
            "code" => 400,
            "msg" => 'Not Send'
        ]);
    }

    public function fetchLdapUsers(
        $username,
        $password,
        $fetchUser
    ) {
        $DomainName = env('LDAP_DOMAIN', "kic.com.kw");
        $ldap_server = env('LDAP_HOST', "ldap://10.10.40.14:389");

        $auth_user = $username . "@" . $DomainName;

        if ($connect = ldap_connect($ldap_server)) {
            $attributes = ['sn'];
            ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connect, LDAP_OPT_NETWORK_TIMEOUT, 10);

            if ($bind = @ldap_bind($connect, $auth_user, $password)) {

                if ($fetchUser != NULL) {
                    $allUser = null;
                    $filter = "(&(objectClass=User)(objectCategory=Person)(sAMAccountName=$fetchUser))";
                } else {
                    $allUser = [];
                    $filter = "(&(objectClass=User)(objectCategory=Person))";
                }

                $attributes = ['givenname', 'cn', 'title', 'description', 'departmentNumber', 'mail', 'department', 'physicaldeliveryofficename', 'extensionattribute1', 'telephonenumber', 'useraccountcontrol'];
                //$attributes = [];
                $result = @ldap_search($connect, "dc=kic,dc=com,dc=kw", $filter, $attributes);
                $infoArray = [];
                if (FALSE !== $result) {

                    $info = @ldap_get_entries($connect, $result);
                    foreach ($info as $information => $infokey) {
                        if (is_array($infokey)) {
                            $check = (array_key_exists('extensionattribute1', $infokey) ? 1 : 0);
                            if ($check == 0) {
                                $addIn = false;
                                foreach ($infokey as $ikey => $ival) {
                                    if (is_array($ival)) {
                                        if ($ikey == 'mail') {
                                            $infoArray['username'] = explode('@', $ival[0])[0];
                                        }
                                        if ($ikey == 'useraccountcontrol' && $ival[0] == '512') {
                                            $addIn = true;
                                        }
                                        $infoArray[$ikey] = $ival[0];
                                    }
                                }
                                if ($addIn) {
                                    if ($fetchUser == NULL) {
                                        $allUser[] = $infoArray;
                                    } else {
                                        $allUser = $infoArray;
                                    }
                                }
                            }
                        }
                    }
                    return $allUser;
                    @ldap_close($connect);
                } else {
                    return response()->json(
                        [
                            "code" => 201,
                            "message" => 'ldap_search_failed'
                        ],
                        401
                    );
                }
                //return $infoArray;
            } else { //if bound to ldap
                return response()->json(
                    [
                        "code" => 201,
                        "message" => 'ldap_connection_failed'
                    ],
                    401
                );
            }
        } //if connected to ldap
        #echo "failed <BR>";
        //@ldap_close($connect);
        //return (false);
        return response()->json(
            [
                "code" => 201,
                "message" => 'ldap_connection_failed'
            ],
            401
        );
    }
}
