<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Ticket $ticket)
    {
        $messages = $ticket->messages()->orderBy('created_at')->get();

        // Append sender names for agent messages
        $agentIds = $messages->where('sender_type', 'agent')->pluck('sender_id')->unique()->filter();
        if ($agentIds->isNotEmpty()) {
            $names = \App\Models\User::whereIn('id', $agentIds)->pluck('name', 'id');
            $messages->each(function ($msg) use ($names) {
                if ($msg->sender_type === 'agent') {
                    $msg->sender_name = $names[$msg->sender_id] ?? '坐席';
                }
            });
        }

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $messages]);
    }

    public function store(Request $request, Ticket $ticket)
    {
        $request->validate([
            'content'     => 'required_without:attachment|string',
            'is_internal' => 'boolean',
            'type'        => 'in:text,image,file',
            'attachment'  => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,docx',
        ]);

        $attachmentUrl  = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $path           = $request->file('attachment')->store('attachments', 'public');
            $attachmentUrl  = Storage::url($path);
            $attachmentName = $request->file('attachment')->getClientOriginalName();
        }

        $message = Message::create([
            'ticket_id'       => $ticket->id,
            'sender_type'     => 'agent',
            'sender_id'       => $request->user()->id,
            'content'         => $request->content ?? '',
            'type'            => $request->type ?? 'text',
            'is_internal'     => $request->boolean('is_internal'),
            'attachment_url'  => $attachmentUrl,
            'attachment_name' => $attachmentName,
        ]);

        // Update first response time
        if (!$ticket->first_response_at) {
            $ticket->update(['first_response_at' => now()]);
        }

        // Unlock ticket
        $ticket->update(['locked_by' => null, 'locked_at' => null, 'status' => 'pending']);

        $message->sender_name = $request->user()->name;
        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['code' => 201, 'message' => 'Message sent.', 'data' => $message], 201);
    }
}
