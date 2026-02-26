<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class WebhookEventController extends Controller
{
    /**
     * List webhook events with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = WebhookEvent::latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $events = $query->paginate(20);

        return response()->json($events);
    }

    /**
     * Show details of a single webhook event.
     */
    public function show($id)
    {
        $event = WebhookEvent::findOrFail($id);
        return response()->json($event);
    }

    /**
     * Delete an event (optional, for cleanup).
     */
    public function destroy($id)
    {
        $event = WebhookEvent::findOrFail($id);
        $event->delete();
        return response()->json(['message' => 'Evento removido.']);
    }
}
