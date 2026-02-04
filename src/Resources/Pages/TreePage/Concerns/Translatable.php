<?php

namespace Openplain\FilamentTreeView\Resources\Pages\TreePage\Concerns;

use Filament\Actions;

trait Translatable
{
    /**
     * The active locale for translatable content.
     */
    public ?string $activeLocale = null;

    /**
     * Initialize the active locale on mount.
     */
    public function mountTranslatable(): void
    {
        $locales = $this->getTranslatableLocales();

        if (empty($this->activeLocale) && !empty($locales)) {
            $this->activeLocale = $locales[0];
        }
    }

    /**
     * Get the locales available for translation.
     *
     * @return array<string>
     */
    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    /**
     * Get the active locale for displaying tree content.
     */
    public function getActiveTreeLocale(): ?string
    {
        if (! in_array($this->activeLocale, $this->getTranslatableLocales())) {
            return null;
        }

        return $this->activeLocale;
    }

    /**
     * Get header actions including the locale switcher.
     *
     * @return array<Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        $locales = $this->getTranslatableLocales();

        // Only show locale switcher if there are multiple locales
        if (count($locales) <= 1) {
            return parent::getHeaderActions();
        }

        return array_merge(
            [
                Actions\Action::make('switchLocale')
                    ->label(__('Language'))
                    ->icon('heroicon-o-language')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\Select::make('locale')
                            ->label(__('Select Language'))
                            ->options(collect($locales)->mapWithKeys(fn ($locale) => [
                                $locale => strtoupper($locale),
                            ])->toArray())
                            ->default($this->activeLocale ?? $locales[0])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data) {
                        $this->activeLocale = $data['locale'];
                        // Force a Livewire component refresh to update all tree content
                        $this->dispatch('$refresh');
                    }),
            ],
            parent::getHeaderActions()
        );
    }

    /**
     * Lifecycle hook called when the active locale is updated.
     */
    public function updatedActiveLocale(): void
    {
        // Refresh the tree to show content in the new locale
        $this->dispatch('tree-locale-updated');
    }

    /**
     * Set the active locale programmatically.
     */
    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
        $this->updatedActiveLocale();
    }
}
