<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Staff;
use Validator;
use App\Http\Resources\UserResource;
use App\Mail\ConfirmNewRegistration;

class AuthController extends Controller
{
    protected $username;
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('guest')->except('logout');
        $this->username = $this->findUsername();
    }
    public function findUsername()
    {
        $login = request()->input('username');

        $user = User::where('phone', $login)->first();

        if ($user) {
            $fieldType =  'phone';

            request()->merge([$fieldType => $login]);
        } else {
            $fieldType =  'email';

            request()->merge([$fieldType => $login]);
        }


        return $fieldType;
    }
    public function username()
    {
        return $this->username;
    }
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function register(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'cpassword' => 'required|same:password'
        ]);

        $user = new User([
            'name'  => $request->name,
            'title'  => $request->title,
            'phone'  => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
            'confirm_hash' => hash('sha256', time()),
        ]);

        if ($user->save()) {
            $user->roles()->sync(6); // role id 6 is user
            // send confirmation email to user
            \Mail::to($user)->send(new ConfirmNewRegistration($user));
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'message' => 'Successfully created user!',
                'accessToken' => $token,
            ], 201);
        } else {
            return response()->json(['error' => 'Provide proper details'], 405);
        }
    }
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     */

    public function login(Request $request)
    {
        $credentials = $request->only($this->username(), 'password');
        $request->validate([
            // 'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        // $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }

        $user = $request->user();
        $name = $user->name . ' (' . $user->email . ')';
        $title = "Log in action";
        //log this event
        $description = "$name logged in to the portal";
        // $this->auditTrailEvent($title, $description);

        return $this->generateAuthorizationKey($user);
    }
    private function generateAuthorizationKey($user, $saveToken = true)
    {
        if ($user->is_confirmed === '0') {
            return response()->json(['message' => 'Account Activation Needed'], 403);
        }
        $user_resource = new UserResource($user);
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        if ($saveToken) {

            $user->api_token = $token;
            $user->save();
        }
        // return response()->json([
        //     'user_data' => $user_resource
        // ])->header('Authorization', $token);
        return response()->json(['data' => $user_resource, 'tk' => $token], 200)->header('Authorization', $token);
    }
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user()
    {
        return new UserResource(Auth::user());
        // return response()->json($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    // public function logout(Request $request)
    // {
    //     $request->user()->tokens()->delete();

    //     return response()->json([
    //         'message' => 'Successfully logged out'
    //     ]);
    // }
    public function logout(Request $request)
    {
        // return $request;
        // $this->guard()->logout();

        // $request->session()->invalidate();
        // $request->user()->tokens()->delete();

        // log this event
        // $description = 'logged out of the portal';
        // $this->auditTrailEvent($request, $description);

        $request->user()->currentAccessToken()->delete();
        if (isset($request->school_id) && $request->school_id !== '') {
            $school_id = $request->school_id;
            $admin_role_id = 1;
            //$school = School::find($school_id);

            $staff = Staff::join('role_user', 'role_user.user_id', '=', 'staff.user_id')->where(['staff.school_id' => $school_id, 'role_user.role_id' => $admin_role_id])->first();


            $user = $staff->user;
            return $this->generateAuthorizationKey($user, false);
        }
        if (isset($request->user_id) && $request->user_id !== '') {

            $user = User::find($request->user_id);
            return $this->generateAuthorizationKey($user, false);
            // if (Auth::loginUsingId($user_id)) {
            //     // Authentication passed...
            //     return redirect()->intended('dashboard');
            // }
        }
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function confirmRegistration($hash)
    {


        $confirm_hash = User::where(['confirm_hash' => $hash])->first();
        $message = 'Invalid Confirmation';
        if ($confirm_hash) {        //hash is confirmed and valid
            if ($confirm_hash->email_verified_at === NULL) {
                $confirm_hash->email_verified_at = date('Y-m-d H:i:s', strtotime('now'));
                $confirm_hash->save();
                $message = 'Registration Confirmed Successfully';
            } else {
                $message = 'Registration Already Confirmed';
            }
            //return view('auth.registration_confirmed', compact('message'));

        }

        return $message;
    }
}
