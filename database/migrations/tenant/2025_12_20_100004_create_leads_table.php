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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('company')->nullable();
            $table->string('position')->nullable(); // Job position

            // Location
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('website')->nullable();

            // Lead Details
            $table->text('needs')->nullable(); // Customer needs/requirements
            $table->decimal('lead_value', 15, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Relationships
            $table->foreignId('source_id')->constrained('lead_sources')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('lead_statuses')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('module_id')->nullable()->constrained('modules')->onDelete('set null');

            // Additional Info
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();

            // Tracking
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->boolean('is_converted')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index('email');
            $table->index('phone');
            $table->index('status_id');
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
