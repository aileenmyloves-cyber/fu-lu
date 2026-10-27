<?php

namespace App\Events\User;

use App\Models\Admin\Property;
use App\Models\Chatbox;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatBox;
    public $conversation;
    public $senderImage;
    public $attachments;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Chatbox $chatBox, $conversation)
    {
        $this->chatBox = $chatBox;
        $this->conversation = $conversation;
        $this->senderImage = $conversation->senderImage;
        $this->attachments = $conversation->conversationsAttachments;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ["support.chat.conversation.".$this->chatBox->id];
    }
    public function broadcastAs()
    {
        return 'conversation';
    }
}
