<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locked_at',
        'is_active',
        'last_login_at',
        'creator_id',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function supervisorProfile()
    {
        return $this->hasOne(SupervisorProfile::class);
    }

    public function coordinatorProfiles()
    {
        return $this->hasMany(CoordinatorProfile::class);
    }

    public function internalExaminerProfiles()
    {
        return $this->hasMany(InternalExaminerProfile::class);
    }

    public function externalExaminerProfiles()
    {
        return $this->hasMany(ExternalExaminerProfile::class);
    }

    public function getInternalExaminerProfileAttribute()
    {
        return $this->internalExaminerProfiles()->first();
    }

    public function getExternalExaminerProfileAttribute()
    {
        return $this->externalExaminerProfiles()->first();
    }

    /**
     * Get the program and level scopes for a coordinator.
     */
    public function coordinatorScopes()
    {
        return $this->coordinatorProfiles()->where(function ($query) {
            $query->where('active', true);
        })->get(['program_id', 'level_id']);
    }

    /**
     * Check if coordinator has access to a student.
     */
    public function hasCoordinatorAccess($student)
    {
        if ($this->hasAnyRole(['Admin', 'Director'])) {
            return true;
        }

        if (!$this->hasRole('Program Coordinator')) {
            return false;
        }

        return $this->coordinatorProfiles()
            ->where(function ($query) use ($student) {
                $query->where('active', true)
                    ->where('program_id', $student->program_id);
                
                // Optional Level check if coordination is level-specific
                if ($student->level_id) {
                    $query->where(function($q) use ($student) {
                         $q->whereNull('level_id')
                           ->orWhere('level_id', $student->level_id);
                    });
                }
            })
            ->exists();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'creator_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Institutional unread communication telemetry.
     */
    public function getUnreadMessagesCountAttribute()
    {
        return \App\Models\MessageReadState::where('user_id', $this->id)
            ->whereNull('read_at')
            ->count();
    }

    public function firstName()
    {
        return explode(' ', $this->name)[0] ?? $this->name;
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            // Handle relations that don't have DB-level cascade deletes
            $user->auditLogs()->delete();
            $user->messages()->delete();
        });
    }
}
