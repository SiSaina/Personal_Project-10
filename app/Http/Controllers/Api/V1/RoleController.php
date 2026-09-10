<?php

namespace App\Http\Controllers\Api\V1;

use App\Filter\V1\RoleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreRoleRequest;
use App\Http\Requests\V1\UpdateRoleRequest;
use App\Http\Resources\V1\RoleCollection;
use App\Http\Resources\V1\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = new RoleFilter;
        $filterItems = $filter->transform($request);

        $includeUsers = $request->query('includeUsers');

        $roles = Role::where($filterItems);
        if ($includeUsers) {
            $roles->with('users');
        }

        return new RoleCollection($roles
            ->orderBy('id', 'asc')
            ->paginate()
            ->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        return new RoleResource(Role::create($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $includeUsers = request()->query('includeUsers');
        if ($includeUsers) {
            $role->loadMissing('users');
        }

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->validated());

        return new RoleResource($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        abort_if($role->users()->exists(), 409, 'A role assigned to users cannot be deleted.');
        $role->delete();

        return response()->noContent();
    }
}
