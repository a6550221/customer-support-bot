<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

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
