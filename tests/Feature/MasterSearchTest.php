<?php

use App\Livewire\MasterSearch;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Support\SearchRegistry;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('returns empty results for an empty term', function () {
    $registry = app(SearchRegistry::class);

    expect($registry->search(''))->toBe([]);
});

it('Searchable trait scope filters case-insensitively', function () {
    CustomerMaster::factory()->create(['name' => 'RAVI SHARMA', 'phone' => '9876543210']);
    CustomerMaster::factory()->create(['name' => 'PRIYA PATEL', 'phone' => '9123456780']);

    $found = CustomerMaster::query()->search('ravi')->get();

    expect($found)->toHaveCount(1)
        ->and($found->first()->name)->toBe('RAVI SHARMA');
});

it('Searchable trait searches across all configured fields', function () {
    CustomerMaster::factory()->create(['name' => 'TEST ONE', 'phone' => '5550001', 'email' => 'one@test.com']);
    CustomerMaster::factory()->create(['name' => 'TEST TWO', 'phone' => '5550002', 'email' => 'two@test.com']);

    expect(CustomerMaster::query()->search('5550001')->count())->toBe(1)
        ->and(CustomerMaster::query()->search('two@test')->count())->toBe(1)
        ->and(CustomerMaster::query()->search('TEST')->count())->toBe(2);
});

it('SearchRegistry returns grouped results across registered modules', function () {
    CustomerMaster::factory()->create(['name' => 'BOSCH MOTORS LTD', 'phone' => '9999999999']);
    VehicleBrandMaster::factory()->create(['name' => 'BOSCH']);

    $registry = app(SearchRegistry::class);
    $results = $registry->search('BOSCH');

    $labels = collect($results)->pluck('label')->all();
    expect($labels)->toContain('Customers')
        ->and($labels)->toContain('Vehicle Brands');
});

it('SearchRegistry caps results per source', function () {
    for ($i = 0; $i < 8; $i++) {
        CustomerMaster::factory()->create(['name' => 'MATCH '.$i, 'phone' => '9999999'.$i]);
    }

    $registry = app(SearchRegistry::class);
    $results = $registry->search('MATCH', perSource: 5);

    $customers = collect($results)->firstWhere('label', 'Customers');
    expect($customers['count'])->toBe(5);
});

it('SearchRegistry omits modules with zero matches', function () {
    CustomerMaster::factory()->create(['name' => 'UNIQUE-CUSTOMER-NAME']);

    $registry = app(SearchRegistry::class);
    $results = $registry->search('UNIQUE-CUSTOMER-NAME');

    $labels = collect($results)->pluck('label')->all();
    expect($labels)->toBe(['Customers']);
});

it('MasterSearch component renders empty state when term is blank', function () {
    Livewire::test(MasterSearch::class)
        ->assertOk()
        ->assertSee('Start typing');
});

it('MasterSearch component renders results when term matches', function () {
    CustomerMaster::factory()->create(['name' => 'PALETTE CUSTOMER']);

    Livewire::test(MasterSearch::class)
        ->set('term', 'PALETTE')
        ->assertSee('Customers')
        ->assertSee('PALETTE CUSTOMER');
});

it('MasterSearch component renders no-match state when term has no hits', function () {
    Livewire::test(MasterSearch::class)
        ->set('term', 'ZZZZZZZNO-SUCH-RECORD-EXISTS')
        ->assertSee('No matches');
});

it('every searchable model implements toSearchResult correctly', function () {
    $cust = CustomerMaster::factory()->create(['name' => 'WHATEVER', 'phone' => '9876543210']);
    $brand = VehicleBrandMaster::factory()->create(['name' => 'TESTBRAND']);
    $emp = EmployeeMaster::factory()->create(['name' => 'WHO', 'employee_code' => 'EMP-X-1']);

    foreach ([$cust, $brand, $emp] as $r) {
        $row = $r->toSearchResult();
        expect($row)->toHaveKeys(['id', 'title']);
        expect($row['title'])->not->toBeEmpty();
    }
});

it('SearchRegistry sources include the 6 wired modules', function () {
    $sources = app(SearchRegistry::class)->sources()->pluck('module')->all();

    expect($sources)
        ->toContain('CustomerMaster')
        ->toContain('CustomerVehicleMaster')
        ->toContain('EmployeeMaster')
        ->toContain('VehicleBrandMaster')
        ->toContain('VehicleModelMaster')
        ->toContain('InsuranceCompanyMaster');
});

