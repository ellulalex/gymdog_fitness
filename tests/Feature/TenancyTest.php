<?php

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Widget;

beforeEach(function () {
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->string('name');
    });
});

afterEach(function () {
    Schema::dropIfExists('widgets');
});

function tenant(string $slug): Tenant
{
    return Tenant::create(['slug' => $slug, 'name' => ucfirst($slug)]);
}

it('auto-fills tenant_id from the current tenant on create', function () {
    $gymdog = tenant('gymdog');
    app(TenantManager::class)->set($gymdog);

    $widget = Widget::create(['name' => 'Grips']);

    expect($widget->tenant_id)->toBe($gymdog->id);
});

it('scopes queries to the current tenant', function () {
    $one = tenant('one');
    $two = tenant('two');
    $manager = app(TenantManager::class);

    $manager->set($one);
    Widget::create(['name' => 'belongs-to-one']);

    $manager->set($two);
    Widget::create(['name' => 'belongs-to-two']);

    // Back to tenant one — only its row is visible.
    $manager->set($one);
    expect(Widget::count())->toBe(1)
        ->and(Widget::first()->name)->toBe('belongs-to-one');
});

it('can bypass the tenant scope explicitly', function () {
    $manager = app(TenantManager::class);

    $manager->set(tenant('one'));
    Widget::create(['name' => 'a']);
    $manager->set(tenant('two'));
    Widget::create(['name' => 'b']);

    expect(Widget::withoutTenantScope()->count())->toBe(2);
});

it('does not scope when no tenant is resolved', function () {
    $manager = app(TenantManager::class);

    $manager->set(tenant('one'));
    Widget::create(['name' => 'a']);
    $manager->set(tenant('two'));
    Widget::create(['name' => 'b']);

    $manager->set(null);
    expect(Widget::count())->toBe(2);
});
