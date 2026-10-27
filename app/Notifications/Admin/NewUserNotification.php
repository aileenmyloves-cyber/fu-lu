<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use App\Constants\GlobalConst;
use App\Models\Admin\BasicSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserNotification extends Notification
{

    public $data;
    public $password;
    public $user_type;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data,$password,$user_type)
    {
        $this->data      = $data;
        $this->password  = $password;
        $this->user_type = $user_type;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $basic_settings     = BasicSettings::first();
        $data               = $this->data;
        $password           = $this->password;
        $user_type           = $this->user_type;

        $loginUrls = [
                        GlobalConst::USER     => '/login',
                        GlobalConst::AGENT    => '/agent/login',
                        GlobalConst::MERCHANT => '/merchant/login',
                    ];


        $login_url = $loginUrls[$user_type] ?? '/login';

        return (new MailMessage)
            ->subject("Your " . $basic_settings->site_name . " Account Has Been Successfully Created")
            ->greeting(__("Dear")." ".$data['firstname'] . ' ' . $data['lastname'] ." ,")
            ->line("We are pleased to inform you that your account has been successfully created on the ". $basic_settings->site_name ." platform.")
            ->line("Account Details:")
            ->line(__("Email").": " . $data['email'])
            ->line(__("Password").": " . $password)
            ->line(__("Login URL").": " . url( $login_url));

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
