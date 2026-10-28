<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function getpermissionGroups(){
        $permission_groups = DB::table('permissions')->select('group_name')
        ->groupBy('group_name')->get();
        return $permission_groups;
    }
    // End Method

    public static function getpermissionByGroupName($group_name){
        $permissions = DB::table('permissions')
                          ->select('name','id')
                          ->where('group_name',$group_name) 
                          ->get();
                          return $permissions;

    }
    // End Method   

    public static function roleHasPermissions($role,$permissions){
        $hasPermission = true;
        foreach($permissions as $key => $permission){
            if(!$role->hasPermissionTo($permission->name)){
                $hasPermission = false;
            }
            return $hasPermission;
        }
 
    }
    // End Method


}
