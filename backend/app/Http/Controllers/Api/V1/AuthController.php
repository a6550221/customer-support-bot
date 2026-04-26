<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['code' => 401, 'message' => '帳號或密碼錯誤', 'data' => null], 401);
        }

        $user->update(['status' => 'online']);
        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLog::record('login', 'user', $user->id);

        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => ['token' => $token, 'user' => $user],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->update(['status' => 'offline']);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['code' => 200, 'message' => 'Logged out.', 'data' => null]);
    }

    public function me(Request $request)
    {
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'   => 'sometimes|string|max:100',
            'avatar' => 'sometimes|url|nullable',
        ]);

        $request->user()->update($request->only('name', 'avatar'));

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $request->user()->fresh()]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status' => 'required|in:online,busy,offline']);
        $request->user()->update(['status' => $request->status]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => ['status' => $request->status]]);
    }
}
