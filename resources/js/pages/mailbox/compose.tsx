import { Head, router } from '@inertiajs/react';
import ComposeForm from '@/components/mailbox/compose-form';
import MailboxShell from '@/components/mailbox/mailbox-shell';
import { dashboard } from '@/routes';
import { compose, index as mailboxIndex } from '@/routes/mailbox';
import type {
    MailboxAccount,
    MailboxDraft,
    MailboxFolder,
    MailboxLabel,
} from '@/types';

type Props = {
    accounts: MailboxAccount[];
    account: MailboxAccount;
    folders: MailboxFolder[];
    labels: MailboxLabel[];
    draft: MailboxDraft;
};

export default function MailboxCompose({
    accounts,
    account,
    folders,
    labels,
    draft,
}: Props) {
    const prepared: MailboxDraft = {
        ...draft,
        to_emails: draft.to_emails ?? [],
        cc_emails: draft.cc_emails ?? [],
        bcc_emails: draft.bcc_emails ?? [],
        subject: draft.subject ?? '',
        body_html: draft.body_html ?? '',
        thread_id: draft.thread_id ?? null,
        in_reply_to: draft.in_reply_to ?? null,
    };

    return (
        <>
            <Head title={prepared.subject || 'Compose'} />
            <MailboxShell
                account={account}
                accounts={accounts}
                folders={folders}
                labels={labels}
                active="compose"
                showSearch={false}
            >
                <div className="mx-auto flex h-full min-h-0 max-w-5xl flex-col overflow-hidden p-6">
                    <h1 className="mb-4 shrink-0 text-lg font-semibold">
                        {prepared.subject || 'New message'}
                    </h1>
                    <ComposeForm
                        account={account}
                        draft={prepared}
                        variant="page"
                        onDiscard={() =>
                            router.visit(mailboxIndex.url({ query: { account_id: account.id } }))
                        }
                    />
                </div>
            </MailboxShell>
        </>
    );
}

MailboxCompose.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Mail', href: mailboxIndex() },
        { title: 'Compose', href: compose() },
    ],
};
