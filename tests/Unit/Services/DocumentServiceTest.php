<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DocumentService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);
    }

    public function test_can_create_document(): void
    {
        $data = [
            'title' => 'Test Document',
            'description' => 'Test Description',
            'file_path' => 'documents/test.pdf',
            'file_type' => 'pdf',
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ];

        $document = $this->service->createDocument($data);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('Test Document', $document->title);
    }

    public function test_can_share_document(): void
    {
        $document = Document::create([
            'title' => 'Test',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'branch_id' => $this->branch->id,
        ]);

        $shared = $this->service->shareDocument($document->id, [1, 2]);

        $this->assertTrue($shared);
    }

    public function test_can_add_document_version(): void
    {
        $document = Document::create([
            'title' => 'Test',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'branch_id' => $this->branch->id,
        ]);

        $version = $this->service->addVersion($document->id, [
            'file_path' => 'test-v2.pdf',
            'version' => '2.0',
            'notes' => 'Updated version',
        ]);

        $this->assertNotNull($version);
    }

    public function test_validates_document_access(): void
    {
        $document = Document::create([
            'title' => 'Test',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'branch_id' => $this->branch->id,
            'created_by' => 1,
        ]);

        $hasAccess = $this->service->canAccess($document->id, 1);

        $this->assertTrue($hasAccess);
    }
}
