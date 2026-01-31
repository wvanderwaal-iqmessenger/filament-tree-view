<?php

use Openplain\FilamentTreeView\Tests\Models\CustomOrderCategory;

beforeEach(function () {
    // Create test data with custom order field
    $this->parent = CustomOrderCategory::create([
        'name' => 'Parent Category',
        'description' => 'Parent description',
        'parent_id' => null,
        'sort_order' => 1,
    ]);

    $this->child1 = CustomOrderCategory::create([
        'name' => 'Child 1',
        'parent_id' => $this->parent->id,
        'sort_order' => 1,
    ]);

    $this->child2 = CustomOrderCategory::create([
        'name' => 'Child 2',
        'parent_id' => $this->parent->id,
        'sort_order' => 2,
    ]);

    $this->child3 = CustomOrderCategory::create([
        'name' => 'Child 3',
        'parent_id' => $this->parent->id,
        'sort_order' => 3,
    ]);

    $this->grandchild = CustomOrderCategory::create([
        'name' => 'Grandchild',
        'parent_id' => $this->child1->id,
        'sort_order' => 1,
    ]);
});

it('uses custom order field name', function () {
    expect($this->parent->getOrderKeyName())->toBe('sort_order');
    expect($this->child1->getOrderKeyName())->toBe('sort_order');
});

it('orders siblings by custom order field', function () {
    $siblings = CustomOrderCategory::where('parent_id', $this->parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($siblings)->toHaveCount(3);
    expect($siblings[0]->id)->toBe($this->child1->id);
    expect($siblings[1]->id)->toBe($this->child2->id);
    expect($siblings[2]->id)->toBe($this->child3->id);
});

it('can update custom order field directly', function () {
    $this->child1->sort_order = 5;
    $this->child1->save();
    $this->child1->refresh();

    expect($this->child1->sort_order)->toBe(5);
});

it('reorders siblings when moving nodes', function () {
    // Move child3 to be the first sibling
    $this->child3->sort_order = 1;
    $this->child3->save();

    // Reorder all siblings
    $siblings = CustomOrderCategory::where('parent_id', $this->parent->id)
        ->orderBy('id')
        ->get();

    $order = 1;
    foreach ($siblings as $sibling) {
        $sibling->sort_order = $order++;
        $sibling->save();
    }

    // Verify the new order
    $reordered = CustomOrderCategory::where('parent_id', $this->parent->id)
        ->orderBy('sort_order')
        ->get();

    // Check that sort_order is sequential 1, 2, 3
    expect($reordered[0]->sort_order)->toBe(1);
    expect($reordered[1]->sort_order)->toBe(2);
    expect($reordered[2]->sort_order)->toBe(3);
});

it('can build tree hierarchy with custom order field', function () {
    $tree = CustomOrderCategory::whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();

    expect($tree)->toHaveCount(1);
    expect($tree->first()->id)->toBe($this->parent->id);
});

it('can move nodes between parents with custom order field', function () {
    // Move grandchild from child1 to child2
    $this->grandchild->update([
        'parent_id' => $this->child2->id,
        'sort_order' => 1,
    ]);

    $this->grandchild->refresh();

    expect($this->grandchild->parent_id)->toBe($this->child2->id);
    expect($this->grandchild->sort_order)->toBe(1);

    // Verify it's a child of child2
    $children = CustomOrderCategory::where('parent_id', $this->child2->id)->get();
    expect($children)->toHaveCount(1);
    expect($children->first()->id)->toBe($this->grandchild->id);
});

it('maintains custom order when creating new siblings', function () {
    $newChild = CustomOrderCategory::create([
        'name' => 'Child 4',
        'parent_id' => $this->parent->id,
        'sort_order' => 4,
    ]);

    $siblings = CustomOrderCategory::where('parent_id', $this->parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($siblings)->toHaveCount(4);
    expect($siblings[3]->id)->toBe($newChild->id);
    expect($siblings[3]->sort_order)->toBe(4);
});

it('can query descendants with custom order field', function () {
    $descendants = $this->parent->descendants()
        ->orderBy('sort_order')
        ->get();

    expect($descendants->count())->toBeGreaterThan(0);
});

it('preserves custom order field when moving to root level', function () {
    // Move child1 to root level
    $this->child1->update([
        'parent_id' => null,
        'sort_order' => 2,
    ]);

    $this->child1->refresh();

    expect($this->child1->parent_id)->toBeNull();
    expect($this->child1->sort_order)->toBe(2);

    // Verify it's at root level
    $roots = CustomOrderCategory::whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();

    expect($roots)->toHaveCount(2);
    expect($roots->pluck('id')->toArray())->toContain($this->parent->id, $this->child1->id);
});

it('handles reordering with custom field in complex hierarchy', function () {
    // Create a more complex structure
    $newParent = CustomOrderCategory::create([
        'name' => 'New Parent',
        'parent_id' => null,
        'sort_order' => 2,
    ]);

    $newChild1 = CustomOrderCategory::create([
        'name' => 'New Child 1',
        'parent_id' => $newParent->id,
        'sort_order' => 1,
    ]);

    $newChild2 = CustomOrderCategory::create([
        'name' => 'New Child 2',
        'parent_id' => $newParent->id,
        'sort_order' => 2,
    ]);

    // Verify ordering
    $children = CustomOrderCategory::where('parent_id', $newParent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(2);
    expect($children[0]->id)->toBe($newChild1->id);
    expect($children[1]->id)->toBe($newChild2->id);

    // Swap order
    $newChild1->sort_order = 2;
    $newChild2->sort_order = 1;
    $newChild1->save();
    $newChild2->save();

    $reordered = CustomOrderCategory::where('parent_id', $newParent->id)
        ->orderBy('sort_order')
        ->get();

    expect($reordered[0]->id)->toBe($newChild2->id);
    expect($reordered[1]->id)->toBe($newChild1->id);
});