it('multi-token search: "ra ri" matches Viraj Zaveri (both tokens in name)', function () {
    // Pin email — the factory's random email may otherwise contain "ra"
    // by coincidence and pull in unintended rows.
    CustomerMaster::factory()->create([
        'name' => 'VIRAJ ZAVERI', 'phone' => '9876543210',
        'email' => 'v@test.test',
    ]);
    CustomerMaster::factory()->create([
        'name' => 'PRIYA PATEL', 'phone' => '9123456780',
        'email' => 'p@test.test',
    ]);

    $found = CustomerMaster::query()->search('ra ri')->get();

    expect($found)->toHaveCount(1)
        ->and($found->first()->name)->toBe('VIRAJ ZAVERI');
});

it('multi-token search: "viraj 7874" matches a name+phone combo across fields', function () {
    CustomerMaster::factory()->create(['name' => 'VIRAJ ZAVERI', 'phone' => '7874940123']);
    CustomerMaster::factory()->create(['name' => 'VIRAJ MEHTA', 'phone' => '9876543210']); // wrong phone
    CustomerMaster::factory()->create(['name' => 'PRIYA PATEL', 'phone' => '7874940000']); // wrong name

    $found = CustomerMaster::query()->search('viraj 7874')->get();

    expect($found)->toHaveCount(1)
        ->and($found->first()->name)->toBe('VIRAJ ZAVERI');
});

it('multi-token search: every token must match somewhere (AND across tokens)', function () {
    CustomerMaster::factory()->create(['name' => 'VIRAJ ZAVERI', 'phone' => '9876543210']);

    // "viraj zzzzz" — first token matches, second matches nothing → no result.
    $found = CustomerMaster::query()->search('viraj zzzzz')->get();

    expect($found)->toHaveCount(0);
});

it('multi-token search: tokens are independent (one in name, one in email)', function () {
    CustomerMaster::factory()->create([
        'name' => 'VIRAJ ZAVERI',
        'phone' => '9876543210',
        'email' => 'viraj@gmail.com',
    ]);

    $found = CustomerMaster::query()->search('viraj gmail')->get();

    expect($found)->toHaveCount(1)
        ->and($found->first()->name)->toBe('VIRAJ ZAVERI');
});

it('multi-token search: extra whitespace is ignored', function () {
    CustomerMaster::factory()->create(['name' => 'VIRAJ ZAVERI']);

    expect(CustomerMaster::query()->search('  ra    ri  ')->count())->toBe(1)
        ->and(CustomerMaster::query()->search("ra\tri")->count())->toBe(1);
});

it('on SQLite: scopeSearch emits ILIKE-only SQL (no word_similarity)', function () {
    if (CustomerMaster::query()->getConnection()->getDriverName() === 'pgsql') {
        $this->markTestSkipped('Test only runs against the SQLite test DB.');
    }

    $sql = CustomerMaster::query()->search('viaraj')->toRawSql();

    expect($sql)->not->toContain('word_similarity')
        ->and(strtolower($sql))->toContain('like');
});

it('on Postgres: scopeSearch emits a word_similarity OR clause for tokens >= 4 chars', function () {
    if (CustomerMaster::query()->getConnection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Test only runs against Postgres.');
    }

    $sqlLong = CustomerMaster::query()->search('viaraj')->toRawSql();
    $sqlShort = CustomerMaster::query()->search('vir')->toRawSql();

    expect($sqlLong)->toContain('word_similarity')
        ->and($sqlShort)->not->toContain('word_similarity'); // short token skips fuzzy
});

it('searches through a relation so a job card is findable by customer name', function () {
    $customer = CustomerMaster::factory()->create(['name' => 'NILESH KULKARNI', 'first_name' => 'NILESH']);
    $mine = JobCard::factory()->create(['customer_id' => $customer->id]);
    $other = JobCard::factory()->create();

    $found = JobCard::query()->search('NILESH')->pluck('id');

    expect($found)->toContain($mine->id)
        ->and($found)->not->toContain($other->id);
});

it('keeps identifier lookups exact instead of fuzzily matching similar codes', function () {
    // Structured numbers share long prefixes; trigram matching on them would
    // return every neighbouring card. Only the typed one should come back.
    $a = JobCard::factory()->create(['job_card_no' => 'MQ/JC/21-22/3099']);
    JobCard::factory()->create(['job_card_no' => 'MQ/JC/21-22/3098']);
    JobCard::factory()->create(['job_card_no' => 'MQ/JC/21-22/3097']);

    $found = JobCard::query()->search('MQ/JC/21-22/3099')->get();

    expect($found)->toHaveCount(1)
        ->and($found->first()->id)->toBe($a->id);
});
