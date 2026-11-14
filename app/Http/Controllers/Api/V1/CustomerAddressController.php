<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Http\Resources\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return CustomerAddressResource::collection(
            $user->addresses()->orderByDesc('is_default')->orderBy('id')->get()
        );
    }

    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $address = DB::transaction(function () use ($data, $user): CustomerAddress {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $isDefault = ($data['is_default'] ?? false) || ! $lockedUser->addresses()->exists();

            if ($isDefault) {
                $lockedUser->addresses()->update(['is_default' => false]);
            }

            $data['is_default'] = $isDefault;

            return $lockedUser->addresses()->create($data);
        });

        return (new CustomerAddressResource($address))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        CustomerAddress $address,
    ): CustomerAddressResource {
        /** @var User $user */
        $user = $request->user();
        abort_unless($address->user_id === $user->id, 404);

        $data = $request->validated();
        $updated = DB::transaction(function () use ($data, $user, $address): CustomerAddress {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedAddress = $lockedUser->addresses()->whereKey($address->id)->lockForUpdate()->firstOrFail();

            if (array_key_exists('is_default', $data)) {
                if ($data['is_default']) {
                    $lockedUser->addresses()->whereKeyNot($lockedAddress->id)->update(['is_default' => false]);
                } elseif ($lockedAddress->is_default) {
                    $replacement = $lockedUser->addresses()
                        ->whereKeyNot($lockedAddress->id)
                        ->orderBy('id')
                        ->first();

                    if ($replacement !== null) {
                        $replacement->update(['is_default' => true]);
                    } else {
                        $data['is_default'] = true;
                    }
                }
            }

            $lockedAddress->update($data);

            return $lockedAddress->refresh();
        });

        return new CustomerAddressResource($updated);
    }

    public function destroy(Request $request, CustomerAddress $address): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($address->user_id === $user->id, 404);

        DB::transaction(function () use ($user, $address): void {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedAddress = $lockedUser->addresses()->whereKey($address->id)->lockForUpdate()->firstOrFail();
            $wasDefault = $lockedAddress->is_default;

            $lockedAddress->delete();

            if ($wasDefault) {
                $lockedUser->addresses()->orderBy('id')->first()?->update(['is_default' => true]);
            }
        });

        return response()->json(status: 204);
    }
}
