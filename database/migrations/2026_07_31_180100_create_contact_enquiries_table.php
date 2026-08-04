<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_enquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('company')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
