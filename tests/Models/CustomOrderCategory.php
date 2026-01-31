<?php

namespace Openplain\FilamentTreeView\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;

class CustomOrderCategory extends Model
{
    use HasTreeStructure;

    protected $table = 'custom_order_categories';

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Use 'sort_order' instead of 'order'
     */
    public function getOrderKeyName(): string
    {
        return 'sort_order';
    }
}
