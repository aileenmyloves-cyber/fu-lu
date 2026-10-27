<?php

namespace App\Models;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $appends = ['senderImage'];

    protected $casts = [
        'id'            => 'integer',
        'chatbox_id'   => 'integer',
        'sender'        => 'integer',
        'sender_type'   => 'string',
        'receiver'      => 'integer',
        'receiver_type' => 'string',
        'message'       => 'string',
        'seen'          => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function conversationsAttachments()
    {
        return $this->hasMany(ConversationAttachments::class,"chatbox_id");
    }


    public function getSenderImageAttribute() {

        if($this->sender_type == "ADMIN") {
            $admin = Admin::find($this->sender);
            if($admin) {
                return get_image($admin->image,"admin-profile");
            }else {
                return files_asset_path("default");
            }
        }else if($this->sender_type == "USER"){
            return $this->user->userImage??'';
        }
        return files_asset_path("default");
    }


    public function user() {
        return $this->belongsTo(User::class,"sender");
    }

}
