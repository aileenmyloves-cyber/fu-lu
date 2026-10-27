<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SetupPage extends Model
{
    use HasFactory;

    protected $casts = [
        'title'   => 'object',
        'details' => 'object'
    ];

    protected $guarded = ['id'];

    public function sections(){
        return $this->hasMany(SetupPageHasSection::class,'setup_page_id')->orderBy('position', 'asc');
    }
}
