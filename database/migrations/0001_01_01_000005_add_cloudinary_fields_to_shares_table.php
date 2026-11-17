<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shares')) {
            Schema::table('shares', function (Blueprint $table) {
                if (!Schema::hasColumn('shares', 'cloudinary_public_id')) {
                    $table->string('cloudinary_public_id', 255)->nullable()->after('external_url');
                }
                if (!Schema::hasColumn('shares', 'cloudinary_resource_type')) {
                    $table->string('cloudinary_resource_type', 20)->nullable()->after('cloudinary_public_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shares')) {
            Schema::table('shares', function (Blueprint $table) {
                $cols = array_filter(['cloudinary_public_id', 'cloudinary_resource_type'], fn ($c) => Schema::hasColumn('shares', $c));
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

