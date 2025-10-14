<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, convert all empty strings to NULL in the nida_number column
        DB::table('fundis')
            ->where('nida_number', '')
            ->update(['nida_number' => null]);

        // Optional: If you want to be extra thorough, also handle whitespace
        DB::table('fundis')
            ->whereRaw('TRIM(nida_number) = ""')
            ->update(['nida_number' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert NULL back to empty strings (if you want to reverse)
        DB::table('fundis')
            ->whereNull('nida_number')
            ->update(['nida_number' => '']);
    }
};
