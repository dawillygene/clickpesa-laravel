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
        Schema::create('clickpesa_webhooks', function (Blueprint $table) {
            $table->id();
            
            // Link to transaction
            $table->string('order_reference')->index()->comment('Links to transaction');
            
            // Webhook Details
            $table->string('event_type')->nullable()->comment('payment.success, payout.failed, etc');
            $table->json('payload')->comment('Raw webhook data from Clickpesa');
            $table->json('headers')->nullable()->comment('Request headers including signature');
            
            // Processing Status
            $table->boolean('verified')->default(false)->comment('Whether signature was verified');
            $table->timestamp('processed_at')->nullable()->comment('When webhook was successfully handled');
            $table->text('processing_error')->nullable()->comment('Error message if processing failed');
            $table->integer('retry_count')->default(0)->comment('Number of processing attempts');
            
            // Audit
            $table->timestamps();
            
            // Indexes
            $table->index('verified');
            $table->index('processed_at');
            $table->index(['order_reference', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clickpesa_webhooks');
    }
};
