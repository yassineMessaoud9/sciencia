<?php

use App\Enums\Ask;
use App\Enums\Status;
use App\Enums\Activity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories');
            $table->foreignId('product_brand_id')->nullable()->constrained('product_brands');
            $table->foreignId('barcode_id')->nullable()->constrained('barcodes');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->unsignedDecimal('buying_price', 19, 6)->default(0);
            $table->unsignedDecimal('selling_price', 19, 6)->default(0);
            $table->unsignedDecimal('variation_price', 19, 6)->default(0);
            $table->tinyInteger('status')->default(Status::ACTIVE);
            $table->bigInteger('order')->default(1);
            $table->tinyInteger('can_purchasable')->default(Ask::YES);
            $table->tinyInteger('show_stock_out')->default(Activity::ENABLE);
            $table->unsignedBigInteger('maximum_purchase_quantity')->default(1);
            $table->unsignedBigInteger('low_stock_quantity_warning')->default(1);
            $table->string('weight')->nullable();
            $table->tinyInteger('refundable')->default(Ask::YES);
            $table->tinyInteger('sell_by_fraction')->default(Ask::NO);
            $table->longText('description')->nullable();
            $table->unsignedTinyInteger('add_to_flash_sale')->default(Ask::NO);
            $table->unsignedDecimal('discount', 19, 6)->default(0);
            $table->dateTime('offer_start_date')->nullable();
            $table->dateTime('offer_end_date')->nullable();
            $table->string('creator_type',)->nullable();
            $table->bigInteger('creator_id',)->nullable();
            $table->string('editor_type',)->nullable();
            $table->bigInteger('editor_id',)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};