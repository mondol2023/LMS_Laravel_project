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
        Schema::create('payment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained('user_subscriptions')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('subscription_packages')->onDelete('cascade');

            // Payment Details
            $table->decimal('package_price', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'due']);

            // Dates
            $table->date('payment_date');
            $table->date('due_date')->nullable();
            $table->date('expiry_date');

            // Meta
            $table->string('payment_method', 50)->nullable();
            $table->text('transaction_note')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'payment_status']);
            $table->index('due_date');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_history');
    }
};
