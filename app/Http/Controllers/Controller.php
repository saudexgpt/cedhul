<?php

namespace App\Http\Controllers;

use App\Notifications\AuditTrail;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $user;
    protected $role;
    protected $roles = [];
    protected $data = [];

    public function __construct(Request $httpRequest)
    {
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }
    public function render($data = [])
    {
        $this->data = array_merge($this->data, $data);
        return response()->json($this->data, 200);
    }
    public function setRoles()
    {
        $roles = Role::get();
        $this->roles = $roles;
    }
    public function getRoles()
    {
        $this->setRoles();
        return $this->roles;
    }
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get();
        return $permissions;
    }
    public function getSoftwareName()
    {
        return env("APP_NAME");
    }

    public function setUser()
    {
        $this->user  = Auth::user();
    }

    public function getUser()
    {
        $this->setUser();

        return $this->user;
    }

    public function auditTrailEvent($title, $action)
    {

        $user = $this->getUser();
        $users = User::where('role', 'super')->get();
        $notification = new AuditTrail($title, $action);
        // if ($class_teacher_id !== null) {
        //     $class = [ClassTeacher::find($class_teacher_id)];

        //     Notification::send($class, $notification);
        // }
        // broadcast(new AuditTrailEvent($title, $action));
        return Notification::send($users->unique(), $notification);
    }
}
