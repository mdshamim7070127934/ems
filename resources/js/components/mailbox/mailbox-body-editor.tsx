import { usePage } from '@inertiajs/react';
import { forwardRef } from 'react';
import CkeditorEmailEditor from '@/components/mailbox/ckeditor-email-editor';
import EmailEditor, { type EmailEditorHandle } from '@/components/mailbox/email-editor';
import TinymceEmailEditor from '@/components/mailbox/tinymce-email-editor';

type Props = {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    minHeightClass?: string;
    className?: string;
};

const MailboxBodyEditor = forwardRef<EmailEditorHandle, Props>(
    function MailboxBodyEditor(props, ref) {
        const editor = usePage().props.mailbox?.editor ?? 'tiptap';

        if (editor === 'ckeditor') {
            return <CkeditorEmailEditor key="ckeditor" ref={ref} {...props} />;
        }

        if (editor === 'tinymce') {
            return <TinymceEmailEditor key="tinymce" ref={ref} {...props} />;
        }

        return <EmailEditor key="tiptap" ref={ref} {...props} />;
    },
);

export type { EmailEditorHandle };
export default MailboxBodyEditor;
