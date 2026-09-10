<?php

namespace App\Http\Controllers\Api\V1;

use App\Filter\V1\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserCollection;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = new UserFilter;
        $filterItems = $filter->transform($request);

        $includeAddresses = $request->query('includeAddresses');

        $users = User::where($filterItems);
        if ($includeAddresses) {
            $users->with('addresses');
        }

        return new UserCollection($users
            ->paginate()
            ->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        return new UserResource(User::create($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $includeAddresses = request()->query('includeAddresses');
        if ($includeAddresses) {
            $user->loadMissing('addresses');
        }

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}
