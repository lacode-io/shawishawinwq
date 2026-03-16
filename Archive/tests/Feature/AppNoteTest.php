<?php

use App\Enums\NotePriority;
use App\Enums\NoteType;
use App\Filament\Resources\AppNoteResource\Pages\CreateAppNote;
use App\Filament\Resources\AppNoteResource\Pages\EditAppNote;
use App\Filament\Resources\AppNoteResource\Pages\ListAppNotes;
use App\Models\AppNote;

use function Pest\Livewire\livewire;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

// ── Permission Tests ──

it('allows super_admin to list notes', function () {
    $this->actingAs(createSuperAdmin());

    livewire(ListAppNotes::class)->assertSuccessful();
});

it('allows viewer to list notes', function () {
    $this->actingAs(createViewer());

    livewire(ListAppNotes::class)->assertSuccessful();
});

it('allows accountant to access create note page', function () {
    $this->actingAs(createAccountant());

    livewire(CreateAppNote::class)->assertSuccessful();
});

it('denies viewer from accessing create note page', function () {
    $this->actingAs(createViewer());

    livewire(CreateAppNote::class)->assertForbidden();
});

it('allows accountant to access edit note page', function () {
    $user = createAccountant();
    $this->actingAs($user);
    $note = AppNote::factory()->create([
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    livewire(EditAppNote::class, ['record' => $note->getRouteKey()])
        ->assertSuccessful();
});

it('denies viewer from accessing edit note page', function () {
    $user = createViewer();
    $this->actingAs($user);
    $admin = createSuperAdmin();
    $note = AppNote::factory()->create([
        'created_by_user_id' => $admin->id,
        'updated_by_user_id' => $admin->id,
    ]);

    livewire(EditAppNote::class, ['record' => $note->getRouteKey()])
        ->assertForbidden();
});

it('denies accountant from deleting notes', function () {
    $user = createAccountant();

    expect($user->hasPermissionTo('delete_app_notes'))->toBeFalse();
});

it('allows super_admin to delete notes', function () {
    $user = createSuperAdmin();
    $note = AppNote::factory()->create([
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    expect($user->can('delete', $note))->toBeTrue();
});

// ── CRUD Tests ──

it('can create a note', function () {
    $user = createSuperAdmin();
    $this->actingAs($user);

    livewire(CreateAppNote::class)
        ->set('data.type', NoteType::Note->value)
        ->set('data.title', 'ملاحظة اختبار')
        ->set('data.body', 'محتوى الملاحظة للاختبار')
        ->set('data.priority', NotePriority::Normal->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('app_notes', [
        'title' => 'ملاحظة اختبار',
        'body' => 'محتوى الملاحظة للاختبار',
        'type' => 'note',
        'priority' => 'normal',
        'created_by_user_id' => $user->id,
    ]);
});

it('can create an inventory note', function () {
    $user = createSuperAdmin();
    $this->actingAs($user);

    livewire(CreateAppNote::class)
        ->set('data.type', NoteType::Inventory->value)
        ->set('data.title', 'جرد المخزن')
        ->set('data.body', 'تفاصيل الجرد')
        ->set('data.priority', NotePriority::High->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('app_notes', [
        'title' => 'جرد المخزن',
        'type' => 'inventory',
        'priority' => 'high',
    ]);
});

it('can edit a note', function () {
    $user = createSuperAdmin();
    $this->actingAs($user);
    $note = AppNote::factory()->create([
        'title' => 'عنوان قديم',
        'body' => 'محتوى قديم',
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    livewire(EditAppNote::class, ['record' => $note->getRouteKey()])
        ->set('data.title', 'عنوان جديد')
        ->set('data.body', 'محتوى جديد')
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('app_notes', [
        'id' => $note->id,
        'title' => 'عنوان جديد',
        'body' => 'محتوى جديد',
    ]);
});

it('logs activity when creating a note', function () {
    $user = createSuperAdmin();
    $this->actingAs($user);

    $note = AppNote::factory()->create([
        'title' => 'ملاحظة مسجلة',
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => AppNote::class,
        'subject_id' => $note->id,
        'event' => 'created',
    ]);
});
