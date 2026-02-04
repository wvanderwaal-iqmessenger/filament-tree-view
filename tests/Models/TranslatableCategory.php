<?php

namespace Openplain\FilamentTreeView\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;

/**
 * Translatable test model
 * Note: This model will only have translation support if spatie/laravel-translatable is installed
 */
class TranslatableCategory extends Model
{
    use HasTreeStructure;

    protected $table = 'translatable_categories';

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

    /**
     * Get the translatable attributes.
     * This is used by Spatie Translatable if it's installed.
     *
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        return ['name', 'description'];
    }

    /**
     * Check if an attribute is translatable.
     * This method is used by Spatie Translatable.
     */
    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->getTranslatableAttributes());
    }

    /**
     * Get a translation for a given attribute and locale.
     * This is a mock implementation for testing when Spatie is not installed.
     */
    public function getTranslation(string $key, string $locale, $useFallbackLocale = true): mixed
    {
        // If the attribute isn't translatable, return the original value
        if (! $this->isTranslatableAttribute($key)) {
            return $this->getAttribute($key);
        }

        // Get the translations array
        $translations = json_decode($this->getAttributes()[$key] ?? '{}', true);

        // Return the translation for the requested locale
        return $translations[$locale] ?? ($useFallbackLocale ? ($translations[config('app.locale')] ?? null) : null);
    }
}
