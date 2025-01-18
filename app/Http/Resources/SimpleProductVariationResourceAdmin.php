<?php

namespace App\Http\Resources;


use App\Enums\Activity;
use App\Enums\Ask;
use App\Libraries\AppLibrary;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleProductVariationResourceAdmin extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'                            => $this->id,
            'product_attribute_id'          => (int) $this->product_attribute_id,
            'product_attribute_option_id'   => (int) $this->product_attribute_option_id,
            'product_attribute_name'        => $this->productAttribute?->name,
            'product_attribute_option_name' => $this->productAttributeOption?->name,
            'price'                         => Carbon::now()->between($this->product?->offer_start_date, $this->product?->offer_end_date) ? AppLibrary::convertAmountFormat($this->price - (($this->price / 100) * $this->product?->discount)) : AppLibrary::convertAmountFormat($this->price),
            'currency_price'                => Carbon::now()->between($this->product?->offer_start_date, $this->product?->offer_end_date) ? AppLibrary::currencyAmountFormat($this->price - (($this->price / 100) * $this->product?->discount)) : AppLibrary::currencyAmountFormat($this->price),
            'old_price'                     => AppLibrary::convertAmountFormat($this->price),
            'old_currency_price'            => AppLibrary::currencyAmountFormat($this->price),
            'discount'                      => Carbon::now()->between($this->product?->offer_start_date, $this->product?->offer_end_date) ? AppLibrary::convertAmountFormat(($this->price / 100) * $this->product?->discount) : 0,
            'discount_percentage'           => AppLibrary::convertAmountFormat($this->product?->discount),
            'sku'                           => $this->sku,
            'stock'                         => $this->product?->show_stock_out == Activity::DISABLE ? $this->product?->can_purchasable == Ask::NO ? (int)env('NON_PURCHASE_QUANTITY') : $this->stock_items_sum_quantity : 0,
        ];
    }
}