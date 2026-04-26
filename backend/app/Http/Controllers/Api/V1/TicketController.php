<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TicketUpdated;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['customer', 'agent', 'category'])
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->assigned_to_me) {
            $query->where('assigned_agent_id', $request->user()->id);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('ticket_no', 'like', '%' . $request->search . '%');
            });
        }

        $tickets = $query->cursorPaginate(20);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $tickets]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'     => 'required|string|max:255',
            'priority'    => 'required|in:low,medium,high,urgent',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'content'     => 'required|string',
        ]);

        $slaHours = ['urgent' => 4, 'high' => 8, 'medium' => 24, 'low' => 72];

        $ticket = Ticket::create([
            'ticket_no'          => Ticket::generateTicketNo(),
            'subject'            => $request->subject,
            'priority'           => $request->priority,
            'category_id'        => $request->category_id,
            'customer_id'        => $request->customer_id,
            'assigned_agent_id'  => $this->roundRobinAgent(),
            'sla_due_at'         => now()->addHours($slaHours[$request->priority]),
            'status'             => 'open',
        ]);

        Message::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'agent',
            'sender_id'   => $request->user()->id,
            'content'     => $request->content,
            'type'        => 'text',
        ]);

        AuditLog::record('ticket.create', 'ticket', $ticket->id, ['subject' => $ticket->subject]);

        return response()->json(['code' => 201, 'message' => 'Ticket created.', 'data' => $ticket->load(['customer', 'agent', 'category'])], 201);
    }

    public function show(Ticket $ticket)
    {
        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => $ticket->load(['customer', 'agent', 'category', 'messages']),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'subject'     => 'sometimes|string|max:255',
            'priority'    => 'sometimes|in:low,medium,high,urgent',
            'category_id' => 'nullable|exists:categories,id',
            'tags'        => 'nullable|array',
        ]);

        // Collision detection: lock the ticket to the current agent
        if ($ticket->locked_by && $ticket->locked_by !== $request->user()->id) {
            $lockedBy = $ticket->lockedBy;
            return response()->json([
                'code'    => 409,
                'message' => "工單正在被 {$lockedBy->name} 編輯中",
                'data'    => null,
            ], 409);
        }

        $ticket->update(array_merge(
            $request->only('subject', 'priority', 'category_id', 'tags'),
            ['locked_by' => $request->user()->id, 'locked_at' => now()]
        ));

        AuditLog::record('ticket.update', 'ticket', $ticket->id);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $ticket->fresh()->load(['customer', 'agent', 'category'])]);
    }

    public function destroy(Ticket $ticket)
    {
        AuditLog::record('ticket.delete', 'ticket', $ticket->id, ['ticket_no' => $ticket->ticket_no]);
        $ticket->delete();

        return response()->json(['code' => 200, 'message' => 'Ticket deleted.', 'data' => null]);
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate(['agent_id' => 'required|exists:users,id']);

        $ticket->update(['assigned_agent_id' => $request->agent_id]);
        AuditLog::record('ticket.assign', 'ticket', $ticket->id, ['agent_id' => $request->agent_id]);

        broadcast(new TicketUpdated($ticket->fresh()->load('agent')))->toOthers();

        return response()->json(['code' => 200, 'message' => 'Assigned.', 'data' => $ticket->fresh()->load('agent')]);
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate(['status' => 'required|in:open,pending,resolved,closed']);

        $data = ['status' => $request->status];
        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);
        AuditLog::record('ticket.status', 'ticket', $ticket->id, ['status' => $request->status]);

        broadcast(new TicketUpdated($ticket->fresh()->load(['customer', 'agent'])))->toOthers();

        return response()->json(['code' => 200, 'message' => 'Status updated.', 'data' => $ticket->fresh()]);
    }

    public function transfer(Request $request, Ticket $ticket)
    {
        $request->validate(['agent_id' => 'required|exists:users,id']);

        $oldAgentId = $ticket->assigned_agent_id;
        $ticket->update(['assigned_agent_id' => $request->agent_id]);

        AuditLog::record('ticket.transfer', 'ticket', $ticket->id, [
            'from' => $oldAgentId,
            'to'   => $request->agent_id,
        ]);

        return response()->json(['code' => 200, 'message' => 'Transferred.', 'data' => $ticket->fresh()->load('agent')]);
    }

    private function roundRobinAgent(): ?int
    {
        $agents = User::where('role', 'agent')
            ->where('status', 'online')
            ->withCount(['tickets' => fn($q) => $q->whereIn('status', ['open', 'pending'])])
            ->orderBy('tickets_count')
            ->first();

        return $agents?->id;
    }
}
