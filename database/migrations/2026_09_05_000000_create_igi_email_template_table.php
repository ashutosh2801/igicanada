<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('igi_email_template')) {
            return;
        }

        Schema::create('igi_email_template', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 200);
            $table->string('subject', 500);
            $table->text('content');
            $table->integer('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('igi_email_template');
    }
};
