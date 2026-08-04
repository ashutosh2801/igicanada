<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('account_type', 'customer')
            ->update([
                'approval_status' => 'suspended',
                'approved_at' => null,
            ]);
    }

    public function down(): void
    {
        // Retail accounts are historical records; their former approval state is not safely inferable.
    }
};
