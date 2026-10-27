<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicSettings extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sms_api'                     => 'string',
        'sms_config'                  => 'object',
        'mail_config'                 => 'object',
        'push_notification_config'    => 'object',
        'broadcast_config'            => 'object',
        'email_verification'          => 'boolean',
        'email_notification'          => 'boolean',
        'kyc_verification'            => 'boolean',
        'agent_email_verification'    => 'boolean',
        'agent_email_notification'    => 'boolean',
        'agent_kyc_verification'      => 'boolean',
        'merchant_email_verification' => 'boolean',
        'merchant_email_notification' => 'boolean',
        'merchant_kyc_verification'   => 'boolean',
        'sms_verification'            => 'boolean',
        'sms_notification'            => 'boolean',
        'agent_sms_verification'      => 'boolean',
        'agent_sms_notification'      => 'boolean',
        'merchant_sms_verification'   => 'boolean',
        'merchant_sms_notification'   => 'boolean',
        'fiat_precision_value'        => 'integer',
        'crypto_precision_value'      => 'integer',
        'user_pin_verification'       => 'boolean',
        'agent_pin_verification'      => 'boolean',
        'merchant_pin_verification'   => 'boolean',
        'storage_config'              => 'object',
        'frontend_mode'               => 'boolean',
        'frontend_url'                => 'object',
    ];


    public function mailConfig() {

    }
    public function scopeSitename($query, $pageTitle)
    {
        $pageTitle = empty($pageTitle) ? '' : ' - ' . $pageTitle;
        return $this->site_name . $pageTitle;
    }
    public function scopeSitenameAgent($query, $pageTitle)
    {
        $pageTitle = empty($pageTitle) ? '' : ' - ' . $pageTitle;
        return $this->agent_site_name . $pageTitle;
    }
    public function scopeSitenameMerchant($query, $pageTitle)
    {
        $pageTitle = empty($pageTitle) ? '' : ' - ' . $pageTitle;
        return $this->merchant_site_name . $pageTitle;
    }
}