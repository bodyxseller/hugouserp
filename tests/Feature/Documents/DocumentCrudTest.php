<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Models\Branch;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    public function test_can_create_document(): void
    {
        $document = Document::create([
            'title' => 'Test Document',
            'description' => 'Test description',
            'file_path' => 'documents/test.pdf',
            'file_type' => 'pdf',
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('documents', ['title' => 'Test Document']);
    }

    public function test_can_read_document(): void
    {
        $document = Document::create([
            'title' => 'Test Document',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'branch_id' => $this->branch->id,
        ]);

        $found = Document::find($document->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_document(): void
    {
        $document = Document::create([
            'title' => 'Test Document',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'branch_id' => $this->branch->id,
        ]);

        $document->update(['title' => 'Updated Document']);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'title' => 'Updated Document']);
    }

    public function test_can_delete_document(): void
    {
        $document = Document::create([
            'title' => 'Test Document',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'branch_id' => $this->branch->id,
        ]);

        $document->delete();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
