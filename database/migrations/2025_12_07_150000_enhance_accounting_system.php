<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add currency support to accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable()->after('type');
            $table->boolean('requires_currency')->default(false)->after('currency_code');
            $table->string('account_category')->nullable()->after('type')->comment('current, fixed, long-term, etc.');
            $table->string('sub_category')->nullable()->after('account_category')->comment('current, fixed, etc.');
            $table->boolean('is_system_account')->default(false)->after('is_active');
            $table->json('metadata')->nullable()->after('description');

            $table->index(['type', 'is_active']);
            $table->index('currency_code');
        });

        // Create account mappings table for module integration
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('module_name'); // sales, purchases, inventory, rental, hrm
            $table->string('mapping_key'); // sales_revenue, cogs, tax_payable, etc.
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->json('conditions')->nullable(); // conditional mappings
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'module_name', 'mapping_key']);
            $table->index(['module_name', 'is_active']);
        });

        // Enhance journal entries with more tracking
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('source_module')->nullable()->after('status'); // sales, purchases, etc.
            $table->string('source_type')->nullable()->after('source_module'); // Sale, Purchase, etc.
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('fiscal_year', 4)->nullable()->after('entry_date');
            $table->string('fiscal_period', 2)->nullable()->after('fiscal_year');
            $table->boolean('is_auto_generated')->default(false)->after('status');
            $table->boolean('is_reversible')->default(true)->after('is_auto_generated');
            $table->foreignId('reversed_by_entry_id')->nullable()->after('is_reversible')->constrained('journal_entries')->onDelete('set null');

            $table->index(['source_module', 'source_type', 'source_id']);
            $table->index(['fiscal_year', 'fiscal_period']);
            $table->index(['status', 'entry_date']);
        });

        // Add more context to journal entry lines
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->string('dimension1')->nullable()->after('description')->comment('Cost center, department, etc.');
            $table->string('dimension2')->nullable()->after('dimension1')->comment('Project, location, etc.');
            $table->foreignId('currency_id')->nullable()->after('credit')->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 10, 6)->nullable()->after('currency_id')->default(1.000000);
            $table->decimal('debit_base', 15, 2)->nullable()->after('exchange_rate')->comment('Amount in base currency');
            $table->decimal('credit_base', 15, 2)->nullable()->after('debit_base')->comment('Amount in base currency');

            $table->index('dimension1');
            $table->index('dimension2');
        });

        // Create fiscal periods table
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('year', 4);
            $table->string('period', 2); // 01-12 for monthly
            $table->string('name'); // January 2025
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->timestamps();

            $table->unique(['branch_id', 'year', 'period']);
            $table->index(['year', 'period', 'status']);
        });

        // Create financial reports configuration table
        Schema::create('financial_report_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('report_type'); // trial_balance, balance_sheet, income_statement
            $table->string('name');
            $table->json('configuration'); // account filters, grouping rules, etc.
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['branch_id', 'report_type']);
        });

        // Create aging buckets configuration
        Schema::create('aging_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // receivable, payable
            $table->json('buckets'); // [{days_from: 0, days_to: 30, label: 'Current'}, ...]
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aging_configurations');
        Schema::dropIfExists('financial_report_configs');
        Schema::dropIfExists('fiscal_periods');

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropIndex(['dimension1']);
            $table->dropIndex(['dimension2']);
            $table->dropForeign(['currency_id']);
            $table->dropColumn([
                'dimension1', 'dimension2', 'currency_id', 'exchange_rate',
                'debit_base', 'credit_base',
            ]);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['source_module', 'source_type', 'source_id']);
            $table->dropIndex(['fiscal_year', 'fiscal_period']);
            $table->dropIndex(['status', 'entry_date']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['reversed_by_entry_id']);
            $table->dropColumn([
                'source_module', 'source_type', 'source_id', 'approved_by', 'approved_at',
                'fiscal_year', 'fiscal_period', 'is_auto_generated', 'is_reversible',
                'reversed_by_entry_id',
            ]);
        });

        Schema::dropIfExists('account_mappings');

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['currency_code']);
            $table->dropColumn([
                'currency_code', 'requires_currency', 'account_category',
                'sub_category', 'is_system_account', 'metadata',
            ]);
        });
    }
};
