<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $currentUser = Auth::user();
        $can_edit = false;
        if ($this->id === $currentUser->id || $currentUser->hasRole('super') || $currentUser->hasRole('admin')) {
            $can_edit = true;
        }

        // $roles = array_map(
        //             function ($role) {
        //                 return $role['name'];
        //             },
        //             $this->roles->toArray()
        //         );
        // $permissions = array_map(
        //     function ($permission) {
        //         return $permission['name'];
        //     },
        //     $this->allPermissions()->toArray()
        // );
        // $rights = array_merge($roles, $permissions);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->email,
            'phone' => $this->phone,
            // 'address' => $this->staff->address,
            'notifications' => [],
            // 'activity_logs' => $this->notifications()->orderBy('created_at', 'DESC')->get(),
            'roles' => array_map(
                function ($role) {
                    return $role['name'];
                },
                $this->roles->toArray()
            ),
            // 'role' => 'admin',
            'permissions' => array_map(
                function ($permission) {
                    return $permission['name'];
                },
                $this->allPermissions()->toArray()
            ),
            'avatar' => '/' . $this->photo, //'https://i.pravatar.cc',
            'can_edit' => $can_edit,
        ];
    }
}
