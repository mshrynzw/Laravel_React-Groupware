<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_rule_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('approval_rule_id')->nullable()->constrained('approval_rules')->nullOnDelete();
            $table->foreignId('approval_rule_version_id')->nullable()->constrained('approval_rule_versions')->nullOnDelete();
            $table->jsonb('resolved_steps');
            $table->jsonb('matched_context')->nullable();
            $table->timestamps();

            $table->unique('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_rule_resolutions');
    }
};
