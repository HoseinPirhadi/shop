<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoreSettingRequest;
use App\Http\Resources\StoreSettingResource;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;

class StoreSettingController extends Controller
{
    public function show(): StoreSettingResource
    {
        return new StoreSettingResource(StoreSetting::current());
    }

    public function update(UpdateStoreSettingRequest $request): StoreSettingResource
    {
        $settings = StoreSetting::current();
        $settings->update($request->validated());

        return new StoreSettingResource($settings->refresh());
    }

    public function publicSettings(): JsonResponse
    {
        $settings = StoreSetting::current();

        return response()->json([
            'data' => [
                'store_name' => $settings->store_name,
                'currency_code' => $settings->currency_code,
                'currency_configured' => $settings->hasConfiguredCurrency(),
                'checkout_enabled' => $settings->hasConfiguredCurrency(),
            ],
        ]);
    }
}
