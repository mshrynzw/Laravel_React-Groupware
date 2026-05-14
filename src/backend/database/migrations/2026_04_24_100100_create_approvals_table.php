<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result')->nullable();
            $table->text('comment')->nullable();
            $table->dateTimeTz('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['request_id', 'step_order']);
            $table->index(['approver_user_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
