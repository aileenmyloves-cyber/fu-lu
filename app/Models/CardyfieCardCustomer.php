<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardyfieCardCustomer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $appends = ['idFontImage','idBackImage','userImage'];

    protected $casts = [
        'user_type'      => 'string',
        'user_id'        => 'integer',
        'ulid'           => 'string',
        'reference_id'   => 'string',
        'first_name'     => 'string',
        'last_name'      => 'string',
        'email'          => 'string',
        'date_of_birth'  => 'string',
        'id_type'        => 'string',
        'id_number'      => 'string',
        'id_front_image' => 'string',
        'id_back_image'  => 'string',
        'user_image'     => 'string',
        'house_number'   => 'string',
        'address_line_1' => 'string',
        'city'           => 'string',
        'state'          => 'string',
        'zip_code'       => 'string',
        'country'        => 'string',
        'status'         => 'string',
        'meta'           => 'object',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getIdFontImageAttribute() {
        $image = $this->attributes['id_front_image'] ?? $this->id_front_image ?? null;
  
        if($image == null) {
            return files_asset_path('default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("card-kyc-images") . "/" . $image;
        }
    }
    public function getIdBackImageAttribute() {
        $image = $this->attributes['id_back_image'] ?? $this->id_back_image ?? null;
        if($image == null) {
            return files_asset_path('default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("card-kyc-images") . "/" . $image;
        }
    }
    public function getUserImageAttribute() {
        $image = $this->attributes['user_image'] ?? $this->user_image ?? null;
        if($image == null) {
            return files_asset_path('default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("card-kyc-images") . "/" . $image;
        }
    }
}
