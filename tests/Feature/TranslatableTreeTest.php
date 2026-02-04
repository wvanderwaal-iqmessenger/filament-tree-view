<?php

use Openplain\FilamentTreeView\Tests\Models\TranslatableCategory;

beforeEach(function () {
    // Create test data with translations stored as JSON
    $this->parent = TranslatableCategory::create([
        'name' => json_encode(['en' => 'Parent Category', 'es' => 'Categoría Padre', 'fr' => 'Catégorie Parent']),
        'description' => json_encode(['en' => 'Parent description', 'es' => 'Descripción del padre', 'fr' => 'Description du parent']),
        'parent_id' => null,
        'order' => 1,
    ]);

    $this->child1 = TranslatableCategory::create([
        'name' => json_encode(['en' => 'Child 1', 'es' => 'Hijo 1', 'fr' => 'Enfant 1']),
        'description' => json_encode(['en' => 'First child', 'es' => 'Primer hijo', 'fr' => 'Premier enfant']),
        'parent_id' => $this->parent->id,
        'order' => 1,
    ]);

    $this->child2 = TranslatableCategory::create([
        'name' => json_encode(['en' => 'Child 2', 'es' => 'Hijo 2', 'fr' => 'Enfant 2']),
        'parent_id' => $this->parent->id,
        'order' => 2,
    ]);
});

it('stores translations as JSON', function () {
    expect($this->parent->name)->toBeJson();

    $translations = json_decode($this->parent->name, true);
    expect($translations)->toBeArray();
    expect($translations)->toHaveKey('en');
    expect($translations['en'])->toBe('Parent Category');
});

it('can get translation for English', function () {
    $translation = $this->parent->getTranslation('name', 'en');
    expect($translation)->toBe('Parent Category');
});

it('can get translation for Spanish', function () {
    $translation = $this->parent->getTranslation('name', 'es');
    expect($translation)->toBe('Categoría Padre');
});

it('can get translation for French', function () {
    $translation = $this->parent->getTranslation('name', 'fr');
    expect($translation)->toBe('Catégorie Parent');
});

it('returns null for missing translation when fallback is disabled', function () {
    $translation = $this->parent->getTranslation('name', 'de', false);
    expect($translation)->toBeNull();
});

it('identifies translatable attributes', function () {
    expect($this->parent->isTranslatableAttribute('name'))->toBeTrue();
    expect($this->parent->isTranslatableAttribute('description'))->toBeTrue();
    expect($this->parent->isTranslatableAttribute('is_active'))->toBeFalse();
    expect($this->parent->isTranslatableAttribute('order'))->toBeFalse();
});

it('can query translatable records', function () {
    $categories = TranslatableCategory::whereNull('parent_id')->get();

    expect($categories)->toHaveCount(1);
    expect($categories->first()->id)->toBe($this->parent->id);
});

it('can build tree hierarchy with translatable records', function () {
    $tree = TranslatableCategory::whereNull('parent_id')->get();

    expect($tree)->toHaveCount(1);
    expect($tree->first()->getTranslation('name', 'en'))->toBe('Parent Category');
});

it('preserves parent-child relationships with translations', function () {
    $children = TranslatableCategory::where('parent_id', $this->parent->id)
        ->orderBy('order')
        ->get();

    expect($children)->toHaveCount(2);
    expect($children[0]->getTranslation('name', 'en'))->toBe('Child 1');
    expect($children[1]->getTranslation('name', 'en'))->toBe('Child 2');
});

it('can access translations in multiple locales', function () {
    $nameEn = $this->child1->getTranslation('name', 'en');
    $nameEs = $this->child1->getTranslation('name', 'es');
    $nameFr = $this->child1->getTranslation('name', 'fr');

    expect($nameEn)->toBe('Child 1');
    expect($nameEs)->toBe('Hijo 1');
    expect($nameFr)->toBe('Enfant 1');
});

it('can update translations', function () {
    $translations = json_decode($this->child1->name, true);
    $translations['de'] = 'Kind 1';

    $this->child1->update([
        'name' => json_encode($translations),
    ]);

    $this->child1->refresh();

    $nameGerman = $this->child1->getTranslation('name', 'de');
    expect($nameGerman)->toBe('Kind 1');
});

it('maintains tree structure when working with translations', function () {
    // Create a new child with translations
    $newChild = TranslatableCategory::create([
        'name' => json_encode(['en' => 'Child 3', 'es' => 'Hijo 3', 'fr' => 'Enfant 3']),
        'parent_id' => $this->parent->id,
        'order' => 3,
    ]);

    $children = TranslatableCategory::where('parent_id', $this->parent->id)
        ->orderBy('order')
        ->get();

    expect($children)->toHaveCount(3);
    expect($children[2]->getTranslation('name', 'en'))->toBe('Child 3');
    expect($children[2]->getTranslation('name', 'es'))->toBe('Hijo 3');
});

it('can move translatable nodes between parents', function () {
    // Move child2 to be a child of child1
    $this->child2->update([
        'parent_id' => $this->child1->id,
    ]);

    $this->child2->refresh();

    expect($this->child2->parent_id)->toBe($this->child1->id);
    expect($this->child2->getTranslation('name', 'en'))->toBe('Child 2');
    expect($this->child2->getTranslation('name', 'es'))->toBe('Hijo 2');
});

it('handles missing description gracefully', function () {
    // child2 doesn't have a description
    $description = $this->child2->getTranslation('description', 'en');
    expect($description)->toBeNull();
});

it('preserves translations during reordering', function () {
    // Swap order of child1 and child2
    $this->child1->update(['order' => 2]);
    $this->child2->update(['order' => 1]);

    $children = TranslatableCategory::where('parent_id', $this->parent->id)
        ->orderBy('order')
        ->get();

    // Verify order changed but translations preserved
    expect($children[0]->id)->toBe($this->child2->id);
    expect($children[0]->getTranslation('name', 'es'))->toBe('Hijo 2');

    expect($children[1]->id)->toBe($this->child1->id);
    expect($children[1]->getTranslation('name', 'fr'))->toBe('Enfant 1');
});
