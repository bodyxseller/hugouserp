<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflow definitions
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('module_name'); // sales, purchases, hrm, etc.
            $table->string('entity_type'); // Sale, Purchase, LeaveRequest, etc.
            $table->text('description')->nullable();
            $table->json('stages'); // [{name: 'Draft', order: 1}, {name: 'Approved', order: 2}]
            $table->json('rules')->nullable(); // Conditional rules for workflow triggering
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();

            $table->index(['module_name', 'is_active']);
            $table->index('entity_type');
        });

        // Workflow instances (actual workflow runs)
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('current_stage');
            $table->string('status'); // pending, approved, rejected, cancelled
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['status', 'current_stage']);
            $table->index('initiated_by');
        });

        // Workflow approvals (individual approval steps)
        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->onDelete('cascade');
            $table->string('stage_name');
            $table->integer('stage_order');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('approver_role')->nullable(); // If approval by role
            $table->string('status'); // pending, approved, rejected, skipped
            $table->text('comments')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index(['workflow_instance_id', 'stage_order']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
        });

        // Workflow notifications
        Schema::create('workflow_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_approval_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // approval_request, approval_granted, approval_rejected
            $table->string('channel'); // email, system, sms
            $table->text('message');
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_sent']);
            $table->index('type');
        });

        // Workflow rules (for conditional logic)
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('priority')->default(0);
            $table->json('conditions'); // [{field: 'amount', operator: '>', value: 1000}]
            $table->json('actions'); // [{type: 'require_approval', params: {...}}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_definition_id', 'is_active']);
        });

        // Workflow audit trail
        Schema::create('workflow_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // created, approved, rejected, reassigned, cancelled
            $table->string('from_stage')->nullable();
            $table->string('to_stage')->nullable();
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index('workflow_instance_id');
            $table->index('performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_audit_logs');
        Schema::dropIfExists('workflow_rules');
        Schema::dropIfExists('workflow_notifications');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
    }
};
