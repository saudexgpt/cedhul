<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\School;
use App\Models\Staff;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentsInClass;
use Auth;

use Illuminate\Http\Request;

class UsersController extends Controller
{


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Role::get();
        $users = User::with('roles')->paginate($request->limit);
        return response()->json(compact('users', 'roles'), 200);
    }
    public function userNotifications()
    {
        $user = $this->getUser();
        // $school = $this->getSchool();
        // $sess_id = $this->getSession()->id;
        $notifications = $user->notifications()->orderBy('created_at', 'DESC')->take(20)->get();
        $unread_notifications = $user->unreadNotifications()->count();
        // if ($notifications->isEmpty()) {
        //     $notifications = $user->notifications()->orderBy('created_at', 'DESC')->take(20)->get();
        //     // $notifications =
        // }
        // $class_teacher_id = '';
        // if ($user->hasRole('student')) {
        //     $student_id = $this->getStudent()->id;
        //     //fetch current class of student
        //     $student_in_class = StudentsInClass::where(['school_id' => $school->id, 'student_id' => $student_id, 'sess_id' => $sess_id])->orderBy('id', 'DESC')->first();
        //     $class_teacher_id = $student_in_class->class_teacher_id;
        // }

        // if ($user->hasRole('staff')) {
        //     $staff_id = $this->getStaff()->id;
        //     $class_teacher = ClassTeacher::where(['school_id' => $school->id, 'teacher_id' => $staff_id]);
        //     //fetch current class of staff
        // }
        return response()->json(compact('notifications', 'unread_notifications'), 200);
    }
    public function markNotificationAsRead()
    {
        $user = $this->getUser();
        $user->unreadNotifications->markAsRead();
        return $this->userNotifications();
    }
    public function changePassword()
    {
        $user = $this->getUser();
        $user->password_status = 'default';
        $user->save();
        return redirect()->route('dashboard');
    }
    public function adminResetUserPassword(Request $request)
    {
        $user = User::find($request->user_id);
        $user->password = 'password';
        $user->password_status = 'default';
        $user->save();
    }
    public function resetPassword(Request $request, User $user)
    {
        $confirm_password = $request->confirm_password;
        $new_password = $request->new_password;

        if ($new_password === $confirm_password) {
            $user->password = $new_password;
            $user->password_status = 'custom';

            if ($user->save()) {
                return response()->json(['message' => 'success'], 200);
            }
        }
        return response()->json([
            'message' => 'Password does not match'
        ], 401);
    }

    public function approveUser(Request $request, User $user)
    {
        $user->is_confirmed = '1';
        $user->save();
        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function performAction(Request $request, User $user)
    {
        $action = $request->action;
        switch ($action) {
            case 'approve':
                $user->is_approved = 1;
                $user->save();
                break;
            case 'suspend':
                $user->is_suspended = 1;
                $user->save();
                break;
            case 'activate':
                $user->is_suspended = 0;
                $user->save();
                break;
            default:
                break;
        }
        return 'success';
    }


    public function show(User $user)
    {
    }

    public function editProfile()
    {
        $state_array = ['' => 'Select State'];
        $states = State::orderBy('name')->get();
        foreach ($states as $state) {
            $state_array[$state->id] = $state->name;
        }
        $user = $this->getUser();
        return $this->render('core::users.edit', compact('user', 'state_array'));
    }

    public function editPhoto(Request $request)
    {

        if (isset($request->user_id) && $request->user_id != '') {
            $user_id = $request->user_id;
            $edit_user = User::find($user_id);
        } else {
            $edit_user = $this->getUser();
        }

        return $this->render('core::users.edit_photo', compact('edit_user'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePhoto(Request $request)
    {
        //
        $school = $this->getSchool();

        $folder_key = ($school) ? "schools/" . $school->folder_key . '/profile_img' : 'photo';
        $user = User::find($request->user_id);
        if ($request->file('photo') != null && $request->file('photo')->isValid()) {
            $mime = $request->file('photo')->getClientMimeType();

            if ($mime == 'image/png' || $mime == 'image/jpeg' || $mime == 'image/jpg' || $mime == 'image/gif') {
                $name = str_replace('@', '_', $user->username);
                $name = str_replace('/', '_', $user->username);
                $name = str_replace('.', '_', $name) . "." . $request->file('photo')->guessClientExtension();
                $photo_name = $user->uploadFile($request, $name, $folder_key);
                $user->photo = $photo_name;
                $user->save();
            }
        }
        return $user->photo;
    }
}
