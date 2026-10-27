<?php

namespace App\Notifications\User\Trade;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TradeMail extends Notification
{
    use Queueable;

    public $user;
    public $data;

    // public $trx_id;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
        // $this->trx_id = $trx_id;
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
        // $trx_id = $this->trx_id;
        $trx_id = $this->data->trx_id;

        $date = Carbon::now();
        $datetime = dateFormat('Y-m-d h:i:s A', $date);
        return (new MailMessage)

            ->greeting(__("Hello")." ".$user->fullname." !")
            ->subject($data->title)
            ->line($data->title)
            ->line(__("web_trx_id").": " .$trx_id)
            ->line(__("request Amount").": " .$data->request_amount)
            ->line(__("Fees & Charges").": " .$data->charges)
            ->line(__("Total Payable Amount").": " .$data->payable)
            ->line(__("Exchange Rate").": " .$data->exchange_rate)
            ->line(__("Recipient Received").": " .$data->received_amount)
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
