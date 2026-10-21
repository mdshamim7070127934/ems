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
        Schema::create('mst_dropdown_option_values', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('option_type_id');
            $table->integer('module_id');
            $table->integer('parent_id')->default(0);
            $table->string('option_value', 100);
            $table->integer('position')->nullable();
            $table->text('user_access_id')->nullable();
            $table->string('master_type')->nullable()->comment('Master Type Service/Product');
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->dateTime('created_on')->useCurrent();
            $table->dateTime('updated_on')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['option_type_id', 'option_value']);
            $table->index('module_id');
            $table->index('parent_id');
            $table->foreign('option_type_id')
                ->references('option_type_id')
                ->on('mst_dropdown')
                ->restrictOnDelete();
            $table->foreign('module_id')
                ->references('module_id')
                ->on('mst_module')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_dropdown_option_values');
    }
};
