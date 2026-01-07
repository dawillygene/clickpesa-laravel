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
        Schema::create('clickpesa_transactions', function (Blueprint $table) {
            $table->id();
            
            // Core Identity & Money Fields
            $table->enum('type', ['payment', 'payout'])->comment('Transaction direction: incoming or outgoing');
            $table->string('channel')->comment('ussd_push, card, mobile_money, bank_transfer');
            $table->string('order_reference')->unique()->comment('Your unique order reference');
            $table->decimal('amount', 18, 2)->comment('Transaction amount');
            $table->string('currency', 3)->comment('Currency code (TZS, USD, etc)');
            $table->string('status')->index()->comment('pending, successful, failed, processing, authorized, reversed');
            
            // Tracking Fields
            $table->string('reference')->nullable()->comment('Clickpesa internal reference ID');
            $table->string('description')->nullable()->comment('Transaction description');
            $table->json('account_details')->nullable()->comment('Other party details (phone, bank account, etc)');
            $table->json('metadata')->nullable()->comment('App-specific data (user_id, invoice_id, etc)');
            
            // Financial Details
            $table->decimal('fee', 18, 2)->nullable()->comment('Transaction fee amount');
            $table->string('fee_bearer')->nullable()->comment('Who pays the fee: merchant or customer');
            $table->boolean('exchanged')->default(false)->comment('Whether currency exchange was applied');
            $table->json('exchange_details')->nullable()->comment('Exchange rate information');
            
            // API Communication
            $table->string('channel_provider')->nullable()->comment('Provider name (MPESA, NMB Bank, etc)');
            $table->string('response_code')->nullable()->comment('API response status code');
            $table->text('response_message')->nullable()->comment('API response message');
            $table->json('request_payload')->nullable()->comment('Sanitized request data sent to API');
            $table->json('response_payload')->nullable()->comment('Full response data from API');
            
            // Audit Fields
            $table->timestamp('processed_at')->nullable()->comment('When transaction was actually processed');
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('type');
            $table->index('channel');
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clickpesa_transactions');
    }
};
