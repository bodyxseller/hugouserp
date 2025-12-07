<?php

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
        Schema::table('module_navigation', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['module_id']);
            
            // Make module_id nullable
            $table->foreignId('module_id')->nullable()->change()->constrained('modules')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_navigation', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['module_id']);
            
            // Make module_id not nullable again
            $table->foreignId('module_id')->change()->constrained('modules')->cascadeOnDelete();
        });
    }
};
