<?php

namespace App\Http\Controllers;

use App\Helper\ExportDataToExcel;
use App\Events\ActionUserEvent;
use App\Http\Requests;
use App\models\User;
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

class UserController extends Controller
{
    function login()
    {
        return View::make('login');
    }
    public function handleLogin(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {

            $user = Auth::user();
            Auth::login($user);

            return redirect()->intended('/admin_dashboard');

        } else {
            return redirect()->back()->with('error', 'Invalid user ID or password');
        }
    }
    /*End Login*/

    function adminDashboard()
    {
        return View::make('admin_dashboard');
        
    }

    function userManagement()
    {
        $users = User::count();
        return View::make('User.user_list')->with('total_user', $users);
    }

    function getAllUser()
    {
        return response()->json(CustomQuery::getUserInformation(Input::get('limit'), Input::get('offset'), Input::get('user_name')));
    }

    function editUserPermission($id)
    {
        //echo storage_path("user/permission/test_list.json");exit;
        //die;
		
		//		return view('User.user_not_authorized');
        // exit;
        
        $read_permission_file = file_get_contents(storage_path("user/permission/test_list.json"));
        $routes = json_decode($read_permission_file);
        $user = User::find($id);
        //echo $user->userPermission->permission_type;exit;
        if ($user->userPermission->permission_type == 0) {
            if (is_null($user->userPermission->permission_list)) {
                //echo "null";exit;
                $permission = null;
            } else {
                //echo "not null";exit;
                $permission = json_decode($user->userPermission->permission_list);
                //echo "<pre>"; print_r($permission);exit;
            }
        } else {
            $permission = 'all';    
        }
        //return Res;
        return View::make('User.user_permission_view')->with(array('routes' => collect($routes), 'id' => $id, 'access' => $permission, 'user' => User::find($id)));
        //return View::make('User.user_permission_view');

    }
    function updatePermission($id)
    {
        $user = User::find($id);
        $all = Input::get('permit_all');

        $permission = count(Input::get('permission')) == 0 ? null : json_encode(Input::get('permission'));
        $user->userPermission->permission_type = 0;
        $user->userPermission->permission_list = $permission;
        $user->userPermission->save();
        //CustomQuery::addActionlog(['ansar_id' => $id, 'action_type' => 'EDIT USER PERMISSION', 'from_state' => '', 'to_state' => '', 'action_by' => auth()->user()->id]);
        return Redirect::action('App\Http\Controllers\UserController@userManagement')->with('success_message', $user->username . " permission has been updated successfully");
        //return View::make('User.user_permission_view');
    }

} 


