<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained('wiki_pages')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('editor_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_revisions');
    }
};
