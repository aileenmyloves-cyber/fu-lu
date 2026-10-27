<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Admin;



class Chatbox extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $appends = ['senderImage'];

    protected $casts = [
        'id'           => 'integer',
        'sender_id '   => 'integer',
        'receiver_id ' => 'integer',
        'status'       => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class,'receiver_id');
    }

    public function sender() {
        return $this->belongsTo(User::class,'sender_id');
    }


    public function conversations() {
        return $this->hasMany(Conversation::class,"chatbox_id");
    }


    public function getSenderImageAttribute() {

        if ($this->user->id == auth()->id()) {
            $user  = $this->sender->fullname;
            $email = $this->sender->email;
            $data  = [
                'fullname' => $user,
                'email'    => $email,
                'image'    => $this->sender->userImage??'',
            ];

            return (object) $data;
        }else {
            $user  = $this->user->fullname;
            $email = $this->user->email;
            $data  = [
                'fullname' => $user,
                'email'    => $email,
                'image'    => $this->user->userImage??'',
            ];
            return (object) $data;
        }



    }




}
