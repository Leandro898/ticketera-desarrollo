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
        Schema::table('users', function (Blueprint $table) {
            // Asegúrate de que estas columnas no existan antes de añadirlas
            if (!Schema::hasColumn('users', 'mp_refresh_token')) {
                $table->text('mp_refresh_token')->nullable()->after('mp_access_token'); // O donde quieras
            }
            if (!Schema::hasColumn('users', 'mp_expires_in')) {
                $table->timestamp('mp_expires_in')->nullable()->after('mp_user_id'); // O donde quieras
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mp_refresh_token')) {
                $table->dropColumn('mp_refresh_token');
            }
            if (Schema::hasColumn('users', 'mp_expires_in')) {
                $table->dropColumn('mp_expires_in');
            }
        });
    }
};
