<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_subjects(): void
    {
        Subject::factory()->count(7)->create();

        $this->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Subjects/Index')
                ->has('subjects.data', 5)
                ->where('subjects.total', 7)
                ->where('filters.search', '')
                ->has('stats')
            );
    }

    public function test_headmaster_can_view_subjects_without_mutation_permissions(): void
    {
        $headmaster = User::factory()->guru()->create();
        School::factory()->create(['headmaster_id' => $headmaster->id]);
        $subject = Subject::factory()->create();
        $this->actingAs($headmaster);

        $this->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissions.can_create', false)
                ->where('subjects.data.0.id', $subject->id)
                ->where('subjects.data.0.can_edit', false)
                ->where('subjects.data.0.can_delete', false)
                ->where('subjects.data.0.can_toggle_status', false)
            );
    }

    public function test_headmaster_cannot_create_import_update_delete_or_toggle_subjects(): void
    {
        $headmaster = User::factory()->guru()->create();
        School::factory()->create(['headmaster_id' => $headmaster->id]);
        $subject = Subject::factory()->create([
            'code' => 'MAT',
            'name' => 'Matematika',
            'is_active' => false,
        ]);
        $this->actingAs($headmaster);

        $this->post(route('admin.subjects.store'), [
            'code' => 'BIN',
            'name' => 'Bahasa Indonesia',
        ])->assertForbidden();

        $this->post(route('admin.subjects.import'))
            ->assertForbidden();

        $this->put(route('admin.subjects.update', $subject), [
            'code' => 'MTK',
            'name' => 'Matematika Lanjutan',
        ])->assertForbidden();

        $this->patch(route('admin.subjects.toggle-status', $subject))
            ->assertForbidden();

        $this->delete(route('admin.subjects.destroy', $subject))
            ->assertForbidden();

        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'is_active' => false,
        ]);
    }

    public function test_index_filters_by_name_or_code(): void
    {
        Subject::factory()->create(['code' => 'MAT', 'name' => 'Matematika']);
        Subject::factory()->create(['code' => 'BIN', 'name' => 'Bahasa Indonesia']);

        $this->get(route('admin.subjects.index', ['search' => 'MAT']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('subjects.data', 1)
                ->where('subjects.data.0.code', 'MAT')
            );
    }

    public function test_index_includes_subject_stats(): void
    {
        $active = Subject::factory()->create(['is_active' => true]);
        Subject::factory()->create(['is_active' => false]);

        $this->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.active.id', $active->id)
                ->where('stats.active.code', $active->code)
                ->where('stats.active.name', $active->name)
                ->where('stats.active.is_active', true)
                ->where('stats.inactive', 1)
            );
    }

    public function test_store_creates_subject(): void
    {
        $this->post(route('admin.subjects.store'), [
            'code' => 'MAT',
            'name' => 'Matematika',
        ])->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'code' => 'MAT',
            'name' => 'Matematika',
            'is_active' => false,
        ]);
    }

    public function test_store_validates_code_required(): void
    {
        $this->post(route('admin.subjects.store'), [
            'name' => 'Matematika',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_validates_code_regex(): void
    {
        $this->post(route('admin.subjects.store'), [
            'code' => 'mat_01', // lowercase and underscore not allowed
            'name' => 'Matematika',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_rejects_duplicate_code(): void
    {
        Subject::factory()->create(['code' => 'MAT']);

        $this->post(route('admin.subjects.store'), [
            'code' => 'MAT',
            'name' => 'Matematika Lanjut',
        ])->assertSessionHasErrors('code');
    }

    public function test_toggle_status_activates_subject(): void
    {
        $previous = Subject::factory()->create(['is_active' => true]);
        $current = Subject::factory()->create(['is_active' => false]);

        $this->patch(route('admin.subjects.toggle-status', $current))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame(2, Subject::query()->where('is_active', true)->count());
    }

    public function test_toggle_status_deactivates_active_subject(): void
    {
        $subject = Subject::factory()->create(['is_active' => true]);

        $this->patch(route('admin.subjects.toggle-status', $subject))
            ->assertRedirect();

        $this->assertFalse($subject->fresh()->is_active);
        $this->assertSame(0, Subject::query()->where('is_active', true)->count());
    }

    public function test_update_validates_and_updates_subject(): void
    {
        $subject = Subject::factory()->create(['code' => 'MAT', 'name' => 'Matematika']);

        $this->put(route('admin.subjects.update', $subject), [
            'code' => 'MTK',
            'name' => 'Matematika Peminatan',
        ])->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'code' => 'MTK',
            'name' => 'Matematika Peminatan',
        ]);
    }

    public function test_update_validates_code_unique_per_code(): void
    {
        Subject::factory()->create(['code' => 'BIN']);
        $subject = Subject::factory()->create(['code' => 'MAT', 'name' => 'Matematika']);

        $this->put(route('admin.subjects.update', $subject), [
            'code' => 'BIN',
            'name' => 'Matematika',
        ])->assertSessionHasErrors('code');
    }

    public function test_destroy_deletes_subject(): void
    {
        $subject = Subject::factory()->create();

        $this->delete(route('admin.subjects.destroy', $subject))
            ->assertRedirect();

        $this->assertDatabaseMissing('subjects', [
            'id' => $subject->id,
        ]);
    }

    public function test_import_creates_subjects_from_spreadsheet(): void
    {
        $content = "kode,nama,status\nMAT,Matematika,aktif\nBIN,Bahasa Indonesia,tidak";
        $file = UploadedFile::fake()->createWithContent('subjects.csv', $content);

        $this->post(route('admin.subjects.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('subjects', [
            'code' => 'MAT',
            'name' => 'Matematika',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('subjects', [
            'code' => 'BIN',
            'name' => 'Bahasa Indonesia',
            'is_active' => false,
        ]);
    }

    public function test_import_validates_file_required_and_extension(): void
    {
        $this->post(route('admin.subjects.import'), [])
            ->assertSessionHasErrors('file');

        $invalidFile = UploadedFile::fake()->create('subjects.txt', 10);
        $this->post(route('admin.subjects.import'), ['file' => $invalidFile])
            ->assertSessionHasErrors('file');
    }

    public function test_import_handles_duplicate_or_invalid_rows(): void
    {
        Subject::factory()->create(['code' => 'MAT']);

        $content = "kode,nama\nMAT,Matematika Duplikat\nFIS,Fisika";
        $file = UploadedFile::fake()->createWithContent('subjects.csv', $content);

        $this->post(route('admin.subjects.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        // Only FIS should be imported because MAT already exists
        $this->assertDatabaseHas('subjects', ['code' => 'FIS', 'name' => 'Fisika']);
        $this->assertSame(2, Subject::count());
    }
}
