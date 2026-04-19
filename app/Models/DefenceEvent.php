<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DefenceEvent extends Model
{
    /** @use HasFactory<\Database\Factories\DefenceEventFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'thesis_project_id',
        'type',
        'schedule_start',
        'schedule_end',
        'location',
        'outcome',
        'signed_outcome_form_url'
    ];

    protected $casts = [
        'schedule_start' => 'datetime',
        'schedule_end' => 'datetime',
    ];

    public function thesis()
    {
        return $this->belongsTo(ThesisProject::class, 'thesis_project_id');
    }

    public function panelMembers()
    {
        return $this->hasMany(PanelMember::class);
    }
    
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}
