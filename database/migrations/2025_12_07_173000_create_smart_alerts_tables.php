<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alert Rules - Define what to monitor
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // null = global
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->enum('category', [
                'inventory',
                'sales',
                'purchases',
                'financial',
                'hrm',
                'system',
                'rental',
                'customer',
            ])->default('system');
            $table->enum('alert_type', [
                'threshold',        // Value crosses threshold
                'anomaly',          // Unusual pattern detected
                'deadline',         // Time-based alert
                'status_change',    // Status changed
                'prediction',       // Predictive alert
            ])->default('threshold');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->json('conditions'); // Rule conditions
            $table->json('thresholds')->nullable(); // Threshold values
            $table->string('metric_type')->nullable(); // What to measure
            $table->integer('check_frequency_minutes')->default(60); // How often to check
            $table->boolean('is_active')->default(true);
            $table->boolean('send_email')->default(false);
            $table->boolean('send_notification')->default(true);
            $table->json('recipient_roles')->nullable(); // Which roles to notify
            $table->json('recipient_users')->nullable(); // Specific users
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
            $table->index(['category', 'alert_type']);
            $table->index('last_checked_at');
        });

        // Alert Instances - Triggered alerts
        Schema::create('alert_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->json('data')->nullable(); // Alert-specific data
            $table->string('entity_type')->nullable(); // Related model class
            $table->unsignedBigInteger('entity_id')->nullable(); // Related model ID
            $table->string('action_url')->nullable(); // Link to relevant page
            $table->enum('status', ['new', 'acknowledged', 'resolved', 'ignored'])->default('new');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['alert_rule_id', 'status']);
            $table->index(['branch_id', 'severity', 'status']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('triggered_at');
        });

        // Alert Recipients - Who received the alert
        Schema::create('alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('notification_sent')->default(false);
            $table->boolean('email_sent')->default(false);
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['alert_instance_id', 'user_id']);
            $table->index(['user_id', 'read']);
        });

        // Anomaly Detection Baselines - For anomaly detection
        Schema::create('anomaly_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric_key'); // e.g., 'daily_sales', 'product_stock_movement'
            $table->string('entity_type')->nullable(); // Optional: specific product, customer, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->decimal('mean', 15, 2);
            $table->decimal('std_dev', 15, 2);
            $table->decimal('min', 15, 2);
            $table->decimal('max', 15, 2);
            $table->integer('sample_count')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'metric_key', 'entity_type', 'entity_id', 'period_start'], 'anomaly_baseline_unique');
            $table->index(['metric_key', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_baselines');
        Schema::dropIfExists('alert_recipients');
        Schema::dropIfExists('alert_instances');
        Schema::dropIfExists('alert_rules');
    }
};
