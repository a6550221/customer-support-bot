<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $articles = KnowledgeBase::with(['category', 'author'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->cursorPaginate(20);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $articles]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'status'      => 'in:published,draft',
        ]);

        $article = KnowledgeBase::create(array_merge(
            $request->only('title', 'content', 'category_id', 'status'),
            ['author_id' => $request->user()->id]
        ));

        return response()->json(['code' => 201, 'message' => 'Created.', 'data' => $article->load(['category', 'author'])], 201);
    }

    public function show(KnowledgeBase $knowledge)
    {
        $knowledge->increment('views');
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $knowledge->load(['category', 'author'])]);
    }

    public function update(Request $request, KnowledgeBase $knowledge)
    {
        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'content'     => 'sometimes|string',
            'category_id' => 'nullable|exists:categories,id',
            'status'      => 'in:published,draft',
        ]);

        $knowledge->update($request->only('title', 'content', 'category_id', 'status'));

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $knowledge->fresh()->load(['category', 'author'])]);
    }

    public function destroy(KnowledgeBase $knowledge)
    {
        $knowledge->delete();
        return response()->json(['code' => 200, 'message' => 'Deleted.', 'data' => null]);
    }

    public function search(Request $request)
    {
        $articles = KnowledgeBase::where('status', 'published')
            ->where(fn($q) =>
                $q->where('title', 'like', "%{$request->q}%")
                  ->orWhere('content', 'like', "%{$request->q}%")
            )->limit(5)->get(['id', 'title', 'content', 'views']);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $articles]);
    }

    public function publicSearch(Request $request)
    {
        return $this->search($request);
    }
}
