<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(EducationalResource::class, 'resource_tag', 'tag_id', 'resource_id');
    }
}
