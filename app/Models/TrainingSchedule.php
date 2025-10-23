<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'training_schedule';

    protected $fillable = ['title', 'date'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'training_schedule_id');
    }

    public function members()
    {
        return $this->belongsToMany(Member::class, 'pivot_member_training_schedule', 'training_schedule_id', 'member_id');
    }
}
