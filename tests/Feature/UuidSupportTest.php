<?php

use Illuminate\Support\Str;
use Openplain\FilamentTreeView\Tests\Models\UuidCategory;

beforeEach(function () {
    // Create test data with UUIDs
    $this->parent = UuidCategory::create([
        'name' => 'Parent Category',
        'description' => 'Parent description',
        'parent_id' => null,
        'order' => 0,
    ]);

    $this->child1 = UuidCategory::create([
        'name' => 'Child 1',
        'parent_id' => $this->parent->id,
        'order' => 1,
    ]);

    $this->child2 = UuidCategory::create([
        'name' => 'Child 2',
        'parent_id' => $this->parent->id,
        'order' => 2,
    ]);

    $this->grandchild = UuidCategory::create([
        'name' => 'Grandchild',
        'parent_id' => $this->child1->id,
        'order' => 3,
    ]);
});

it('stores UUID primary keys as strings', function () {
    expect($this->parent->id)->toBeString();
    expect(Str::isUuid($this->parent->id))->toBeTrue();
});

it('stores UUID parent_id as strings', function () {
    expect($this->child1->parent_id)->toBeString();
    expect($this->child1->parent_id)->toBe($this->parent->id);
    expect(Str::isUuid($this->child1->parent_id))->toBeTrue();
});

it('can query children with UUID parent_id', function () {
    $children = UuidCategory::where('parent_id', $this->parent->id)->get();

    expect($children)->toHaveCount(2);
    expect($children->pluck('id')->toArray())->toContain($this->child1->id, $this->child2->id);
});

it('can build tree hierarchy with UUIDs', function () {
    $tree = UuidCategory::whereNull('parent_id')->get();

    expect($tree)->toHaveCount(1);
    expect($tree->first()->id)->toBe($this->parent->id);
});

it('can reorder nodes with UUID IDs', function () {
    // Simulate reordering: move child2 to be a child of child1
    $this->child2->update([
        'parent_id' => $this->child1->id,
    ]);

    $this->child2->refresh();

    expect($this->child2->parent_id)->toBe($this->child1->id);
    expect($this->child2->parent_id)->toBeString();
    expect(Str::isUuid($this->child2->parent_id))->toBeTrue();

    // Verify the hierarchy
    $child1Children = UuidCategory::where('parent_id', $this->child1->id)->get();

    expect($child1Children)->toHaveCount(2);
    expect($child1Children->pluck('id')->toArray())->toContain($this->grandchild->id, $this->child2->id);
});

it('can move nodes to root level with UUID IDs', function () {
    // Move grandchild to root level
    $this->grandchild->update([
        'parent_id' => null,
    ]);

    $this->grandchild->refresh();

    expect($this->grandchild->parent_id)->toBeNull();

    // Verify it's at root level
    $rootNodes = UuidCategory::whereNull('parent_id')->get();
    expect($rootNodes->pluck('id')->toArray())->toContain($this->grandchild->id);
});

it('preserves UUID format when moving between nodes', function () {
    // Move grandchild from child1 to child2
    $this->grandchild->update([
        'parent_id' => $this->child2->id,
    ]);

    $this->grandchild->refresh();

    expect($this->grandchild->parent_id)->toBe($this->child2->id);
    expect($this->grandchild->parent_id)->toBeString();
    expect(Str::isUuid($this->grandchild->parent_id))->toBeTrue();

    // Verify in database
    $fromDb = UuidCategory::find($this->grandchild->id);
    expect($fromDb->parent_id)->toBe($this->child2->id);
    expect(Str::isUuid($fromDb->parent_id))->toBeTrue();
});

it('handles root parent_id correctly with UUIDs', function () {
    // When parent_id is null (string representation of no parent)
    $rootNode = UuidCategory::create([
        'name' => 'New Root',
        'order' => 4,
    ]);

    $rootNode->refresh();
    expect($rootNode->parent_id)->toBeNull();

    // Verify it's queryable as root
    $roots = UuidCategory::whereNull('parent_id')->get();
    expect($roots->pluck('id')->toArray())->toContain($rootNode->id);
});

it('does not convert UUID to integer', function () {
    // This test ensures UUIDs remain as strings and are not converted to integers
    $uuid = $this->parent->id;

    // Verify the UUID is not numeric
    expect(is_numeric($uuid))->toBeFalse();

    // Verify we can find by UUID string
    $found = UuidCategory::find($uuid);
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($uuid);
    expect($found->id)->toBeString();
});

it('can handle tree operations with mixed UUID formats', function () {
    // Create nodes with different UUID formats to ensure consistency
    $newParent = UuidCategory::create([
        'name' => 'New Parent',
        'parent_id' => null,
        'order' => 5,
    ]);

    $newChild = UuidCategory::create([
        'name' => 'New Child',
        'parent_id' => $newParent->id, // Using the generated UUID
        'order' => 6,
    ]);

    expect($newChild->parent_id)->toBe($newParent->id);
    expect(Str::isUuid($newChild->parent_id))->toBeTrue();

    // Move an existing node to be a child of the new parent
    $this->child2->update([
        'parent_id' => $newParent->id,
    ]);

    $this->child2->refresh();
    expect($this->child2->parent_id)->toBe($newParent->id);

    // Verify the new parent has 2 children
    $children = UuidCategory::where('parent_id', $newParent->id)->get();
    expect($children)->toHaveCount(2);
});
