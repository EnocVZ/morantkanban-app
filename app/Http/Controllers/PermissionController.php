<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use App\Helpers\MethodHelper;

class PermissionController extends Controller {

    public function getAllRoleWithPermission($roleId){
        try {
            $permissions = Permission::leftJoin('role_permission as rp', function($join) use ($roleId) {
                $join->on('rp.permission_id', '=', 'permission.id')
                    ->where('rp.role_id', '=', $roleId);
            })
            ->leftJoin('roles as rl', 'rp.role_id', '=', 'rl.id')
            ->select('permission.*','rp.id as rpermission_id', 'rp.role_id', 'rl.name as role_name')
            ->get();
            return MethodHelper::successResponse($permissions);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function savePermissionToRole(){
        $body = Request::all();
        $data = $body['data'];
        try {
            foreach($data as $item){
                $exists = RolePermission::where('role_id', $item['role_id'])
                        ->where('permission_id', $item['permission_id'])
                        ->exists();
                if($item['selected']){
                    if(!$exists){
                        RolePermission::create([
                            'role_id' => $item['role_id'],
                            'permission_id' => $item['permission_id']
                        ]);
                    }
                } else {
                    if($exists){
                        RolePermission::where('role_id', $item['role_id'])
                            ->where('permission_id', $item['permission_id'])
                            ->delete();
                    }
                }
            }
            return MethodHelper::successResponse($data);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        } 
    }
}