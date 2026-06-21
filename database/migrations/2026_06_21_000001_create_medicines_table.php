<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name_en');
            $table->string('name_fa')->nullable();
            $table->boolean('is_global')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Prevents a user from creating two medicines with the same name_en.
            // MySQL's default utf8mb4_unicode_ci collation is case-insensitive,
            // so this index also catches case variants (Aspirin vs aspirin).
            $table->unique(['user_id', 'name_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
