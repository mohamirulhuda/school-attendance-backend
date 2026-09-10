<?php

use App\Models\Period;
use App\Services\Period\ManagePeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ManagePeriod::class);
});

it('creates a valid period', function () {
    $period = $this->service->create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    expect($period)
        ->toBeInstanceOf(Period::class)
        ->and($period->number)->toBe(1)
        ->and($period->name)->toBe('Jam 1')
        ->and($period->is_active)->toBeTrue();

    $this->assertDatabaseHas('periods', [
        'id' => $period->id,
        'number' => 1,
        'name' => 'Jam 1',
        'is_active' => true,
    ]);
});

it('rejects an invalid time range', function () {
    $this->service->create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '08:00',
        'ends_at' => '07:00',
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('requires active period numbers to start at one without gaps', function () {
    Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    Period::create([
        'number' => 3,
        'name' => 'Jam 3',
        'starts_at' => '08:30',
        'ends_at' => '09:15',
        'is_active' => true,
    ]);

    $this->service->create([
        'number' => 4,
        'name' => 'Jam 4',
        'starts_at' => '09:30',
        'ends_at' => '10:15',
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('rejects overlapping active periods', function () {
    Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    $this->service->create([
        'number' => 2,
        'name' => 'Jam 2',
        'starts_at' => '07:30',
        'ends_at' => '08:15',
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('rejects deactivating a period when it creates a number gap', function () {
    Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    $period2 = Period::create([
        'number' => 2,
        'name' => 'Jam 2',
        'starts_at' => '07:45',
        'ends_at' => '08:30',
        'is_active' => true,
    ]);

    Period::create([
        'number' => 3,
        'name' => 'Jam 3',
        'starts_at' => '08:30',
        'ends_at' => '09:15',
        'is_active' => true,
    ]);

    $this->service->update($period2, [
        'is_active' => false,
    ]);
})->throws(ValidationException::class);

it('updates a period with a valid configuration', function () {
    $period = Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    $updated = $this->service->update($period, [
        'name' => 'Jam Pertama',
        'starts_at' => '07:15',
        'ends_at' => '08:00',
    ]);

    expect($updated->name)->toBe('Jam Pertama')
        ->and($updated->starts_at->format('H:i'))->toBe('07:15')
        ->and($updated->ends_at->format('H:i'))->toBe('08:00');
});

it('allows an inactive period to have a number outside the active configuration', function () {
    Period::create([
        'number' => 1,
        'name' => 'Jam 1',
        'starts_at' => '07:00',
        'ends_at' => '07:45',
        'is_active' => true,
    ]);

    $period = $this->service->create([
        'number' => 99,
        'name' => 'Historical Period',
        'starts_at' => '10:00',
        'ends_at' => '10:45',
        'is_active' => false,
    ]);

    expect($period->is_active)->toBeFalse()
        ->and($period->number)->toBe(99);
});
