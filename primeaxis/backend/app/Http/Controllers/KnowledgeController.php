<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function index()
    {
        return response()->json([
            'code' => 200, 'message' => 'success',
            'data' => KnowledgeBase::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'     => 'required|in:faq,policy,template,guide',
            'question' => 'required|string|max:500',
            'answer'   => 'required|string',
        ]);

        $item = KnowledgeBase::create($request->only('type', 'question', 'answer'));

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $item = KnowledgeBase::findOrFail($id);
        $item->update($request->only('type', 'question', 'answer'));

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $item]);
    }

    public function destroy($id)
    {
        KnowledgeBase::findOrFail($id)->delete();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => null]);
    }
}
