<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormVersion extends Model
{
    use HasFactory;

    protected $fillable = ['form_id', 'version_number', 'schema', 'status', 'published_at'];

    protected $casts = [
        'schema' => 'array',
        'published_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
