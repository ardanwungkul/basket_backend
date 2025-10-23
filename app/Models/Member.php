<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function file()
    {
        return $this->hasOne(MemberFile::class, 'member_id');
    }

    public function parent()
    {
        return $this->belongsTo(Guardian::class, 'parent_id');
    }

    public function bill()
    {
        return $this->hasMany(MemberBill::class, 'member_id');
    }

    public function training_schedules()
    {
        return $this->belongsToMany(TrainingSchedule::class, 'pivot_member_training_schedule', 'member_id', 'training_schedule_id');
    }
}
