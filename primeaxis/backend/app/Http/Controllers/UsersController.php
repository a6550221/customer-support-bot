<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::orderBy('role')->orderBy('name')->get();

        // Attach monthly order count
        $counts = Order::selectRaw('assignee_id, COUNT(*) as cnt')
            ->whereMonth('created_at', now()->month)
            ->groupBy('assignee_id')
            ->pluck('cnt', 'assignee_id');

        $data = $users->map(fn($u) => [
            'id'     => $u->id,
            'name'   => $u->name,
            'email'  => $u->email,
            'role'   => $u->role,
            'online' => $u->status === 'online',
            'active' => (bool) $u->active,
            'orders' => $counts[$u->id] ?? 0,
        ]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,supervisor,agent',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'offline',
            'active'   => true,
        ]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'online' => false,
            'active' => true,
            'orders' => 0,
        ]]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->has('active')) {
            $user->active = (bool) $request->active;
        }
        if ($request->has('role')) {
            $request->validate(['role' => 'in:admin,supervisor,agent']);
            $user->role = $request->role;
        }
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        $user->save();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $user]);
    }
}
