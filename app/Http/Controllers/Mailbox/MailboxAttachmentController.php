<?php

namespace App\Http\Controllers\Mailbox;

use App\Models\Mailbox\EmailAttachment;
use App\Services\Mailbox\MailboxAttachmentService;
use App\Services\Mailbox\UniqueEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailboxAttachmentController extends MailboxController
{
    public function show(EmailAttachment $mailboxAttachment, MailboxAttachmentService $attachments): StreamedResponse
    {
        $mailboxAttachment->load('email.account');
        abort_if($mailboxAttachment->email === null, 404);
        $this->authorize('download', $mailboxAttachment->email);

        return $attachments->download($mailboxAttachment);
    }

    public function preview(EmailAttachment $mailboxAttachment, MailboxAttachmentService $attachments): StreamedResponse
    {
        $mailboxAttachment->load('email.account');
        abort_if($mailboxAttachment->email === null, 404);
        $this->authorize('download', $mailboxAttachment->email);

        return $attachments->preview($mailboxAttachment);
    }

    public function autocomplete(Request $request, UniqueEmailService $uniqueEmails): JsonResponse
    {
        $suggestions = $uniqueEmails->suggest($request->string('q')->toString());

        return response()->json([
            'success' => true,
            'message' => 'Suggestions fetched.',
            'data' => $suggestions,
        ]);
    }
}
