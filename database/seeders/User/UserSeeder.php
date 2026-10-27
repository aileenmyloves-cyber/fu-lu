<?php

namespace Database\Seeders\User;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $data = [
            [
                'firstname'                 => "Test",
                'lastname'                  => "User",
                'email'                     => "user@appdevs.net",
                'username'                  => "appdevs",
                'mobile_code'               => "880",
                'mobile'                    => "123456789",
                'full_mobile'               => "880123456789",
                'status'                    => true,
                'password'                  => Hash::make("appdevs"),
                'referral_id'               => generate_unique_string('users','referral_id',8,'number'),
                'current_referral_level_id' => 1,
                'address'                   => '{"country":"Bangladesh","city":"Dhaka","zip":"1230","state":"Bangladesh","address":"Dhaka,Bangladesh"}',
                'email_verified'            => true,
                'sms_verified'              => true,
                'kyc_verified'              => true,
                'sudo_customer'             => '{"business":"64b65a1ffc3b6439f7d56cc0","type":"individual","name":"Test User","phoneNumber":"880123456789","emailAddress":"user@appdevs.net","status":"active","individual":{"firstName":"Test","lastName":"User","dob":"2026-06-04T00:00:00.000Z","identity":{"type":"BVN","number":"12334567891"},"documents":{"idFrontUrl":"https:\/\/qrpaypro.test\/storage\/frontend\/user\/card-kyc\/sudo\/6fcdc5f7-1812-42d9-8a32-a536876411c5.webp","idBackUrl":"https:\/\/qrpaypro.test\/storage\/frontend\/user\/card-kyc\/sudo\/bc370f48-9b27-4d35-a3b3-fb737716ab38.webp","addressVerificationUrl":"https:\/\/qrpaypro.test\/storage\/frontend\/user\/card-kyc\/sudo\/c5d0df62-0b94-41ae-a8a1-ac59614741fa.webp"}},"billingAddress":{"line1":"Dhaka,Bangladesh","line2":"","city":"Dhaka","state":"Bangladesh","country":"Bangladesh","postalCode":"1230"},"isDeleted":false,"createdAt":"2026-06-04T05:05:42.064Z","updatedAt":"2026-06-04T05:05:42.064Z","_id":"6a2107a67b98d9ca84fd82f9","__v":0}',
                'sudo_account'              => '{"_id":"64b65cbcfc3b6439f7d573de","business":"64b65a1ffc3b6439f7d56cc0","type":"account","currency":"USD","accountName":"Sudo Settlement Account","accountType":"Current","currentBalance":1061955.5399999986,"availableBalance":1061955.5399999986,"provider":"Sudo","providerReference":"acc_1689672892636","referenceCode":"acc_1689672892636","isDefault":true,"isDeleted":false,"createdAt":"2023-07-18T09:34:52.637Z","updatedAt":"2023-07-18T09:34:52.637Z","__v":0,"charges":[]}',
                'created_at'                => now(),
                'updated_at'                => now(),
            ],
            [
                'firstname'                 => "Test",
                'lastname'                  => "User2",
                'email'                     => "user2@appdevs.net",
                'username'                  => "testusr2",
                'mobile_code'               => "880",
                'mobile'                    => "123456785",
                'full_mobile'               => "880123456785",
                'status'                    => true,
                'password'                  => Hash::make("appdevs"),
                'referral_id'               => generate_unique_string('users','referral_id',8,'number'),
                'current_referral_level_id' => 1,
                'address'                   => '{"country":"Bangladesh","city":"Dhaka","zip":"1230","state":"Bangladesh","address":"Dhaka,Bangladesh"}',
                'email_verified'            => true,
                'sms_verified'              => true,
                'kyc_verified'              => true,
                'sudo_customer'             => '{"business":"64b65a1ffc3b6439f7d56cc0","type":"individual","name":"Test User2","phoneNumber":"880123456785","emailAddress":"user2@appdevs.net","status":"active","individual":{"firstName":"Test","lastName":"User2","dob":"2026-06-04T00:00:00.000Z","identity":{"type":"BVN","number":"12334567891"},"documents":{"idFrontUrl":"https:\/\/qrpaypro.test\/storage\/frontend\/user\/card-kyc\/sudo\/24025ff6-8e6c-4580-81c4-19d47e25e3ca.webp","idBackUrl":"https:\/\/qrpaypro.test\/storage\/frontend\/user\/card-kyc\/sudo\/fa2734f4-5fca-41ff-bc31-0363123ecdc9.webp","addressVerificationUrl":"https:\/\/qrpaypro.test\/storage\/frontend\/user\/card-kyc\/sudo\/4303ca11-eca7-45f9-96a7-88ef35cff846.webp"}},"billingAddress":{"line1":"Dhaka,Bangladesh","line2":"","city":"Dhaka","state":"Bangladesh","country":"Bangladesh","postalCode":"1230"},"isDeleted":false,"createdAt":"2026-06-04T05:49:28.519Z","updatedAt":"2026-06-04T05:49:28.519Z","_id":"6a2111e87b98d9ca84fd8393","__v":0}',
                'sudo_account'              => '{"_id":"64b65cbcfc3b6439f7d573de","business":"64b65a1ffc3b6439f7d56cc0","type":"account","currency":"USD","accountName":"Sudo Settlement Account","accountType":"Current","currentBalance":1061751.5399999986,"availableBalance":1061751.5399999986,"provider":"Sudo","providerReference":"acc_1689672892636","referenceCode":"acc_1689672892636","isDefault":true,"isDeleted":false,"createdAt":"2023-07-18T09:34:52.637Z","updatedAt":"2023-07-18T09:34:52.637Z","__v":0,"charges":[]}',
                'created_at'                => now(),
                'updated_at'                => now(),
            ]

        ];

        User::insert($data);
    }
}
