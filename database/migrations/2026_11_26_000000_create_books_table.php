<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('author', 120);
            $table->string('isbn', 13)->unique();
            $table->text('description');
            $table->string('category', 60)->index();
            $table->unsignedSmallInteger('publication_year');
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();
            $table->index('title');
            $table->index('author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
