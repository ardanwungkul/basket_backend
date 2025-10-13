<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    public function file()
    {
        return $this->hasOne(MemberFile::class, 'member_id');
    }
    public function parent()
    {
        return $this->belongsTo(Guardian::class, 'parent_id');
    }
}
