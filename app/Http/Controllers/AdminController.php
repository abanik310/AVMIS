<?php

namespace App\Http\Controllers;

use App\Helper\ExportDataToExcel;
use App\Events\ActionUserEvent;
use App\Http\Requests;
use App\models\User;
use App\models\Users;
use App\models\UserLog;
use App\models\UserOtp;
use App\models\UserPermission;
use App\models\UserProfile;
use App\models\UserType;
use App\modules\recruitment\Models\JobCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Form;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Config;
use App\Http\Repositories\data\DataInterface;
use Maatwebsite\Excel\Facades\Excel;
use App\Helper\SMSTrait;
use App\Models\CustomQuery;

class AdminController extends Controller
{
    private $dataRepo;

    public function __construct(DataInterface $dataRepo)
    {
        $this->dataRepo = $dataRepo;
    }

    /*Start Login*/
    

    

    function adminRegistrationView()
    {
        return View::make('admin_registration');
        
    }

    function adminRegistration()
    {
        $mobile = Input::get('mobile');
        //$email = Input::get('email');
        $username = Input::get('username');
        $password = Input::get('password');
        $type = Input::get('user_type');

        User::insert([
            'mobile' => $mobile,
            //'email' => $email,
            'username' => $username,
            'password' => Hash::make($password),
            'type' => $type,
            'created_by' => Auth::user()->id,
        ]);

        return View::make('admin_registration')->with('success_message', 'User Created Successfully!');
        
    }

    function entryListView()
    {
        return View::make('partials.entry_list_content');
    }

    function importListView()
    {
        return View::make('import_list');
    }


    function logout(Request $request)
    {
        $request->session()->forget(['user_id', 'password']);
        $request->session()->flush();
        Auth::logout();
        return redirect()->intended('/login');

    }

    public function getUserData()
    {
        $user = Auth::user();
        if (!Auth::check()) return [];
        $v = Cache::remember('user_data_' . $user->id, 10, function () use ($user) {


               $v = User::with(['usertype', 'userPermission'])->find($user->id);
               //print_r($v); exit;
            return $v;
        });
        return $v;
    }  

    function getTemplate()
    {
        return View::make('template_list');
    }

    public function DivisionName(Request $request)
    {
        return Response::json($this->dataRepo->getDivisions($request->id,$request->oz));
    }

    public function DistrictName(Request $request)
    {
        return Response::json($this->dataRepo->getUnits(is_array($request->id)||!$request->id?$request->id:explode(",",$request->id), $request->unit_id));
    }

    public function ThanaName(Request $request)
    {
        return Response::json($this->dataRepo->getThanas($request->division_id, $request->id));
    }

    public function UnionName(Request $request)
    {
        return Response::json($this->dataRepo->getUnions($request->division_id, $request->unit_id,$request->thana_id,$request->id));
    }

    function userManagement()
    {
        $users = User::count();
        return View::make('User.user_list')->with($users);
    }

    function ansarVdpInfo()
    {
        return response()->json(CustomQuery::getAllInformation(Input::get('range'),Input::get('unit'),Input::get('thana'),Input::get('entry_unit'),Input::get('limit'), Input::get('offset'), Input::get('geo_id')));
    }

    function kpiInfo()
    {
        return View::make('kpi.kpi_info');
    }

    function CreateKpi()
    {
        return View::make('kpi.create_kpi');
    }


} 


