<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationalResource extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'resources';

    protected $fillable = [
        'title',
        'description',
        'type',
        'url',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'resource_tag', 'resource_id', 'tag_id');
    }
}
