<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminRolePermission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $with = [
        'role',
        'hasPermissions',
    ];

    public function role() {
        return $this->belongsTo(AdminRole::class,"admin_role_id");
    }

    public function getStringStatusAttribute() {
        $status = [
             true    => __("active"),
            false   => __("Deactivated"),
        ];

        return $status[$this->status];
    }

    public function getEditDataAttribute() {
        $data = [
            'id'        => $this->id,
            'name'      => $this->name,
        ];

        return json_encode($data);
    }

    public function hasPermissions() {
        return $this->hasMany(AdminRoleHasPermission::class,"admin_role_permission_id");
    }
}
