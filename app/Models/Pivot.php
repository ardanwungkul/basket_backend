<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Pivot extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pivot';

    protected $fillable = [
        'member_id',
        'training_schedule_id',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
    
    public function trainingSchedule()
    {
        return $this->belongsTo(TrainingSchedule::class, 'training_schedule_id');
    }
}
