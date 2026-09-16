<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    // Append-only record: no updated_at column.
    const UPDATED_AT = null;

    protected $fillable = ['account_id', 'form_id', 'form_version_id', 'data', 'ip_hash', 'user_agent', 'created_at'];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function formVersion()
    {
        return $this->belongsTo(FormVersion::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
