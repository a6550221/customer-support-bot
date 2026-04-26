<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuickReply;
use Illuminate\Http\Request;

class QuickReplyController extends Controller
{
    public function index(Request $request)
    {
        $replies = QuickReply::where(fn($q) =>
            $q->where('is_global', true)->orWhere('agent_id', $request->user()->id)
        )->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $replies]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:100',
            'content'   => 'required|string',
            'is_global' => 'boolean',
        ]);

        $reply = QuickReply::create(array_merge(
            $request->only('title', 'content', 'is_global'),
            ['agent_id' => $request->user()->id]
        ));

        return response()->json(['code' => 201, 'message' => 'Created.', 'data' => $reply], 201);
    }

    public function show(QuickReply $quickReply)
    {
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $quickReply]);
    }

    public function update(Request $request, QuickReply $quickReply)
    {
        $request->validate([
            'title'   => 'sometimes|string|max:100',
            'content' => 'sometimes|string',
        ]);
        $quickReply->update($request->only('title', 'content', 'is_global'));

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $quickReply->fresh()]);
    }

    public function destroy(QuickReply $quickReply)
    {
        $quickReply->delete();
        return response()->json(['code' => 200, 'message' => 'Deleted.', 'data' => null]);
    }
}
