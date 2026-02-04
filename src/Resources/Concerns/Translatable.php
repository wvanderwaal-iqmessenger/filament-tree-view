<?php

namespace Openplain\FilamentTreeView\Resources\Concerns;

trait Translatable
{
    /**
     * Get the locales available for translation.
     *
     * @return array<string>
     */
    public static function getTranslatableLocales(): array
    {
        return config('app.locales', [config('app.locale')]);
    }
}
