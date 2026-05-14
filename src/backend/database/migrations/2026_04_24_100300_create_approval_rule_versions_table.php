<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_rule_id')->constrained('approval_rules')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->jsonb('conditions');
            $table->jsonb('steps');
            $table->boolean('is_published')->default(false);
            $table->dateTimeTz('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['approval_rule_id', 'version_no']);
            $table->index(['approval_rule_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_rule_versions');
    }
};
