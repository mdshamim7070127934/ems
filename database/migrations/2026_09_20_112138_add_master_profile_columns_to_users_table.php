<?php

use App\Enums\Master\Status;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('username', 250)->nullable()->unique()->after('last_name');
            $table->unsignedInteger('role_id')->nullable()->after('username');
            $table->unsignedBigInteger('reporting_person_id')->nullable()->after('role_id');
            $table->integer('branch_id')->nullable()->after('reporting_person_id');
            $table->json('extra_branch_ids')->nullable()->after('branch_id');
            $table->json('exclude_branch_ids')->nullable()->after('extra_branch_ids');
            $table->enum('status', Status::cases())->default(Status::Active)->after('exclude_branch_ids');
            $table->boolean('is_available')->default(true)->after('status');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_available');
            $table->unsignedBigInteger('last_modified_by')->nullable()->after('created_by');
            $table->dateTime('last_login')->nullable()->after('last_modified_by');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->restrictOnDelete();
            $table->foreign('reporting_person_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('branch_id')
                ->references('id')
                ->on('mst_branch')
                ->restrictOnDelete();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('last_modified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['reporting_person_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['last_modified_by']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'username',
                'role_id',
                'reporting_person_id',
                'branch_id',
                'extra_branch_ids',
                'exclude_branch_ids',
                'status',
                'is_available',
                'created_by',
                'last_modified_by',
                'last_login',
            ]);
        });
    }
};
