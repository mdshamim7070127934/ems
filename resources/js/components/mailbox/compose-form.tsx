import { router, useForm, usePage } from '@inertiajs/react';
import { Image as ImageIcon, Link as LinkIcon, Paperclip, PenLine, Save, Send, Smile, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import MailboxBodyEditor, { type EmailEditorHandle } from '@/components/mailbox/mailbox-body-editor';
import EmojiPicker from '@/components/mailbox/emoji-picker';
import AttachmentList from '@/components/mailbox/attachment-list';
import MailboxDraftDeleteDialog from '@/components/mailbox/mailbox-draft-delete-dialog';
import RecipientField, { type RecipientFieldHandle } from '@/components/mailbox/recipient-field';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { csrfToken, fileToDataUrl, isImageFile } from '@/lib/mailbox';
import {
    applySignatureHtml,
    seededBodyHtml,
} from '@/lib/email-signature';
import { cn } from '@/lib/utils';
import { index as mailboxIndex } from '@/routes/mailbox';
import { store as draftsStore } from '@/routes/mailbox/drafts';
import type { MailboxAccount, MailboxDraft } from '@/types';

type AutosaveResponse = {
    success: boolean;
    message?: string;
    data?: MailboxDraft;
    errors?: Record<string, string[]>;
};

type Props = {
    account: MailboxAccount;
    draft: MailboxDraft;
    variant?: 'page' | 'popup' | 'thread';
    onDiscard?: () => void;
};

export default function ComposeForm({
    account,
    draft,
    variant = 'page',
    onDiscard,
}: Props) {
    const signatures = usePage().props.mailbox.signatures;
    const form = useForm({
        draft_id: draft.id ?? null,
        account_id: account.id,
        to_emails: draft.to_emails ?? [],
        cc_emails: draft.cc_emails ?? [],
        bcc_emails: draft.bcc_emails ?? [],
        subject: draft.subject ?? '',
        body_html: seededBodyHtml(draft.body_html, signatures.default_html),
        thread_id: draft.thread_id,
        in_reply_to: draft.in_reply_to,
        attachments: [] as File[],
        keep_attachment_ids: (draft.attachments ?? []).map((item) => item.id),
        send: false,
    });

    const [ccOpen, setCcOpen] = useState(
        (draft.cc_emails ?? []).length > 0 || (draft.bcc_emails ?? []).length > 0,
    );
    const [emojiOpen, setEmojiOpen] = useState(false);
    const [dragging, setDragging] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editorKey, setEditorKey] = useState(0);
    const skipAutosave = useRef(true);
    const formRef = useRef(form);
    const draftIdRef = useRef<number | null>(draft.id ?? null);
    const persistChain = useRef(Promise.resolve<MailboxDraft | null>(null));
    const editorRef = useRef<EmailEditorHandle>(null);
    const toRef = useRef<RecipientFieldHandle>(null);
    const ccRef = useRef<RecipientFieldHandle>(null);
    const bccRef = useRef<RecipientFieldHandle>(null);
    const fileRef = useRef<HTMLInputElement>(null);
    const imageRef = useRef<HTMLInputElement>(null);
    const [saving, setSaving] = useState(false);
    const [storedAttachments, setStoredAttachments] = useState(
        draft.attachments ?? [],
    );
    const savedAttachments = storedAttachments.filter((item) =>
        form.data.keep_attachment_ids.includes(item.id),
    );

    formRef.current = form;

    function insertSignature(html: string): void {
        const body = editorRef.current?.getHtml() ?? form.data.body_html;

        form.setData('body_html', applySignatureHtml(body, html));
        setEditorKey((key) => key + 1);
    }

    function appendEmails(payload: FormData, key: string, emails: string[]): void {
        if (emails.length === 0) {
            payload.append(key, '');

            return;
        }

        emails.forEach((email) => payload.append(`${key}[]`, email));
    }

    function persist(sendAfter = false): Promise<MailboxDraft | null> {
        const run = persistChain.current.then(async () => {
            const current = formRef.current;
            const toEmails = toRef.current?.commitPending() ?? current.data.to_emails;
            const ccEmails = ccRef.current?.commitPending() ?? current.data.cc_emails;
            const bccEmails = bccRef.current?.commitPending() ?? current.data.bcc_emails;
            const liveHtml =
                editorRef.current?.getHtml() ?? current.data.body_html;
            const payload = new FormData();

            payload.append('account_id', String(current.data.account_id));
            payload.append('send', sendAfter ? '1' : '0');
            payload.append('subject', current.data.subject);
            payload.append('body_html', liveHtml);

            if (draftIdRef.current) {
                payload.append('draft_id', String(draftIdRef.current));
            }

            if (current.data.thread_id) {
                payload.append('thread_id', String(current.data.thread_id));
            }

            if (current.data.in_reply_to) {
                payload.append('in_reply_to', current.data.in_reply_to);
            }

            appendEmails(payload, 'to_emails', toEmails);
            appendEmails(payload, 'cc_emails', ccEmails);
            appendEmails(payload, 'bcc_emails', bccEmails);
            if (current.data.keep_attachment_ids.length === 0) {
                payload.append('keep_attachment_ids', '');
            } else {
                current.data.keep_attachment_ids.forEach((id) =>
                    payload.append('keep_attachment_ids[]', String(id)),
                );
            }

            current.data.attachments.forEach((file) =>
                payload.append('attachments[]', file),
            );

            try {
                const response = await fetch(draftsStore.url(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': csrfToken(),
                    },
                    body: payload,
                });

                const body = (await response.json().catch(() => null)) as AutosaveResponse | null;

                if (!response.ok) {
                    if (body?.errors) {
                        current.clearErrors();
                        current.setError(body.errors);
                    }

                    return null;
                }

                const saved = body?.data;

                if (saved?.id) {
                    const latest = formRef.current;
                    const sentFiles = current.data.attachments;

                    draftIdRef.current = saved.id;
                    latest.clearErrors();
                    latest.setData({
                        ...latest.data,
                        draft_id: saved.id,
                        body_html:
                            latest.data.body_html === current.data.body_html
                                ? liveHtml
                                : latest.data.body_html,
                        keep_attachment_ids: (saved.attachments ?? []).map(
                            (item) => item.id,
                        ),
                        attachments: latest.data.attachments.filter(
                            (file) => !sentFiles.includes(file),
                        ),
                    });
                    setStoredAttachments(saved.attachments ?? []);
                }

                return saved ?? null;
            } catch {
                return null;
            }
        });

        persistChain.current = run.then(
            () => null,
            () => null,
        );

        return run;
    }

    async function save(sendAfter = false): Promise<void> {
        if (saving) {
            return;
        }

        setSaving(true);

        try {
            const saved = await persist(sendAfter);

            if (!sendAfter || !saved?.id) {
                if (!sendAfter && saved?.id) {
                    router.reload({
                        only: ['drafts'],
                        preserveState: true,
                        preserveScroll: true,
                    });
                }

                return;
            }

            router.visit(
                mailboxIndex.url({
                    query: { account_id: account.id },
                }),
            );
            onDiscard?.();
        } finally {
            setSaving(false);
        }
    }

    async function handleFiles(files: File[]) {
        const nextAttachments = [...form.data.attachments];

        for (const file of files) {
            if (isImageFile(file)) {
                const src = await fileToDataUrl(file);
                editorRef.current?.insertImage(src, file.name);
            } else {
                nextAttachments.push(file);
            }
        }

        form.setData('attachments', nextAttachments);
    }

    useEffect(() => {
        if (skipAutosave.current) {
            skipAutosave.current = false;

            return;
        }

        const timeout = window.setTimeout(() => {
            void persist(false);
        }, 2000);

        return () => window.clearTimeout(timeout);
    }, [
        form.data.to_emails,
        form.data.cc_emails,
        form.data.bcc_emails,
        form.data.subject,
        form.data.body_html,
    ]);

    return (
        <form
            className={cn(
                'flex h-full min-h-0 flex-1 flex-col overflow-hidden',
                variant === 'page' && 'gap-3',
            )}
            onSubmit={(event) => {
                event.preventDefault();
                save(true);
            }}
            onDragOver={(event) => {
                event.preventDefault();
                setDragging(true);
            }}
            onDragLeave={() => setDragging(false)}
            onDrop={(event) => {
                event.preventDefault();
                setDragging(false);
                void handleFiles(Array.from(event.dataTransfer.files));
            }}
        >
            <div className={cn('shrink-0 space-y-2', variant !== 'page' && 'flex-shrink-0')}>
                <div>
                    <p className="text-muted-foreground text-xs">From</p>
                    <Input value={account.email} readOnly className="h-8 bg-muted/40" />
                </div>
                <RecipientField
                    ref={toRef}
                    label="To"
                    value={form.data.to_emails}
                    onChange={(emails) => form.setData('to_emails', emails)}
                    error={form.errors.to_emails}
                />
                {ccOpen ? (
                    <>
                        <RecipientField
                            ref={ccRef}
                            label="Cc"
                            placeholder="Cc"
                            value={form.data.cc_emails}
                            onChange={(emails) => form.setData('cc_emails', emails)}
                        />
                        <RecipientField
                            ref={bccRef}
                            label="Bcc"
                            placeholder="Bcc"
                            value={form.data.bcc_emails}
                            onChange={(emails) => form.setData('bcc_emails', emails)}
                        />
                    </>
                ) : (
                    <button
                        type="button"
                        className="text-muted-foreground hover:text-foreground text-xs"
                        onClick={() => setCcOpen(true)}
                    >
                        Cc / Bcc
                    </button>
                )}
                <Input
                    value={form.data.subject}
                    onChange={(event) => form.setData('subject', event.target.value)}
                    placeholder="Subject"
                    className="h-8"
                />
                <AttachmentList
                    saved={savedAttachments}
                    files={form.data.attachments}
                    onRemoveSaved={(id) =>
                        form.setData(
                            'keep_attachment_ids',
                            form.data.keep_attachment_ids.filter((item) => item !== id),
                        )
                    }
                    onRemoveFile={(index) =>
                        form.setData(
                            'attachments',
                            form.data.attachments.filter((_, itemIndex) => itemIndex !== index),
                        )
                    }
                />
            </div>

            <div
                className={cn(
                    'relative mt-2 flex min-h-0 flex-1 flex-col overflow-hidden rounded-md border',
                    dragging && 'border-primary border-dashed bg-primary/5',
                )}
            >
                <MailboxBodyEditor
                    key={editorKey}
                    ref={editorRef}
                    className="h-full min-h-0"
                    value={form.data.body_html}
                    onChange={(html) => form.setData('body_html', html)}
                    minHeightClass={variant === 'popup' ? 'min-h-[180px]' : 'min-h-[200px]'}
                />
                {dragging && (
                    <div className="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-muted-foreground">
                        Drop images to embed, or files to attach
                    </div>
                )}
            </div>

            <div className="relative mt-2 flex shrink-0 flex-wrap items-center gap-1 border-t pt-2">
                <Button
                    type="submit"
                    disabled={saving}
                    className="rounded-full bg-[#1a73e8] px-4 hover:bg-[#1557b0]"
                >
                    <Send />
                    Send
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="rounded-full"
                    disabled={saving}
                    onClick={() => void save(false)}
                    title="Save draft"
                >
                    <Save />
                </Button>
                <span className="bg-border mx-1 h-5 w-px" />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="rounded-full"
                    title="Attach files"
                    onClick={() => fileRef.current?.click()}
                >
                    <Paperclip />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="rounded-full"
                    title="Insert link"
                    onClick={() => editorRef.current?.promptLink()}
                >
                    <LinkIcon />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="rounded-full"
                    title="Insert emoji"
                    onClick={() => setEmojiOpen((open) => !open)}
                >
                    <Smile />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="rounded-full"
                    title="Insert photo"
                    onClick={() => imageRef.current?.click()}
                >
                    <ImageIcon />
                </Button>
                {signatures.items.length > 0 && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="rounded-full"
                                title="Insert signature"
                            >
                                <PenLine />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start">
                            <DropdownMenuLabel>Signatures</DropdownMenuLabel>
                            {signatures.items.map((signature) => (
                                <DropdownMenuItem
                                    key={signature.id}
                                    onClick={() => insertSignature(signature.html)}
                                >
                                    {signature.name}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
                <span className="flex-1" />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="rounded-full"
                    title="Discard"
                    onClick={() => setDeleteOpen(true)}
                >
                    <Trash2 />
                </Button>
                <MailboxDraftDeleteDialog
                    open={deleteOpen}
                    onOpenChange={setDeleteOpen}
                    draftId={form.data.draft_id}
                    subject={form.data.subject}
                    preserveState={false}
                    onDeleted={onDiscard}
                />
                <EmojiPicker
                    open={emojiOpen}
                    onClose={() => setEmojiOpen(false)}
                    onSelect={(emoji) => editorRef.current?.insertEmoji(emoji)}
                />
            </div>

            <input
                ref={fileRef}
                type="file"
                multiple
                className="hidden"
                onChange={(event) => {
                    void handleFiles(Array.from(event.target.files ?? []));
                    event.target.value = '';
                }}
            />
            <input
                ref={imageRef}
                type="file"
                accept="image/*"
                multiple
                className="hidden"
                onChange={(event) => {
                    void handleFiles(Array.from(event.target.files ?? []));
                    event.target.value = '';
                }}
            />
        </form>
    );
}
