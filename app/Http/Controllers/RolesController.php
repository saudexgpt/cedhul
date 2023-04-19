<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CurriculumLevelGroup;

class RolesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Role::where('name', '!=', 'client')->with('permissions')->get();
        return response()->json(compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $name = strtolower(str_replace(' ', '-', $request->name));
        $role = Role::where('name', $name)->first();
        if (!$role) {
            $role = new Role();
            $role->name = $name;
            $role->display_name = ucwords(str_replace('-', ' ', $name));
            $role->description = $request->description;
            $role->save();
        }
        return $this->index($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $name = strtolower(str_replace(' ', '-', $request->name));
        $role->name = $name;
        $role->display_name = ucwords(str_replace('-', ' ', $name));
        $role->description = $request->description;
        $role->save();
        return $this->index($request);
    }

    public function assignRoles(Request $request)
    {
        $user = User::find($request->user_id);
        // $user->syncRoles($request->role_id);
        $user->roles()->sync($request->role_id);
        $user->flushCache();
        return 'success';
    }
    // public function removeAssignedRole(Request $request)
    // {
    //     $user = User::find($request->user_id);
    //     $user->detachRole($request->roles);
    // }
}
