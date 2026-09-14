<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'api_key'];

    public function forms()
    {
        return $this->hasMany(Form::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
