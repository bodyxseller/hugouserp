<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);
    }

    public function test_can_create_project(): void
    {
        $data = [
            'name' => 'Test Project',
            'code' => 'PROJ001',
            'description' => 'Test Description',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ];

        $project = $this->service->createProject($data);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('Test Project', $project->name);
    }

    public function test_can_add_task_to_project(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'code' => 'PROJ001',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $task = $this->service->addTask($project->id, [
            'title' => 'Test Task',
            'description' => 'Task description',
            'status' => 'pending',
        ]);

        $this->assertNotNull($task);
    }

    public function test_can_calculate_project_progress(): void
    {
        $project = Project::create([
            'name' => 'Test',
            'code' => 'PROJ001',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $progress = $this->service->calculateProgress($project->id);

        $this->assertIsNumeric($progress);
        $this->assertGreaterThanOrEqual(0, $progress);
        $this->assertLessThanOrEqual(100, $progress);
    }

    public function test_can_log_project_time(): void
    {
        $project = Project::create([
            'name' => 'Test',
            'code' => 'PROJ001',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $timeLog = $this->service->logTime($project->id, [
            'user_id' => 1,
            'hours' => 8,
            'date' => now(),
            'description' => 'Development work',
        ]);

        $this->assertNotNull($timeLog);
    }
}
