<?php

namespace Openplain\FilamentTreeView\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;

class UuidCategory extends Model
{
    use HasTreeStructure;
    use HasUuids;

    protected $table = 'uuid_categories';

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }
}
