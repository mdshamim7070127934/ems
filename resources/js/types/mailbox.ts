export type MailboxAccount = {
    id: number;
    email: string;
    display_name: string | null;
    provider: string;
    provider_label: string;
    is_default: boolean;
    is_active: boolean;
    uses_oauth: boolean;
};

export type MailboxFolder = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    type: string;
    unread_count: number;
    message_count: number;
};

export type MailboxLabel = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
};

export type MailboxThread = {
    id: number;
    subject: string | null;
    snippet: string | null;
    from_email: string | null;
    from_name: string | null;
    is_starred: boolean;
    is_unread: boolean;
    has_attachments: boolean;
    message_count: number;
    last_message_at: string | null;
    labels: MailboxLabel[];
    sender_blocked: boolean;
};

export type MailboxAttachment = {
    id: number;
    original_filename: string;
    mime_type: string | null;
    size: number;
};

export type MailboxEmail = {
    id: number;
    from_email: string;
    from_name: string | null;
    to_emails: string[];
    cc_emails: string[];
    subject: string;
    snippet: string | null;
    body_html: string | null;
    body_plain: string | null;
    is_unread: boolean;
    is_starred: boolean;
    internal_date: string | null;
    attachments: MailboxAttachment[];
    label_ids: number[];
    sender_blocked: boolean;
};

export type MailboxDraft = {
    id?: number;
    to_emails: string[];
    cc_emails: string[];
    bcc_emails: string[];
    subject: string | null;
    body_html: string | null;
    thread_id: number | null;
    in_reply_to: string | null;
    updated_at?: string | null;
    attachments?: Array<{
        id: number;
        original_filename: string;
        size: number;
    }>;
};

export type MailboxRecipientSuggestion = {
    email: string;
    display_name: string;
};

export type MailboxChrome = {
    accounts: MailboxAccount[];
    folders: MailboxFolder[];
    labels: MailboxLabel[];
};

export type MailboxFilters = {
    account_id: number | null;
    folder: string;
    label: number | null;
    search: string;
    page: number;
    pageSize: number;
};

export type PaginatedMailboxThreads = {
    data: MailboxThread[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
};

export type MailboxDraftFilters = {
    account_id: number;
    search: string;
    page: number;
    pageSize: number;
};

export type PaginatedMailboxDrafts = {
    data: MailboxDraft[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
};

export type MailboxSignatureItem = {
    id: number;
    name: string;
    html: string;
};

export type SharedMailboxSignatures = {
    items: MailboxSignatureItem[];
    default_html: string | null;
};

export type EmailSignatureSortColumn =
    | 'signature_name'
    | 'status'
    | 'is_default'
    | 'created_at';

export type EmailSignature = {
    id: number;
    signature_name: string;
    signature_html: string | null;
    is_default: boolean;
    status: 'active' | 'inactive';
    status_label: string;
    created_at: string;
    updated_at: string;
};

export type EmailSignatureFilters = {
    search: string;
    sortBy: EmailSignatureSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedEmailSignatures = {
    data: EmailSignature[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
};
