<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrowalletCustomerKyc extends Model
{
    use HasFactory;

    public const ACCOUNT_PURPOSES = [
        'personal'     => 'Personal Use',
        'business'     => 'Business',
        'savings'      => 'Savings',
        'investment'   => 'Investment',
        'travel'       => 'Travel',
        'shopping'     => 'Online Shopping',
        'bill_payment' => 'Bill Payment',
        'other'        => 'Other',
    ];

    public const EMPLOYMENT_STATUSES = [
        'employed'      => 'Employed',
        'self_employed' => 'Self Employed',
        'unemployed'    => 'Unemployed',
        'student'       => 'Student',
        'retired'       => 'Retired',
    ];

    public const OCCUPATIONS = [
        'engineer'       => 'Engineer',
        'doctor'         => 'Doctor',
        'teacher'        => 'Teacher',
        'accountant'     => 'Accountant',
        'business_owner' => 'Business Owner',
        'sales'          => 'Sales / Marketing',
        'freelancer'     => 'Freelancer',
        'student'        => 'Student',
        'other'          => 'Other',
    ];

    protected $appends = ['idImageData','faceImageData'];
    protected $guarded = ['id'];


    protected $casts = [
        'user_id'     => 'integer',
        'name'        => 'string',
        'first_name'  => 'string',
        'last_name'   => 'string',
        'dob'         => 'date',
        'id_type'     => 'string',
        'id_number'   => 'string',
        'email'       => 'string',
        'line1'       => 'string',
        'city'        => 'string',
        'state'       => 'string',
        'postal_code' => 'string',
        'country'     => 'string',
        'phone_code'  => 'string',
        'phone'       => 'string',
        'account_purpose'   => 'string',
        'employment_status' => 'string',
        'occupation'        => 'string',
        'reference'         => 'string',
        'customer_id'       => 'string',
        'kyc_status'        => 'string',
        'completion_link'   => 'string',
        'mode'              => 'string',
        'id_image'    => 'string',
        'face_image'  => 'string',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function getIdImageDataAttribute() {
        $image = $this->attributes['id_image']??$this->id_image??null;
        if($image == null) {
            return files_asset_path('profile-default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("card-kyc-images") . "/" . $image;
        }
    }
    public function getFaceImageDataAttribute() {
        $image = $this->attributes['face_image'] ?? $this->face_image ?? null;
        if($image == null) {
            return files_asset_path('profile-default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("card-kyc-images") . "/" . $image;
        }
    }
}
