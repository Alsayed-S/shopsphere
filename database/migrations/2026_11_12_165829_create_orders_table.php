
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Order owner
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            // Unique order reference
            $table->string('order_number')->unique();

            // Order and payment status
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');

            // Shipping information snapshot
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->text('shipping_address');

            // Order amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
