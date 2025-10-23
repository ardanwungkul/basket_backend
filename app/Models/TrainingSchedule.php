<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TrainingSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'training_schedule';
    protected $fillable = ['title', 'date'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'training_schedule_id');
    }

    public function pivots()
    {
        return $this->hasMany(Pivot::class, 'training_schedule_id');
    }
}
