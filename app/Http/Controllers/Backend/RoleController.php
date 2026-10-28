<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoleController extends Controller
{
    public function AllPermission(){
        $permissions = Permission::all();
        return view("admin.backend.pages.permission.all_permission", compact("permissions"));
    }
    // End Method

    public function AddPermission(){
        return view("admin.backend.pages.permission.add_permission");
    }
    // End Method

    public function StorePermission(Request $request){

        Permission::create([
            'name'=> $request->name,
            'group_name'=> $request->group_name,
        ]);

        $notification = array(
            'message'=> 'Permission Inserted Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.permission')->with($notification);
    }
    // End Method    

    public function EditPermission(string $id){
        $permissions = Permission::find($id);
        return view("admin.backend.pages.permission.edit_permission", compact('permissions'));
    }
    // End Method

    public function UpdatePermission(Request $request){
        $per_id = $request->per_id;

        // 1. Find the permission or fail gracefully if it doesn't exist
        $permission = Permission::find($per_id);

        if (!$permission) {
            $notification = array(
                'message'=> 'Permission not found!',
                'alert-type'=> 'error',
            );
            return redirect()->back()->with($notification);
        }

        // 2. Update the record
        $permission->update([
            'name'=> $request->name,
            'group_name'=> $request->group_name,
        ]);

        $notification = array(
            'message'=> 'Permission Updated Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.permission')->with($notification);
    }
    // End Method

    public function DeletePermission(string $id){

        $permission = Permission::findOrFail($id);

        $permission->delete();

        $notification = array(
            'message' => 'Permission Deleted Successfully',
            'alert-type' => 'success',
        );
        return redirect()->route('all.permission')->with($notification);
    }
    // End Method

    public function AllRoles(){
        $roles = Role::all();
        return view("admin.backend.pages.role.all_role", compact("roles"));
    }
    // End Method

    public function AddRoles(){
        return view('admin.backend.pages.role.add_role');
    }
    // End Method

    public function StoreRoles(Request $request){

        Role::create([
            'name'=> $request->name,
        ]);

        $notification = array(
            'message'=> 'Role Inserted Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.roles')->with($notification);
    }
    // End Method

    public function EditRoles(string $id){
        $roles = Role::find($id);
        return view('admin.backend.pages.role.edit_role', compact('roles'));
    }
    // End Method

    public function UpdateRoles(Request $request){
        $role_id = $request->id;

        Role::find($role_id)->update([
            'name'=> $request->name,
        ]);
        
        $notification = array (
            'message'=> 'Role Updated Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.roles')->with($notification);
    }
    // End Method

    public function DeleteRoles(string $id){

        $role = Role::findOrFail($id);

        $role->delete();
        
        $notification = array(
            'message' => 'Role Deleted Successfully',
            'alert-type' => 'success',
        );
        return redirect()->route('all.roles')->with($notification);
    }
    // End Method

     public function AddRolesPermission(){
        $roles = Role::all();
        $permissions = Permission::all();
        $permission_groups = User::getpermissionGroups();
        return view("admin.backend.pages.rolesetup.add_roles_permission", compact("roles", "permissions", "permission_groups"));

    }
    // End Method

    public function RolePermissionStore(Request $request){

        $data = array();
        $permissions = $request->permission;

        foreach($permissions as $key => $item){
            $data['role_id'] = $request->role_id;
            $data['permission_id'] = $item;

            DB::table('role_has_permissions')->insert($data);
        }
        // End Foreach
    
        $notification = array(
            'message'=> 'Role Permission Inserted Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.roles')->with($notification);
    }
    // End Method

    public function AllRolesPermission(){
        $roles = Role::all();
        return view("admin.backend.pages.rolesetup.all_roles_permission", compact("roles"));
    }
    // End Method

    public function AdminEditRoles(string $id){
        $role = Role::find($id);
        $permissions = Permission::all();
        $permission_groups = User::getpermissionGroups();
        return view("admin.backend.pages.rolesetup.edit_roles_permission", compact("role", "permissions", "permission_groups"));
    }
    // End Method

    public function AdminRolesUpdate(Request $request, string $id){
        $role = Role::find($id);
        $permissions = $request->permission;

        if (!empty($permissions)) {
            $permissionNames = Permission::whereIn('id', $permissions)
            ->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);
        } else {
            $role->syncPermissions([]);
        }
        
        $notification = array(
            'message'=> 'Role Permission Updated Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.roles.permission')->with($notification);
    }
    // End Method

    public function AdminDeleteRoles(string $id){

        $role = Role::find($id);
        if(!is_null($role)) {
            $role->delete();
        }
        
        $notification = array(
            'message' => 'Role Permission Deleted Successfully',
            'alert-type' => 'success',
        );
        return redirect()->route('all.roles.permission')->with($notification);
    }
    // End Method

    public function AllAdmin(){
        $alladmin = User::where('role','admin')->latest()->get();
        return view("admin.backend.pages.admin.all_admin", compact("alladmin"));
    }
    // End Method

    public function AddAdmin(){
        $roles = Role::all();
        return view('admin.backend.pages.admin.add_admin', compact('roles'));
    }
    // End Method

    public function StoreAdmin(Request $request){

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = 'admin';
        $user->save();

        if ($request->roles) {
            $role = Role::where('id', $request->roles)->where('guard_name', 'web')->first();
            if ($role) {
                $user->assignRole($role->name);
            }
        }

        $notification = array(
            'message'=> 'New Admin Inserted Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.admin')->with($notification);

    }
    // End Method

    public function EditAdmin($id){
        $admin = User::find($id);
        $roles = Role::all();
        return view("admin.backend.pages.admin.edit_admin", compact("admin", "roles"));

    }
    // End Method

    public function UpdateAdmin(Request $request,$id){

        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = 'admin';
        $user->save();

        $user->roles()->detach();

        if ($request->roles) {
            $role = Role::where('id', $request->roles)->where('guard_name', 'web')->first();
            if ($role) {
                $user->assignRole($role->name);
        }

    }

        $notification = array(
            'message'=> 'New Admin Updated Successfully',
            'alert-type'=> 'success',
        );
        return redirect()->route('all.admin')->with($notification);
    }    
    // End Method

}
