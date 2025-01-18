<?php

use App\Enums\Ask;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_serial_no')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('subtotal', 19, 6);
            $table->decimal('tax', 19, 6)->nullable()->default(0);
            $table->decimal('discount', 19, 6)->nullable()->default(0);
            $table->decimal('delivery_charge', 19, 6)->nullable()->default(0);
            $table->decimal('total', 19, 6);
            $table->tinyInteger('order_type')->default(OrderType::DELIVERY);
            $table->timestamp('order_datetime')->default(date('y-m-d h:m:s'));
            $table->bigInteger('payment_method')->default(PaymentGateway::CASH_ON_DELIVERY);
            $table->tinyInteger('payment_status')->default(PaymentStatus::UNPAID);
            $table->tinyInteger('pos_payment_method')->nullable();
            $table->string('pos_payment_note', 200)->nullable();
            $table->bigInteger('delivery_zone_id')->nullable();
            $table->tinyInteger('status');
            $table->bigInteger('delivery_boy_id')->nullable();
            $table->tinyInteger('active')->default(ASK::NO);
            $table->text('reason')->nullable();
            $table->decimal('edited_amount', 13, 3)->default(0);
            $table->timestamp('edited_date')->nullable();
            $table->string('source')->nullable();
            $table->string('creator_type')->nullable();
            $table->bigInteger('creator_id')->nullable();
            $table->string('editor_type')->nullable();
            $table->bigInteger('editor_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
