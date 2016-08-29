<?php

namespace QuizSystem\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'name'
    ];

    public function users()
    {
        return $this->belongsToMany('QuizSystem\Models\Entities\User')
    			    ->withTimestamps();
    }
}