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
        $tableName = config('localization.database.table', 'translations');
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->index();
            $table->string('namespace')->nullable()->index();
            $table->string('group')->index();
            $table->string('key');
            $table->text('value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['locale', 'namespace', 'group', 'key'], 'translations_unique');
            $table->index(['locale', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('localization.database.table', 'translations');
        Schema::dropIfExists($tableName);
    }
};