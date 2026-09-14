<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = ['account_id', 'name', 'slug', 'published_version_id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function versions()
    {
        return $this->hasMany(FormVersion::class);
    }

    public function publishedVersion()
    {
        return $this->belongsTo(FormVersion::class, 'published_version_id');
    }

    /**
     * The single in-progress draft, if one exists. Editing this never
     * touches the published version or any submission tied to it.
     */
    public function draftVersion()
    {
        return $this->versions()->where('status', 'draft')->latest('version_number')->first();
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
