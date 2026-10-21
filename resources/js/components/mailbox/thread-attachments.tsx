import { Download, Eye, FileText, MoreVertical, Paperclip } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatFileSize, isImageMime, triggerFileDownloads } from '@/lib/mailbox';
import { preview as previewAttachment, show as showAttachment } from '@/routes/mailbox/attachments';
import type { MailboxAttachment } from '@/types';

type Props = {
    attachments: MailboxAttachment[];
};

export default function ThreadAttachments({ attachments }: Props) {
    if (attachments.length === 0) {
        return null;
    }

    const totalSize = attachments.reduce((sum, attachment) => sum + attachment.size, 0);

    function downloadAll() {
        triggerFileDownloads(attachments.map((attachment) => showAttachment.url(attachment.id)));
    }

    return (
        <div className="mb-3 rounded-lg border border-[#e9ecef] bg-[#f8f9fa] p-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <span className="text-muted-foreground text-[0.8125rem]">
                    <Paperclip className="mr-1 inline size-3.5" />
                    {attachments.length} attachment
                    {attachments.length === 1 ? '' : 's'} ({formatFileSize(totalSize)})
                </span>
                {attachments.length > 1 && (
                    <Button type="button" size="sm" variant="outline" onClick={downloadAll}>
                        <Download />
                        Download all
                    </Button>
                )}
            </div>
            <div className="flex flex-wrap gap-2">
                {attachments.map((attachment) => (
                    <AttachmentCard key={attachment.id} attachment={attachment} />
                ))}
            </div>
        </div>
    );
}

function AttachmentCard({ attachment }: { attachment: MailboxAttachment }) {
    const [thumbFailed, setThumbFailed] = useState(false);
    const isImage = isImageMime(attachment.mime_type, attachment.original_filename);
    const previewUrl = previewAttachment.url(attachment.id);
    const downloadUrl = showAttachment.url(attachment.id);

    return (
        <div className="relative flex h-[140px] w-[120px] shrink-0 flex-col overflow-hidden rounded-md border border-[#e9ecef] bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div className="bg-muted relative flex h-[72px] items-center justify-center">
                {isImage && !thumbFailed ? (
                    <img
                        src={previewUrl}
                        alt=""
                        className="size-full object-cover"
                        onError={() => setThumbFailed(true)}
                    />
                ) : (
                    <FileText className="text-muted-foreground size-8" />
                )}
            </div>
            <div className="flex min-h-0 flex-1 flex-col px-1.5 py-1.5 pr-6">
                <p className="truncate text-[0.7rem] font-medium" title={attachment.original_filename}>
                    {attachment.original_filename}
                </p>
                <p className="text-muted-foreground text-[0.65rem]">{formatFileSize(attachment.size)}</p>
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute top-1 right-1 size-6"
                        aria-label={`Options for ${attachment.original_filename}`}
                    >
                        <MoreVertical className="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="min-w-[140px]">
                    <DropdownMenuItem asChild>
                        <a href={previewUrl} target="_blank" rel="noopener noreferrer">
                            <Eye />
                            Preview
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <a href={downloadUrl} download={attachment.original_filename}>
                            <Download />
                            Download
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
