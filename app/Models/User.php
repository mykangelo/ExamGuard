<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'preferences', 'avatar_path'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public static function defaultPreferences(): array
    {
        return [
            'emailExamSubmitted'   => true,
            'emailViolations'      => true,
            'defaultWarningLimit'  => 3,
            'defaultTimeLimit'     => 60,
            'readNotificationIds'  => [],
            'emailExamAssigned'    => true,
            'emailClassUpdates'    => true,
            'emailExamReminder'    => true,
            'emailExamResults'     => true,
            'department'           => '',
            'yearLevel'            => '',
            'studentId'            => '',
        ];
    }

    public function preferencesWithDefaults(): array
    {
        return array_merge(static::defaultPreferences(), $this->preferences ?? []);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'professor_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'professor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'student_id');
    }

    public function toAuthArray(): array
    {
        $prefs = $this->preferencesWithDefaults();

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'role'         => $this->role,
            'verified'     => $this->hasVerifiedEmail(),
            'member_since' => $this->created_at?->format('M j, Y'),
            'avatarUrl'    => PublicStorageUrl::for($this->avatar_path),
            'department'   => $prefs['department'] ?? '',
            'yearLevel'    => $prefs['yearLevel'] ?? '',
            'studentId'    => $prefs['studentId'] ?? '',
            'preferences'  => $prefs,
        ];
    }
}
