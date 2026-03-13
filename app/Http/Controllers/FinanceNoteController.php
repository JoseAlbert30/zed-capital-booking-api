<?php

namespace App\Http\Controllers;

use App\Events\FinanceNoteAdded;
use App\Models\FinanceNote;
use App\Models\FinanceNoteAttachment;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FinanceNoteController extends Controller
{
    /** Short-key → model class map (mirrors AppServiceProvider morph map) */
    private const MODEL_MAP = [
        'noc'        => \App\Models\FinanceNOC::class,
        'pop'        => \App\Models\FinancePOP::class,
        'soa'        => \App\Models\FinanceSOA::class,
        'penalty'    => \App\Models\FinancePenalty::class,
        'thirdparty' => \App\Models\FinanceThirdparty::class,
    ];

    // -----------------------------------------------------------------------
    // GET /finance/notes?noteable_type=noc&noteable_id=5
    // -----------------------------------------------------------------------
    public function index(Request $request)
    {
        $type = $request->query('noteable_type');
        $id   = $request->query('noteable_id');

        if (!$type || !$id || !isset(self::MODEL_MAP[$type])) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        $notes = FinanceNote::with('attachments')
            ->where('noteable_type', $type)
            ->where('noteable_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'notes'   => $notes->map(fn($n) => $n->toApiArray()),
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /finance/notes
    // -----------------------------------------------------------------------
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'noteable_type' => 'required|in:noc,pop,soa,penalty,thirdparty',
            'noteable_id'   => 'required|integer|min:1',
            'project_name'  => 'required|string',
            'message'       => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Determine sender
        $authType = $request->attributes->get('auth_type', 'admin');
        $user     = $request->user();

        if ($authType === 'developer') {
            $sentByType = 'developer';
            $sentByName = $user?->name ?? 'Developer';
            $sentById   = $user?->id;
        } else {
            $sentByType = 'admin';
            $sentByName = $user?->full_name ?? $user?->name ?? 'Admin';
            $sentById   = $user?->id;
        }

        // Create the note
        $note = FinanceNote::create([
            'noteable_type' => $request->noteable_type,
            'noteable_id'   => $request->noteable_id,
            'project_name'  => $request->project_name,
            'message'       => $request->message,
            'sent_by_type'  => $sentByType,
            'sent_by_id'    => $sentById,
            'sent_by_name'  => $sentByName,
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $safeFileName = time() . '_' . preg_replace('/[%#?&+\s]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs(
                    'finance/notes/' . $request->project_name,
                    $safeFileName,
                    'public'
                );
                FinanceNoteAttachment::create([
                    'note_id'   => $note->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $note->load('attachments');

        // Send email notification to the other party
        if ($sentByType === 'developer') {
            $this->notifyAdmin($note, $request->project_name);
        } else {
            $this->notifyDeveloper($note, $request->noteable_type, (int) $request->noteable_id, $request->project_name);
        }

        // Broadcast via WebSocket
        broadcast(new FinanceNoteAdded(
            $request->project_name,
            $request->noteable_type,
            (int) $request->noteable_id,
            $note->toApiArray()
        ));

        return response()->json(['success' => true, 'note' => $note->toApiArray()], 201);
    }

    // -----------------------------------------------------------------------
    // POST /finance/notes/{id}/read  – mark as read (by the OTHER party)
    // -----------------------------------------------------------------------
    public function markRead(Request $request, $id)
    {
        $note = FinanceNote::find($id);
        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note not found'], 404);
        }

        if (!$note->is_read) {
            $note->is_read = true;
            $note->read_at = now();
            $note->save();
        }

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // DELETE /finance/notes/{id}  – admin only
    // -----------------------------------------------------------------------
    public function destroy($id)
    {
        $note = FinanceNote::find($id);
        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note not found'], 404);
        }

        // Delete attachment files
        foreach ($note->attachments as $att) {
            if (Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }

        $note->delete();

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function notifyAdmin(FinanceNote $note, string $projectName): void
    {
        try {
            $property    = Property::where('project_name', $projectName)->first();
            $adminEmails = ['wbd@zedcapital.ae'];

            if ($property && $property->admin_emails) {
                $parsed = array_filter(array_map('trim', explode(',', $property->admin_emails)));
                if (!empty($parsed)) {
                    $adminEmails = $parsed;
                }
            }

            $emailData = [
                'subject'         => "Developer Note: {$note->sent_by_name} – {$projectName}",
                'greeting'        => 'New Developer Note / Request',
                'transactionType' => 'Developer Note',
                'messageBody'     => "A developer has added a note or request for a finance item in <strong>{$projectName}</strong>.",
                'details'         => [
                    'Project'     => $projectName,
                    'From'        => $note->sent_by_name,
                    'Message'     => $note->message,
                ],
            ];

            Mail::mailer('finance')->send('emails.finance-note-to-admin', $emailData, function ($message) use ($adminEmails, $note, $projectName) {
                $message->to($adminEmails)
                    ->subject("Developer Note: {$note->sent_by_name} – {$projectName}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to send developer note notification to admin: " . $e->getMessage());
        }
    }

    private function notifyDeveloper(FinanceNote $note, string $noteableType, int $noteableId, string $projectName): void
    {
        try {
            $modelClass = self::MODEL_MAP[$noteableType] ?? null;
            if (!$modelClass) {
                return;
            }

            $item     = $modelClass::find($noteableId);
            $property = Property::where('project_name', $projectName)->first();

            if (!$property || !$property->developer_email) {
                Log::warning("No developer email for project: {$projectName}");
                return;
            }

            // Get or generate magic link
            $magicLink = DeveloperMagicLink::where('project_name', $projectName)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $projectName,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90
                );
            }

            $emailData = [
                'subject'         => "Admin Reply – {$projectName}",
                'transactionType' => 'Admin Reply',
                'developerName'   => $property->developer_name ?? 'Developer',
                'messageBody'     => "An admin has replied to a note on a finance item in <strong>{$projectName}</strong>.",
                'details'         => [
                    'Project'  => $projectName,
                    'From'     => $note->sent_by_name,
                    'Message'  => $note->message,
                ],
                'magicLink'       => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
            ];

            Mail::mailer('finance')->send('emails.finance-note-to-developer', $emailData, function ($message) use ($property, $projectName) {
                $message->to($property->developer_email)
                    ->subject("Admin Reply – {$projectName}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to send admin reply notification to developer: " . $e->getMessage());
        }
    }
}
