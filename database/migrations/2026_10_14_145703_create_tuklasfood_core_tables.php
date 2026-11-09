<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->string('name')->unique();
            $table->enum('base_unit', ['g', 'ml', 'pc']);
            $table->decimal('piece_weight_g', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('equipment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->unique(['user_id', 'equipment_id']);
        });

        Schema::create('user_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 10);
            $table->decimal('quantity_base', 12, 3);
            $table->date('purchase_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('storage_location')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'ingredient_id']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cuisine')->default('Filipino')->index();
            $table->unsignedTinyInteger('servings');
            $table->unsignedSmallInteger('prep_minutes');
            $table->unsignedSmallInteger('cook_minutes');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('easy');
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('instruction');
            $table->unsignedInteger('timer_seconds')->nullable();
            $table->unique(['recipe_id', 'position']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 10);
            $table->decimal('quantity_base', 12, 3);
            $table->boolean('is_optional')->default(false);
            $table->string('note')->nullable();
            $table->unique(['recipe_id', 'ingredient_id']);
        });

        Schema::create('recipe_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->unsignedTinyInteger('group_no');
            $table->unique(['recipe_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'recipe_equipment', 'recipe_ingredients', 'recipe_steps', 'recipes',
            'user_ingredients', 'equipment_user', 'equipment', 'ingredients', 'ingredient_categories',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};