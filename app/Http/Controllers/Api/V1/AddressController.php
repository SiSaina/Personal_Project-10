<?php

namespace App\Http\Controllers\Api\V1;

use App\Filter\V1\AddressFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreAddressRequest;
use App\Http\Requests\V1\UpdateAddressRequest;
use App\Http\Resources\V1\AddressCollection;
use App\Http\Resources\V1\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = new AddressFilter;
        $filterItems = $filter->transform($request);

        $query = Address::where($filterItems);
        if (! $this->isStaff($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return new AddressCollection($query->paginate()->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddressRequest $request)
    {
        $data = $request->validated();
        if (! $this->isStaff($request) || ! isset($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        return new AddressResource(Address::create($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Address $address)
    {
        $this->authorizeOwner($request, $address);

        return new AddressResource($address);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address)
    {
        $this->authorizeOwner($request, $address);
        $data = $request->validated();
        if (! $this->isStaff($request)) {
            unset($data['user_id']);
        }
        $address->update($data);

        return new AddressResource($address);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Address $address)
    {
        $this->authorizeOwner($request, $address);
        $address->delete();

        return response()->noContent();
    }

    private function isStaff(Request $request): bool
    {
        return in_array($request->user()->role?->role_type, ['Admin', 'Employee'], true);
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($this->isStaff($request) || $address->user_id === $request->user()->id, 403);
    }
}
