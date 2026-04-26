<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ChatMessageSent;
use App\Events\ChatSessionUpdated;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function init(Request $request)
    {
        $request->validate([
            'visitor_name'  => 'nullable|string|max:100',
            'visitor_email' => 'nullable|email',
        ]);

        $session = ChatSession::create([
            'session_token' => Str::uuid(),
            'visitor_name'  => $request->visitor_name ?? 'Visitor',
            'visitor_email' => $request->visitor_email,
            'source_url'    => $request->header('Referer'),
            'browser'       => $request->header('User-Agent'),
            'status'        => 'waiting',
        ]);

        broadcast(new ChatSessionUpdated($session))->toOthers();

        return response()->json([
            'code'    => 200,
            'message' => 'Chat session created.',
            'data'    => ['session_token' => $session->session_token, 'session_id' => $session->id],
        ]);
    }

    public function sessions(Request $request)
    {
        $sessions = ChatSession::with(['agent', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->when(!$request->user()->isSupervisor(), fn($q) =>
                $q->where(fn($sub) =>
                    $sub->where('agent_id', $request->user()->id)->orWhere('status', 'waiting')
                )
            )
            ->orderByRaw("FIELD(status, 'waiting', 'active', 'closed')")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $sessions]);
    }

    public function accept(Request $request, ChatSession $session)
    {
        if ($session->status !== 'waiting') {
            return response()->json(['code' => 400, 'message' => 'Session not in waiting state.', 'data' => null], 400);
        }

        $session->update([
            'agent_id'    => $request->user()->id,
            'status'      => 'active',
            'accepted_at' => now(),
        ]);

        ChatMessage::create([
            'session_id'  => $session->id,
            'sender_type' => 'system',
            'content'     => "{$request->user()->name} 已接入對話",
        ]);

        broadcast(new ChatSessionUpdated($session->fresh()->load('agent')))->toOthers();

        return response()->json(['code' => 200, 'message' => 'Accepted.', 'data' => $session->fresh()]);
    }

    public function close(Request $request, ChatSession $session)
    {
        $session->update(['status' => 'closed', 'closed_at' => now()]);

        // Convert to ticket if not already
        if (!$session->ticket_id) {
            $customer = null;
            if ($session->visitor_email) {
                $customer = Customer::firstOrCreate(
                    ['email' => $session->visitor_email],
                    ['name' => $session->visitor_name ?? 'Unknown']
                );
            }

            $ticket = Ticket::create([
                'ticket_no'         => Ticket::generateTicketNo(),
                'subject'           => 'Live Chat: ' . ($session->visitor_name ?? 'Visitor'),
                'status'            => 'resolved',
                'priority'          => 'medium',
                'customer_id'       => $customer?->id,
                'assigned_agent_id' => $session->agent_id,
                'resolved_at'       => now(),
                'source'            => 'live_chat',
            ]);

            $session->update(['ticket_id' => $ticket->id]);
        }

        broadcast(new ChatSessionUpdated($session->fresh()))->toOthers();

        return response()->json(['code' => 200, 'message' => 'Session closed.', 'data' => $session->fresh()]);
    }

    public function sendMessage(Request $request, ChatSession $session)
    {
        $request->validate(['content' => 'required|string']);

        $msg = ChatMessage::create([
            'session_id'  => $session->id,
            'sender_type' => 'agent',
            'sender_id'   => $request->user()->id,
            'content'     => $request->content,
        ]);

        broadcast(new ChatMessageSent($msg->load([])))->toOthers();

        return response()->json(['code' => 201, 'message' => 'Sent.', 'data' => $msg], 201);
    }

    public function visitorMessage(Request $request, ChatSession $session)
    {
        $request->validate(['content' => 'required|string']);

        if ($session->status === 'closed') {
            return response()->json(['code' => 400, 'message' => 'Session is closed.', 'data' => null], 400);
        }

        $msg = ChatMessage::create([
            'session_id'  => $session->id,
            'sender_type' => 'visitor',
            'content'     => $request->content,
        ]);

        broadcast(new ChatMessageSent($msg))->toOthers();

        return response()->json(['code' => 201, 'message' => 'Sent.', 'data' => $msg], 201);
    }

    public function typing(Request $request, ChatSession $session)
    {
        broadcast(new \App\Events\TypingIndicator($session->id, 'agent', $request->user()->name))->toOthers();
        return response()->json(['code' => 200, 'message' => 'ok', 'data' => null]);
    }

    public function messages(ChatSession $session)
    {
        $messages = $session->messages()->orderBy('created_at')->get();
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $messages]);
    }

    public function poll(Request $request, ChatSession $session)
    {
        $since = $request->since ? \Carbon\Carbon::parse($request->since) : null;
        $messages = $session->messages()
            ->when($since, fn($q) => $q->where('created_at', '>', $since))
            ->orderBy('created_at')
            ->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $messages]);
    }
}
