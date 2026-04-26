<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('email', 'like', "%{$request->search}%")
        )->withCount('tickets')->cursorPaginate(20);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $customers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'nullable|email|unique:customers,email',
            'phone'   => 'nullable|string|max:30',
            'company' => 'nullable|string|max:100',
            'tags'    => 'nullable|array',
        ]);

        $customer = Customer::create($request->only('name', 'email', 'phone', 'company', 'tags'));

        return response()->json(['code' => 201, 'message' => 'Customer created.', 'data' => $customer], 201);
    }

    public function show(Customer $customer)
    {
        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => $customer->load(['tickets' => fn($q) => $q->latest()->limit(10)]),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'    => 'sometimes|string|max:100',
            'email'   => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone'   => 'nullable|string|max:30',
            'company' => 'nullable|string|max:100',
            'tags'    => 'nullable|array',
        ]);

        $customer->update($request->only('name', 'email', 'phone', 'company', 'tags'));

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $customer->fresh()]);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['code' => 200, 'message' => 'Customer deleted.', 'data' => null]);
    }
}
