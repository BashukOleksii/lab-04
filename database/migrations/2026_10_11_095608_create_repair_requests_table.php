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
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            
            $table->string('subject',200);
            $table->unsignedTinyInteger('priority')->default(2);
            $table->string('status',20)->default('open');
            $table->boolean('urgent')->default(false);
            
            $table->dateTime('reported_at');

            $table->decimal('cost',10,2)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('defect_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
