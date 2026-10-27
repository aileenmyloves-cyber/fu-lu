<?php

namespace App\Notifications\User\Trade;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MarketplaceMailReceiver extends Notification
{
    use Queueable;

    public $user;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
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
        $user = $this->user;
        $data = $this->data;
        $date = Carbon::now();
        $datetime = dateFormat('Y-m-d h:i:s A', $date);

        return (new MailMessage)
            ->greeting(__("Hello")." ".$user->fullname." !")
            ->subject("Trade Purchase Information")
            ->line($data->title)
            ->line(__("web_trx_id").": " .$data->trx_id)
            ->line(__("Selling Amount").": " .$data->selling_amount)
            ->line(__("Asking Amount").": " .$data->asking_amount)
            ->line(__("Status").": " .$data->status)
            ->line(__("Date And Time").": " .$datetime)
            ->line(__('Thank you for using our application!'));
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
