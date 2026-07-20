<?php

use App\Models\User;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Database\Seeders\DatabaseSeeder;

/**
 * `system:reset` / `deploy.sh` run DatabaseSeeder on the server. This is the only
 * coverage that the full seeder chain boots end-to-end — it caught a --no-dev
 * deploy crash (Faker-dependent demo seeders) that module tests could not.
 */
it('seeds the full baseline chain, real masters, and the test super admin', function () {
    $this->seed(DatabaseSeeder::class);

    // Test user is always recreated so login survives every reset.
    $testUser = User::where('email', 'test@example.com')->first();
    expect($testUser)->not->toBeNull()
        ->and($testUser->hasRole('Super Admin'))->toBeTrue();

    // A curated (non-Faker) master must exist regardless of environment.
    expect(SpareBrandMaster::where('name', 'BOSCH')->exists())->toBeTrue();
});
