<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecipeDirection extends Model
{
    protected $fillable = [
    	'description'
    ];

    public static function form()
    {
    	return [
    		'description' => ''
    	];
    }
}
