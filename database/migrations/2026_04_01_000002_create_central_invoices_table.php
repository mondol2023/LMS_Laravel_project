<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remote_invoice_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'overdue', 'cancelled'])->default('unpaid');
            $table->json('data')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index('remote_invoice_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_invoices');
    }
};
