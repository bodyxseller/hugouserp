<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\ModuleNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->module = Module::create([
            'key' => 'test_module',
            'name' => 'Test Module',
            'is_active' => true,
        ]);
    }

    public function test_can_create_module_navigation(): void
    {
        $nav = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'nav_key' => 'main_nav',
            'nav_label' => 'Main Navigation',
            'nav_label_ar' => 'القائمة الرئيسية',
            'icon' => 'home',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('module_navigation', [
            'module_id' => $this->module->id,
            'nav_key' => 'main_nav',
        ]);
    }

    public function test_navigation_belongs_to_module(): void
    {
        $nav = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'nav_key' => 'main_nav',
            'nav_label' => 'Main Navigation',
        ]);

        $this->assertInstanceOf(Module::class, $nav->module);
        $this->assertEquals($this->module->id, $nav->module->id);
    }

    public function test_navigation_can_have_parent_and_children(): void
    {
        $parent = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'nav_key' => 'parent_nav',
            'nav_label' => 'Parent Navigation',
        ]);

        $child = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'parent_id' => $parent->id,
            'nav_key' => 'child_nav',
            'nav_label' => 'Child Navigation',
        ]);

        $this->assertInstanceOf(ModuleNavigation::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertCount(1, $parent->children);
    }

    public function test_localized_label_returns_arabic_when_locale_is_ar(): void
    {
        $nav = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'nav_key' => 'main_nav',
            'nav_label' => 'Main Navigation',
            'nav_label_ar' => 'القائمة الرئيسية',
        ]);

        app()->setLocale('ar');
        $this->assertEquals('القائمة الرئيسية', $nav->localized_label);

        app()->setLocale('en');
        $this->assertEquals('Main Navigation', $nav->localized_label);
    }

    public function test_root_items_scope_filters_items_without_parent(): void
    {
        $parent = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'nav_key' => 'parent_nav',
            'nav_label' => 'Parent Navigation',
        ]);

        ModuleNavigation::create([
            'module_id' => $this->module->id,
            'parent_id' => $parent->id,
            'nav_key' => 'child_nav',
            'nav_label' => 'Child Navigation',
        ]);

        $rootItems = ModuleNavigation::rootItems()->get();
        $this->assertCount(1, $rootItems);
        $this->assertEquals('parent_nav', $rootItems->first()->nav_key);
    }

    public function test_get_all_children_returns_all_descendants(): void
    {
        $parent = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'nav_key' => 'parent',
            'nav_label' => 'Parent',
        ]);

        $child1 = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'parent_id' => $parent->id,
            'nav_key' => 'child1',
            'nav_label' => 'Child 1',
        ]);

        $grandchild = ModuleNavigation::create([
            'module_id' => $this->module->id,
            'parent_id' => $child1->id,
            'nav_key' => 'grandchild',
            'nav_label' => 'Grandchild',
        ]);

        $allChildren = $parent->getAllChildren();
        $this->assertCount(2, $allChildren);
        $this->assertTrue($allChildren->contains('nav_key', 'child1'));
        $this->assertTrue($allChildren->contains('nav_key', 'grandchild'));
    }
}
