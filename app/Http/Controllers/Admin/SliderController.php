<?php

namespace App\Http\Controllers\Admin;


use Exception;
use App\Models\Slider;
use App\Services\SliderService;
use App\Http\Requests\SliderRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\SliderResource;

class SliderController extends AdminController
{
    private SliderService $sliderService;

    public function __construct(SliderService $slider)
    {
        parent::__construct();
        $this->sliderService = $slider;
         $this->middleware(['permission:settings'])->only('store', 'update', 'destroy', 'show');
    }

    public function index(PaginateRequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return SliderResource::collection($this->sliderService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(SliderRequest $request): SliderResource | \Illuminate\Http\Response
    {
        try {
            return new SliderResource($this->sliderService->store($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function show(Slider $slider): SliderResource | \Illuminate\Http\Response
    {
        try {
            return new SliderResource($this->sliderService->show($slider));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(SliderRequest $request, Slider $slider): SliderResource | \Illuminate\Http\Response
    {
        try {
            return new SliderResource($this->sliderService->update($request, $slider));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(Slider $slider): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $this->sliderService->destroy($slider);
            return response('', 202);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
