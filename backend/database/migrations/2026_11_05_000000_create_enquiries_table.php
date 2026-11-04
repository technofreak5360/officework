<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            // No foreign key, because products live in an external source. The
            // title is copied so the record still reads correctly if that changes.
            $table->unsignedBigInteger('product_id');
            $table->string('product_title');
            $table->string('name', 120);
            $table->string('email', 254);
            $table->string('phone', 30)->nullable();
            $table->text('message')->nullable();
            $table->string('email_status', 20)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
