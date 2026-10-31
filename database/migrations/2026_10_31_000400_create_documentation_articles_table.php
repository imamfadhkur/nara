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
        Schema::create('documentation_articles', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('category_name');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon', 16)->default('📖');
            $table->text('excerpt');
            $table->longText('content');
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->string('last_updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_articles');
    }
};
