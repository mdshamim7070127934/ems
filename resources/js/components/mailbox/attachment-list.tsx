import { FileText, Paperclip, X } from 'lucide-react';
import { formatFileSize, isImageFile } from '@/lib/mailbox';

type SavedAttachment = {
    id: number;
    original_filename: string;
    size: number;
};

type Props = {
    saved: SavedAttachment[];
    files: File[];
    onRemoveSaved: (id: number) => void;
    onRemoveFile: (index: number) => void;
};

export default function AttachmentList({
    saved,
    files,
    onRemoveSaved,
    onRemoveFile,
}: Props) {
    if (saved.length === 0 && files.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {saved.map((attachment) => (
                <span
                    key={`saved-${attachment.id}`}
                    className="bg-muted inline-flex max-w-full items-center gap-1 rounded-full border px-2 py-1 text-xs"
                >
                    <Paperclip className="size-3.5 shrink-0" />
                    <span className="truncate">{attachment.original_filename}</span>
                    <span className="text-muted-foreground">
                        {formatFileSize(attachment.size)}
                    </span>
                    <button
                        type="button"
                        className="hover:text-destructive"
                        onClick={() => onRemoveSaved(attachment.id)}
                        aria-label={`Remove ${attachment.original_filename}`}
                    >
                        <X className="size-3.5" />
                    </button>
                </span>
            ))}
            {files.map((file, index) => (
                <span
                    key={`file-${file.name}-${index}`}
                    className="bg-muted inline-flex max-w-full items-center gap-1 rounded-full border px-2 py-1 text-xs"
                >
                    {isImageFile(file) ? (
                        <img
                            src={URL.createObjectURL(file)}
                            alt=""
                            className="size-5 rounded object-cover"
                        />
                    ) : (
                        <FileText className="size-3.5 shrink-0" />
                    )}
                    <span className="truncate">{file.name}</span>
                    <span className="text-muted-foreground">
                        {formatFileSize(file.size)}
                    </span>
                    <button
                        type="button"
                        className="hover:text-destructive"
                        onClick={() => onRemoveFile(index)}
                        aria-label={`Remove ${file.name}`}
                    >
                        <X className="size-3.5" />
                    </button>
                </span>
            ))}
        </div>
    );
}
