<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Re-activate any retail accounts that the wholesale-only enforcement migration had suspended,
        // so returning retail customers can sign in again.
        DB::table('users')
            ->where('account_type', 'retail')
            ->where('approval_status', 'suspended')
            ->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Re-suspend retail accounts to restore the previous wholesale-only state.
        DB::table('users')
            ->where('account_type', 'retail')
            ->update([
                'approval_status' => 'suspended',
                'approved_at' => null,
            ]);
    }
};