<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /** GET /chat/sessions — list all sessions, newest first */
    public function sessions()
    {
        $sessions = ChatSession::with('assignee:id,name')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $sessions]);
    }

    /** POST /chat/sessions — create a new session */
    public function createSession(Request $request)
    {
        $session = ChatSession::create([
            'order_no'       => $request->order_no,
            'customer_name'  => $request->customer_name ?? '客戶',
            'customer_phone' => $request->customer_phone,
            'assignee_id'    => $request->user()->id,
        ]);

        // Add Axi greeting as the first message
        ChatMessage::create([
            'session_id'  => $session->id,
            'from_type'   => 'axi',
            'sender_name' => 'Axi',
            'content'     => "您好！我是 Axi，PrimeAxis 智能助理。請問有什麼可以幫您？\nHello! I'm Axi. How may I help?",
        ]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $session]);
    }

    /** GET /chat/sessions/{id}/messages */
    public function messages($id)
    {
        $session  = ChatSession::findOrFail($id);
        $messages = $session->messages()->orderBy('created_at')->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $messages]);
    }

    /** POST /chat/sessions/{id}/messages */
    public function sendMessage(Request $request, $id)
    {
        $session = ChatSession::findOrFail($id);

        $request->validate([
            'content'   => 'required|string|max:5000',
            'from_type' => 'sometimes|in:agent,customer,axi',
        ]);

        $fromType = $request->from_type ?? 'agent';
        $senderName = match ($fromType) {
            'axi'      => 'Axi',
            'customer' => $session->customer_name,
            default    => $request->sender_name ?? $request->user()->name,
        };

        $message = ChatMessage::create([
            'session_id'  => $session->id,
            'from_type'   => $fromType,
            'sender_name' => $senderName,
            'content'     => $request->content,
        ]);

        // Bump session updated_at so it sorts to top of list
        $session->touch();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $message]);
    }
}
