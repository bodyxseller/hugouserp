<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Module Management Tables
 *
 * This migration creates core module management tables and accounting foundation tables.
 *
 * NOTES:
 * - module_branch and module_custom_fields are unique to this migration
 * - accounts, journal_entries, and journal_entry_lines are created here and enhanced in 2025_12_07_150000
 * - Duplicate definitions for customers, suppliers, purchases, sales, expenses, incomes have been REMOVED
 *   (These are created in earlier migrations: 2025_11_15_000010 and 2025_11_15_000011)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Module Management Tables
        if (! Schema::hasTable('module_branch')) {
            Schema::create('module_branch', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->unique(['module_id', 'branch_id']);
            });
        }

        if (! Schema::hasTable('module_custom_fields')) {
            Schema::create('module_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->string('field_key', 100);
                $table->string('field_label');
                $table->string('field_label_ar')->nullable();
                $table->enum('field_type', ['text', 'textarea', 'number', 'email', 'phone', 'date', 'datetime', 'select', 'multiselect', 'checkbox', 'radio', 'file', 'image', 'color', 'url']);
                $table->json('field_options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->text('validation_rules')->nullable();
                $table->string('placeholder')->nullable();
                $table->string('default_value')->nullable();
                $table->timestamps();
                $table->unique(['module_id', 'field_key']);
            });
        }


        // Accounting Foundation Tables (created here, enhanced in 2025_12_07_150000)
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('account_number')->unique();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->decimal('balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches');
                $table->string('reference_number')->unique();
                $table->date('entry_date');
                $table->text('description')->nullable();
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts');
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Drop only the tables created in this migration
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('module_custom_fields');
        Schema::dropIfExists('module_branch');
        
        // Note: customers, suppliers, purchases, sales, expenses, incomes
        // are dropped by their respective original migrations (2025_11_15_*)
    }
};
