<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role', 'department', 'status', 'avatar']);
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:admin,supervisor,agent',
            'department' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'department' => $request->department,
        ]);

        return response()->json(['code' => 201, 'message' => 'User created.', 'data' => $user], 201);
    }

    public function show(User $user)
    {
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'sometimes|string|max:100',
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'role'       => 'sometimes|in:admin,supervisor,agent',
            'department' => 'nullable|string|max:100',
            'password'   => 'nullable|string|min:8',
        ]);

        $data = $request->only('name', 'email', 'role', 'department');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $user->fresh()]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['code' => 200, 'message' => 'Deleted.', 'data' => null]);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:admin,supervisor,agent']);
        $user->update(['role' => $request->role]);
        return response()->json(['code' => 200, 'message' => 'Role updated.', 'data' => $user->fresh()]);
    }
}
