<?php

namespace QuizSystem\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
  use SoftDeletes;

  protected $table = 'quizzes';

  protected $fillable = [
		'name',
		'description',
		'timed',
		'no_of_questions',
		'active_status',
		'user_retries'
  ];

  public static function boot() {
    parent::boot();

    static::creating(function($category) {
      $category->slug = str_slug($category->name);

      $latestSlug =
        static::withTrashed()
        	->whereRaw("slug RLIKE '^{$category->slug}(-[0-9]*)?$'")
  				->latest('id')
          ->pluck('slug');

      if($latestSlug) {
        $pieces = explode('-', $latestSlug);
        $number = intval(end($pieces));
        $category->slug .= '-' . ($number + 1);
      }
    });
  }




}
