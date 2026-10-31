<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NULL = acceso a todas las sucursales (administrador)
            $table->foreignId('sucursal_id')->nullable()->after('id')
                ->constrained('sucursales')->restrictOnDelete();
            $table->string('telefono', 20)->nullable()->after('email');
            // Los usuarios se desactivan, nunca se borran: tienen historial en las órdenes.
            $table->boolean('activo')->default(true)->after('password');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sucursal_id');
            $table->dropColumn(['telefono', 'activo', 'deleted_at']);
        });
    }
};
