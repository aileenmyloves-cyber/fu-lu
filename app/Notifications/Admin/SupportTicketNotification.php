<?php

namespace App\Notifications\Admin;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\UserSupportTicket;
use App\Models\Admin\BasicSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SupportTicketNotification extends Notification
{

    public $user;
    public $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($user,$data)
    {
        $this->user         = $user;
        $this->data         = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $basic_settings     = BasicSettings::first();
        $data               = $this->data;
        $user               = $this->user;
        $support_ticket     = UserSupportTicket::where('id',$data)->first();

        $created_date       = Carbon::parse($support_ticket->created_at)->format('F d, Y');

        return (new MailMessage)
            ->subject("Support Ticket Created: " . '#' . $support_ticket->token)
            ->line('Hi ' . $support_ticket->creator->fullname . ",")
            ->line('We wanted to let you know that a support ticket has been created on your behalf by our support team.')
            ->line('Here are the details:')
            ->line('Ticket ID: ' .'#' . $support_ticket->token)
            ->line('Subject: ' . $support_ticket->subject)
            ->line('Status: ' . $support_ticket->stringStatus->value)
            ->line('Created On: ' . $created_date)
            ->line('Created By: ' . $support_ticket->admin->fullname)
            ->line('Description:')
            ->line($support_ticket->desc)
            ->line('Our support team is reviewing the ticket and will contact you with updates or additional questions. You can respond to this email or view the ticket online at any time.')
            ->line('View your ticket here: ' . route('user.support.ticket.conversation',encrypt($support_ticket->id)))
            ->line('Thank you,')
            ->line($basic_settings->site_name .' Support Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
