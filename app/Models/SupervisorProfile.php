<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupervisorProfile extends Model
{
    /** @use HasFactory<\Database\Factories\SupervisorProfileFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'staff_id',
        'max_students',
        'current_load',
        'specialization',
        'rank'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_supervisor');
    }
    
    public function assignments()
    {
        return $this->hasMany(SupervisionAssignment::class, 'supervisor_profile_id');
    }
}
