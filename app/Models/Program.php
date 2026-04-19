<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'code', 'degree_type'];

    public function cohorts()
    {
        return $this->hasMany(Cohort::class);
    }
    
    public function coordinatorProfiles()
    {
        return $this->hasMany(CoordinatorProfile::class);
    }

    public function students()
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function supervisors()
    {
        return $this->hasMany(SupervisorProfile::class);
    }
}
