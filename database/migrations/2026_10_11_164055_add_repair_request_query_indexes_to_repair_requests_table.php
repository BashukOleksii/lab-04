
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {

            $table->index(
                [
                    'department_id',
                    'status',
                    'reported_at',
                    'id'
                ],
                'idx_repairs_dep_status_date'
            );

            $table->index(
                [
                    'priority',
                    'reported_at',
                    'id'
                ],
                'idx_repairs_priority_date'
            );

            $table->index(
                [
                    'reported_at',
                    'id'
                ],
                'idx_repairs_reported_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {

            $table->dropIndex(
                'idx_repairs_dep_status_date'
            );

            $table->dropIndex(
                'idx_repairs_priority_date'
            );

            $table->dropIndex(
                'idx_repairs_reported_date'
            );
        });
    }
};
