<?php

namespace App\Notifications\User\Investment;

use App\Constants\GlobalConst;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentProfitNotification extends Notification
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
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

        $basic_settings = BasicSettingsProvider::get();

        $message = 'Congratulation! You have received your investment profit of ' . $this->data['investment']->investPlan->name . ' Plan ('. $this->data['profit_wallet']->currency->symbol . get_amount($this->data['total_profit'],null) .')';

        if($this->data['action_type'] == GlobalConst::INVESTMENT) {
            $message = 'Congratulation! Your investment plan duration is complete. You have received your investment capital amount ('. $this->data['profit_wallet']->currency->symbol . get_amount($this->data['total_profit'],null) .')';
        }

        return (new MailMessage)
                    ->subject("You Received Your Investment Profit - " . $basic_settings->site_name)
                    ->line($message)
                    ->action('Details', route('user.invest.plan.log'))
                    ->line('Thank you for using our application!');
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
