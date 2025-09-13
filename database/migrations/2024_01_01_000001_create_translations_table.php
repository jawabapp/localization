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

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('locale', 10)->index();
                $table->string('group')->index();
                $table->string('key');
                $table->text('value')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['locale', 'group', 'key']);
                $table->index(['locale', 'group']);
            });
        } else {
            // Migrate from old schema if exists
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $connection = Schema::getConnection();
                $columns = $connection->getSchemaBuilder()->getColumnListing($tableName);

                // Add locale column if not exists
                if (!in_array('locale', $columns)) {
                    $table->string('locale', 10)->after('id')->default('en')->index();
                }

                // Rename language_code to locale if exists
                if (in_array('language_code', $columns) && !in_array('locale', $columns)) {
                    $table->renameColumn('language_code', 'locale');
                }

                // Add group column if not exists
                if (!in_array('group', $columns)) {
                    $table->string('group')->after('locale')->default('general')->index();
                }

                // Add metadata column if not exists
                if (!in_array('metadata', $columns)) {
                    $table->json('metadata')->nullable()->after('value');
                }

                // Update indexes
                try {
                    $table->index(['locale', 'group']);
                } catch (\Exception $e) {
                    // Index might already exist
                }
            });

            // Update existing data to extract group from key
            DB::table($tableName)->whereNull('group')->orWhere('group', '')->chunk(100, function ($translations) use ($tableName) {
                foreach ($translations as $translation) {
                    if (strpos($translation->key, '.') !== false) {
                        $parts = explode('.', $translation->key, 2);
                        DB::table($tableName)
                            ->where('id', $translation->id)
                            ->update([
                                'group' => $parts[0],
                                'key' => $parts[1] ?? $translation->key,
                            ]);
                    } else {
                        DB::table($tableName)
                            ->where('id', $translation->id)
                            ->update(['group' => '__JSON__']);
                    }
                }
            });
        }
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