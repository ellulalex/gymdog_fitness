<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('renders the content admin pages', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/admin/pages',
    '/admin/pages/create',
    '/admin/posts',
    '/admin/posts/create',
    '/admin/post-categories',
    '/admin/post-categories/create',
    '/admin/redirects',
    '/admin/redirects/create',
]);
