<?php

namespace App\Http\Controllers;

use App\Models\FollowupTask;
use Illuminate\Http\Request;

class FollowupController extends Controller
{
    public function index(Request $request)
    {
        $tasks = FollowupTask::with('assignee:id,name')
            ->orderByRaw("FIELD(status, 'inprogress', 'todo', 'done')")
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $tasks]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:500',
            'priority' => 'sometimes|in:high,medium,low',
        ]);

        $task = FollowupTask::create([
            'title'       => $request->title,
            'order_no'    => $request->order_no,
            'customer'    => $request->customer,
            'priority'    => $request->priority    ?? 'medium',
            'status'      => $request->status      ?? 'todo',
            'due_time'    => $request->due_time     ?? $request->dueTime,
            'note'        => $request->note,
            'assignee_id' => $request->assignee_id ?? $request->user()->id,
        ]);

        $task->load('assignee:id,name');

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $task]);
    }

    public function update(Request $request, $id)
    {
        $task = FollowupTask::findOrFail($id);

        $task->update(array_filter([
            'title'    => $request->title,
            'priority' => $request->priority,
            'status'   => $request->status,
            'due_time' => $request->due_time ?? $request->dueTime,
            'note'     => $request->note,
        ], fn($v) => !is_null($v)));

        $task->load('assignee:id,name');

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $task]);
    }
}
