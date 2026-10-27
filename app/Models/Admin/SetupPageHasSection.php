<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetupPageHasSection extends Model
{
    use HasFactory;

    protected $guarded      = ['id'];

    protected $appends      = [
        'editData'
    ];

    protected $casts        = [
        'id'                => 'integer',
        'sectup_page_id'    => 'integer',
        'site_section_id'   => 'integer',
        'position'          => 'integer',
        'status'            => 'integer',
    ];

    public function page(){
        return $this->belongsTo(SetupPage::class,'setup_page_id');
    }

    public function section(){
        return $this->belongsTo(SiteSections::class,'site_section_id');
    }

    public function getEditDataAttribute() {
        $data               = [
            'id'            => $this->id,
            'section'       => $this->section->key,
            'position'      => $this->position,
        ];

        return json_encode($data);
    }
}
