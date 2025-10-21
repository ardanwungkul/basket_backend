<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'member_id',
        'coach_id',
        'training_schedule_id',
        'date',
        'time',
        'method',
        'status',
        'reason'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function trainingSchedule()
    {
        return $this->belongsTo(TrainingSchedule::class, 'training_schedule_id');
    }
}