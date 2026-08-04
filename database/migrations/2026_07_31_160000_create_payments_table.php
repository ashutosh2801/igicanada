<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider')->default('paypal');
            $table->string('provider_order_id')->unique();
            $table->string('provider_capture_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('created')->index();
            $table->string('payer_email')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
