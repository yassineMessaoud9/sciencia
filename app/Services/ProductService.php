<?php

namespace App\Services;


use Exception;
use App\Enums\Ask;
use App\Enums\BarcodeType;
use Carbon\Carbon;
use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\ProductTax;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Libraries\AppLibrary;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\PaginateRequest;
use App\Models\ProductAttributeOption;
use Picqer\Barcode\BarcodeGeneratorJPG;
use App\Http\Requests\ChangeImageRequest;
use App\Http\Requests\ProductOfferRequest;
use App\Http\Requests\ShippingAndReturnRequest;

class ProductService
{
    public object $product;
    protected array $productFilter = [
        'name',
        'sku',
        'slug',
        'buying_price',
        'selling_price',
        'product_category_id',
        'product_brand_id',
        'barcode_id',
        'tax_id',
        'unit_id',
        'show_stock_out',
        'status',
        'can_purchasable',
        'refundable',
        'weight',
        'order',
        'except'
    ];

    /**
     * @throws Exception
     */
    public function list(PaginateRequest $request)
    {
        try {
            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';

            return Product::with('media', 'category', 'brand', 'taxes', 'tags', 'unit')->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])->where(function ($query) use ($requests) {
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->productFilter)) {
                        if ($key == "except") {
                            $explodes = explode('|', $request);
                            if (count($explodes)) {
                                foreach ($explodes as $explode) {
                                    $query->where('id', '!=', $explode);
                                }
                            }
                        } else {
                            if ($key == "product_category_id") {
                                $query->where($key, $request);
                            } elseif ($key == "tax_id") {
                                $query->whereHas('taxes', function ($q) use ($key, $request) {
                                    $q->where($key, $request);
                                });
                            } else {
                                $query->where($key, 'like', '%' . $request . '%');
                            }
                        }
                    }
                }
            })->orderBy($orderColumn, $orderType)->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function store(ProductRequest $request): object
    {
        try {
            DB::transaction(function () use ($request) {
                if ($request->barcode_id === BarcodeType::EAN_13) {
                    $barcode_value = str_pad($request->sku, 12, '0', STR_PAD_LEFT);
                }
                if ($request->barcode_id === BarcodeType::UPC_A) {
                    $barcode_value = str_pad($request->sku, 11, '0', STR_PAD_LEFT);
                }

                $this->product = Product::create($request->validated() + ['slug' => Str::slug($request->name), 'variation_price' => $request->selling_price]);
                if ($request->tags) {
                    $tagItems = json_decode($request->tags, true);
                    foreach ($tagItems as $tagItem) {
                        ProductTag::create([
                            'product_id' => $this->product->id,
                            'name'       => $tagItem['text']
                        ]);
                    }
                }

                if ($request->tax_id) {
                    foreach ($request->tax_id as $tax) {
                        ProductTax::create([
                            'product_id' => $this->product->id,
                            'tax_id'     => $tax
                        ]);
                    }
                }

                $generator = new BarcodeGeneratorJPG();
                if ($this->product->barcode_id == BarcodeType::EAN_13) {
                    $barcode = $generator->getBarcode($barcode_value, $generator::TYPE_EAN_13);
                }
                if ($this->product->barcode_id == BarcodeType::UPC_A) {
                    $barcode = $generator->getBarcode($barcode_value, $generator::TYPE_UPC_A);
                }
                $tempFilePath = storage_path('app/public/barcode.jpg');
                file_put_contents($tempFilePath, $barcode);
                $this->product->addMedia($tempFilePath)->toMediaCollection('product-barcode');
            });
            return $this->product;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            DB::rollBack();
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(ProductRequest $request, Product $product): object
    {
        try {
            DB::transaction(function () use ($request, $product) {

                if ($request->barcode_id != $product->barcode_id) {
                    if ($request->barcode_id === BarcodeType::EAN_13) {
                        $barcode_value = str_pad($request->sku, 12, '0', STR_PAD_LEFT);
                    }
                    if ($request->barcode_id === BarcodeType::UPC_A) {
                        $barcode_value = str_pad($request->sku, 11, '0', STR_PAD_LEFT);
                    }
                    $product->update($request->validated() + ['slug' => Str::slug($request->name)]);

                    $generator = new BarcodeGeneratorJPG();
                    if ($product->barcode_id === BarcodeType::EAN_13) {
                        $barcode = $generator->getBarcode($barcode_value, $generator::TYPE_EAN_13);
                    }
                    if ($product->barcode_id === BarcodeType::UPC_A) {
                        $barcode = $generator->getBarcode($barcode_value, $generator::TYPE_UPC_A);
                    }
                    $tempFilePath = storage_path('app/public/barcode.jpg');
                    file_put_contents($tempFilePath, $barcode);
                    $product->clearMediaCollection('product-barcode');
                    $product->addMedia($tempFilePath)->toMediaCollection('product-barcode');
                } else {
                    $product->update($request->validated() + ['slug' => Str::slug($request->name)]);
                }

                if ($request->tags) {
                    $product->tags()->delete();
                    $tagItems = json_decode($request->tags, true);
                    foreach ($tagItems as $tagItem) {
                        ProductTag::create([
                            'product_id' => $product->id,
                            'name'       => $tagItem['text']
                        ]);
                    }
                }

                if ($request->tax_id) {
                    $product->taxes()->delete();
                    foreach ($request->tax_id as $tax) {
                        ProductTax::create([
                            'product_id' => $product->id,
                            'tax_id'     => $tax
                        ]);
                    }
                }

                if (!$request->tax_id) {
                    $product->taxes()->delete();
                }

                if ($product->variations) {
                    $this->product = Product::find($product->id);
                    $checkMinPrice = $product->variations->min('price');
                    if ($checkMinPrice) {
                        $this->product->variation_price = $checkMinPrice;
                        $this->product->save();
                    }
                }
            });
            return $this->product;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            DB::rollBack();
            throw new Exception($exception->getMessage(), 422);
        }
    }

    public function downloadBarcode(Product $product)
    {
        return $product->getMedia('product-barcode')->first();
    }

    /**
     * @throws Exception
     */
    public function destroy(Product $product): void
    {
        try {
            DB::transaction(function () use ($product) {
                if ($product->productTaxes) {
                    $product->productTaxes()->delete();
                }
                $product->delete();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            DB::rollBack();
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function show(Product $product): Product
    {
        try {
            return $product;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function barcodeProduct($barcode)
    {
        try {
            $productVariation = ProductVariation::where(['sku' => $barcode])->first();
            $product = Product::where(['sku' => $barcode])->first();

            if ($productVariation || $product) {
                if ($productVariation) {
                    return [
                        'product_id'   => $productVariation->product_id,
                        'variation_id' => $productVariation->id
                    ];
                }
                if ($product) {
                    return [
                        'product_id'   => $product->id,
                        'variation_id' => ''
                    ];
                }
            } else {
                throw new Exception(trans('all.message.product_match'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function uploadImage(ChangeImageRequest $request, Product $product): Product
    {
        try {

            if ($request->image) {
                $product->clearMediaCollection('product');
                $product->addMedia($request->image)->toMediaCollection('product');
            }
            return $product;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function deleteImage(Product $product, $index): Product
    {
        try {
            $images = $product->getMedia('product');
            if (isset($images[$index])) {
                $images[$index]->delete();
            }
            return Product::find($product->id);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }


    /**
     * @throws Exception
     */
    public function mostPopularProducts(PaginateRequest $request)
    {
        try {

            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 32) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';
            $rand        = $request->get('rand', 0) > 0 ? $request->get('rand') : 0;

            return Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status', 'products.unit_id')
                ->with(['unit', 'wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->withCount('orderCountable')
                ->where(['status' => Status::ACTIVE])
                ->orderBy('order_countable_count', 'desc')
                ->randAndLimitOrOrderBy($rand, $orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function productReport(PaginateRequest $request)
    {
        try {
            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            return Product::withCount('orders')->where(function ($query) use ($requests) {
                if (isset($requests['from_date']) && isset($requests['to_date'])) {
                    $first_date = date('Y-m-d', strtotime($requests['from_date']));
                    $last_date  = date('Y-m-d', strtotime($requests['to_date']));
                    $query->whereDate('created_at', '>=', $first_date)->whereDate(
                        'created_at',
                        '<=',
                        $last_date
                    );
                }
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->productFilter)) {
                        if ($key == "except") {
                            $explodes = explode('|', $request);
                            if (count($explodes)) {
                                foreach ($explodes as $explode) {
                                    $query->where('id', '!=', $explode);
                                }
                            }
                        } else {
                            $query->where($key, 'like', '%' . $request . '%');
                        }
                    }
                }
            })->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function generateSku()
    {
        try {
            return AppLibrary::sku(rand(1000000, 9999999));
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function productOffer(ProductOfferRequest $request, Product $product): object
    {
        try {
            DB::transaction(function () use ($request, $product) {
                $this->product              = $product;
                $product->add_to_flash_sale = $request->add_to_flash_sale;
                $product->discount          = $request->discount;
                $product->offer_start_date  = date('Y-m-d H:i:s', strtotime($request->offer_start_date));
                $product->offer_end_date    = date('Y-m-d H:i:s', strtotime($request->offer_end_date));
                $product->save();
            });
            return $this->product;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function categoryWiseProducts(Request $request): \Vanilla\Support\Collection|\IlluminateAgnostic\Str\Support\Collection|\IlluminateAgnostic\Collection\Support\Collection|\IlluminateAgnostic\StrAgnostic\Str\Support\Collection|\IlluminateAgnostic\ArrAgnostic\Arr\Support\Collection|\Illuminate\Support\Collection|\IlluminateAgnostic\Arr\Support\Collection
    {
        try {
            $customProductFilter = [
                'name',
                'sku',
                'slug',
                'status'
            ];

            $customProductFilterMask = [
                'name'   => 'products.name',
                'sku'    => 'products.sku',
                'slug'   => 'products.slug',
                'status' => 'products.status'
            ];

            $categories = [];
            if ($request->has('category')) {
                if (!blank($request->category)) {
                    $categories = ProductCategory::where(['slug' => $request->category])->first();
                    if ($categories) {
                        $categories = $categories->descendantsAndSelf->toArray();
                    } else {
                        $categories = [];
                    }
                }
            }

            $productCategory = Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.status', 'products.product_category_id', 'products.product_brand_id', 'products.variation_price')->with('brand', 'variations')->where(function ($query) use ($request, $categories) {
                if (count($categories)) {
                    $i = 0;
                    foreach ($categories as $category) {
                        if ($i === 0) {
                            $query->where('product_category_id', $category['id']);
                        } else {
                            $query->orWhere('product_category_id', $category['id']);
                        }
                        $i++;
                    }
                }
            })->where(function ($query) use ($request, $customProductFilter, $customProductFilterMask) {
                foreach ($request->all() as $key => $req) {
                    if (in_array($key, $customProductFilter)) {
                        $query->where($customProductFilterMask[$key], 'like', '%' . $req . '%');
                    }
                }
            })->get();

            $perPage     = $request->post('per_page', 30);
            $orderColumn = 'products.name';
            $orderType   = 'asc';
            if ($request->post('sort_by') == 'newest') {
                $orderColumn = 'id';
                $orderType   = 'desc';
            } else if ($request->post('sort_by') == 'price_low_to_high') {
                $orderColumn = 'products.variation_price';
            } else if ($request->post('sort_by') == 'price_high_to_low') {
                $orderColumn = 'products.variation_price';
                $orderType   = 'desc';
            } else if ($request->post('sort_by') == 'top_rated') {
                $orderColumn = 'rating_star';
                $orderType   = 'desc';
            }

            $products = Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.product_category_id', 'products.product_brand_id', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status')
                ->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->with('media', 'brand', 'variations')
                ->where(function ($query) use ($request, $categories) {
                    if (count($categories)) {
                        $i = 0;
                        foreach ($categories as $category) {
                            if ($i === 0) {
                                $query->where('product_category_id', $category['id']);
                            } else {
                                $query->orWhere('product_category_id', $category['id']);
                            }
                            $i++;
                        }
                    }
                })->where(function ($query) use ($request) {
                    if (!blank($request->brand)) {
                        $brands = json_decode($request->brand);
                        if (count($brands)) {
                            $i = 0;
                            foreach ($brands as $brand) {
                                if ($i === 0) {
                                    $query->where('product_brand_id', $brand);
                                } else {
                                    $query->orWhere('product_brand_id', $brand);
                                }
                                $i++;
                            }
                        }
                    }
                })->where(function ($query) use ($request, $customProductFilter, $customProductFilterMask) {
                    foreach ($request->all() as $key => $req) {
                        if (in_array($key, $customProductFilter)) {
                            $query->where($customProductFilterMask[$key], 'like', '%' . $req . '%');
                        }
                    }
                })->where(function ($query) use ($request) {
                    if (!blank($request->variation)) {
                        $variations = json_decode($request->variation);
                        if (count($variations)) {
                            $arrays = [];
                            foreach ($variations as $variation) {
                                $arrays[$variation->attribute][] = [
                                    'option' => $variation->option
                                ];
                            }

                            foreach ($arrays as $key => $array) {
                                $query->whereHas('variations', function ($q) use ($key, $array) {
                                    $i = 0;
                                    foreach ($array as $a) {
                                        if ($i === 0) {
                                            $q->where('product_attribute_id', $key)->where('product_attribute_option_id', $a['option']);
                                        } else {
                                            $q->orWhere('product_attribute_id', $key)->where('product_attribute_option_id', $a['option']);
                                        }
                                        $i++;
                                    }
                                });
                            }
                        }
                    }
                })->orderBy($orderColumn, $orderType)->where(function ($query) use ($request) {
                    if ($request->min_price >= 0 && $request->max_price > 0) {
                        $query->whereBetween('variation_price', [$request->min_price, $request->max_price]);
                    }
                })->paginate($perPage);

            $variations = $productCategory->map(function ($query) {
                return $query->variations;
            });

            $variationArray         = [];
            $productAttributeOption = ProductAttributeOption::get()->pluck('name', 'id')->toArray();
            if ($variations) {
                foreach ($variations->toArray() as $variation) {
                    if (count($variation)) {
                        foreach ($variation as $v) {
                            if (isset($variationArray[Str::slug($v['product_attribute']['name'], '_')])) {
                                $status = true;
                                foreach ($variationArray[Str::slug($v['product_attribute']['name'], '_')] as $va) {
                                    if ($v['product_attribute_option_id'] == $va['product_attribute_option_id']) {
                                        $status = false;
                                    }
                                }
                                if ($status) {
                                    $variationArray[Str::slug($v['product_attribute']['name'], '_')][] = [
                                        'attribute_name'              => $v['product_attribute']['name'],
                                        'attribute_option_name'       => isset($productAttributeOption[$v['product_attribute_option_id']]) ? $productAttributeOption[$v['product_attribute_option_id']] : '',
                                        'product_attribute_id'        => (int) $v['product_attribute_id'],
                                        "product_attribute_option_id" => (int) $v['product_attribute_option_id'],
                                    ];
                                }
                            } else {
                                $variationArray[Str::slug($v['product_attribute']['name'], '_')][] = [
                                    'attribute_name'              => $v['product_attribute']['name'],
                                    'attribute_option_name'       => isset($productAttributeOption[$v['product_attribute_option_id']]) ? $productAttributeOption[$v['product_attribute_option_id']] : '',
                                    'product_attribute_id'        => (int) $v['product_attribute_id'],
                                    "product_attribute_option_id" => (int) $v['product_attribute_option_id']
                                ];
                            }
                        }
                    }
                }
            }

            return collect([
                'products'   => $products,
                'brands'     => $productCategory->map(function ($query) {
                    return $query->brand;
                })->whereNotNull('id')->unique('id')->values()->all(),
                'variations' => $variationArray,
                'max_price'  => ceil($productCategory->max('variation_price') + 50),
            ]);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function flashSaleProducts(PaginateRequest $request)
    {
        try {
            $now         = Carbon::now();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 32) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';
            $rand        = $request->get('rand', 0) > 0 ? $request->get('rand') : 0;

            return Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status', 'products.unit_id')
                ->with(['unit', 'wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->with('media', 'variations')
                ->active('products.status')
                ->where('products.add_to_flash_sale', Ask::YES)
                ->where('products.offer_start_date', '<=', $now)
                ->where('products.offer_end_date', '>=', $now)
                ->randAndLimitOrOrderBy($rand, $orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function offerProducts(PaginateRequest $request)
    {
        try {
            $now         = Carbon::now();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 32) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';
            $rand        = $request->get('rand', 0) > 0 ? $request->get('rand') : 0;

            return Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status')
                ->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->with('media', 'variations')
                ->active('products.status')
                ->where('products.offer_start_date', '<=', $now)
                ->where('products.offer_end_date', '>=', $now)
                ->randAndLimitOrOrderBy($rand, $orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    public function dailyDealsProducts(PaginateRequest $request)
    {
        try {
            $today       = Carbon::today()->toDateString();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 32) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';
            $rand        = $request->get('rand', 0) > 0 ? $request->get('rand') : 0;

            return Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status')
                ->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->with('media', 'variations')
                ->active('products.status')
                ->whereDate('products.offer_start_date', $today)
                ->whereDate('products.offer_end_date', $today)
                ->randAndLimitOrOrderBy($rand, $orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function showWithRelation(Product $product, Request $request)
    {
        try {
            return Product::with('media', 'category', 'unit', 'taxes')
                ->with(['seo' => fn($query) => $query->with('media')])
                ->withSum('stockItems', 'quantity')
                ->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->where(['id' => $product->id, 'status' => Status::ACTIVE])
                ->first();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    public function showWithTrashed(Product $product, Request $request)
    {
        try {
            return Product::with('media', 'category', 'unit', 'taxes')
                ->with(['seo' => fn($query) => $query->with('media')])
                ->withSum('stockItems', 'quantity')
                ->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                ->where(['id' => $product->id, 'status' => Status::ACTIVE])
                ->withTrashed()
                ->first();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function relatedProducts(Product $product, PaginateRequest $request)
    {
        try {
            $productTags = $product->tags;
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 32) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';
            $rand        = $request->get('rand', 0) > 0 ? $request->get('rand') : 0;

            if (count($productTags) > 0) {
                return Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status')
                    ->with(['wishlist' => fn($query) => $query->where('user_id', Auth::check() ? Auth::user()->id : 0)])
                    ->with('media', 'variations', 'tags')
                    ->active('products.status')
                    ->whereHas('tags', function ($query) use ($productTags) {
                        if (count($productTags) > 0) {
                            $i = 0;
                            foreach ($productTags as $productTag) {
                                if ($i === 0) {
                                    $query->where('name', 'like', '%' . $productTag->name . '%');
                                } else {
                                    $query->orWhere('name', 'like', '%' . $productTag->name . '%');
                                }
                                $i++;
                            }
                        }
                        return $query;
                    })
                    ->whereNot('id', $product->id)
                    ->randAndLimitOrOrderBy($rand, $orderColumn, $orderType)
                    ->$method($methodValue);
            } else {
                return collect([]);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }


    /**
     * @throws Exception
     */
    public function wishlistProducts(PaginateRequest $request)
    {
        try {
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 32) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';
            $rand        = $request->get('rand', 0) > 0 ? $request->get('rand') : 0;

            return Product::select('products.id', 'products.name', 'products.sku', 'products.slug', 'products.selling_price', 'products.variation_price', 'products.add_to_flash_sale', 'products.offer_start_date', 'products.offer_end_date', 'products.discount', 'products.status', 'products.unit_id')
                ->with('unit', 'media', 'variations', 'wishlist')
                ->whereHas('wishlist', function ($query) {
                    return $query->where('user_id', Auth::user()->id);
                })
                ->active('products.status')
                ->randAndLimitOrOrderBy($rand, $orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function purchasableProducts()
    {
        try {
            return Product::select('id', 'name', 'buying_price', 'can_purchasable', 'status', 'sku')
                ->with('productTaxes')
                ->with('variations')
                ->where('can_purchasable', ASK::YES)
                ->where('status', Status::ACTIVE)
                ->orderBy('name', 'asc')
                ->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function simpleProducts()
    {
        try {
            return Product::select('id', 'name', 'buying_price', 'status', 'sku')
                ->with('productTaxes')
                ->with('variations')
                ->orderBy('name', 'asc')
                ->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function topProducts()
    {
        try {
            return Product::withCount('orderCountable')->where(['status' => Status::ACTIVE])->orderBy('order_countable_count', 'desc')->limit(10)->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function ancestorCategoryWiseProducts(ProductCategory $category, $rand = null)
    {

        try {
            $categories = [];
            if (!blank($category)) {
                $categories = ProductCategory::where(['id' => $category->id])->first();
                if ($categories) {
                    $categories = $categories->descendantsAndSelf->toArray();
                } else {
                    $categories = [];
                }
            }

            return Product::select('id', 'name', 'sku', 'slug', 'status', 'product_category_id')
                ->where(function ($query) use ($categories) {
                    if (count($categories)) {
                        $i = 0;
                        foreach ($categories as $category) {
                            if ($i === 0) {
                                $query->where('product_category_id', $category['id']);
                            } else {
                                $query->orWhere('product_category_id', $category['id']);
                            }
                            $i++;
                        }
                    }
                })
                ->randAndLimitOrOrderBy($rand, 'id', 'asc')
                ->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }
}
