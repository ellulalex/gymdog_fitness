<?php

use App\Models\User;
use Filament\Facades\Filament;

it('shows the admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Sign in', false);
});

it('lets a staff user reach the admin panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $this->actingAs($user)->get('/admin')->assertOk();
});
