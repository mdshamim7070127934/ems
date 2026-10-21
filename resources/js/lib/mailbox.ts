import {
    FileText,
    Folder,
    Inbox,
    Send,
    ShieldAlert,
    Star,
    Trash2,
    type LucideIcon,
} from 'lucide-react';

export function csrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

export function isValidEmail(value: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
}

export function formatMailboxDate(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const now = new Date();

    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString(undefined, {
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function formatExactMailboxDate(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(undefined, {
        weekday: 'short',
        month: 'numeric',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function folderDisplayName(slug: string, name: string): string {
    const names: Record<string, string> = {
        inbox: 'Inbox',
        starred: 'Starred',
        sent: 'Sent',
        'sent-mail': 'Sent',
        draft: 'Drafts',
        'draft-mail': 'Drafts',
        trash: 'Trash',
        spam: 'Spam',
        'all-mail': 'All Mail',
        important: 'Important',
    };

    return names[slug] ?? name;
}

export function folderIcon(slug: string): LucideIcon {
    if (slug === 'inbox') {
        return Inbox;
    }

    if (slug === 'sent' || slug === 'sent-mail') {
        return Send;
    }

    if (slug === 'draft' || slug === 'draft-mail') {
        return FileText;
    }

    if (slug === 'trash') {
        return Trash2;
    }

    if (slug === 'spam') {
        return ShieldAlert;
    }

    if (slug === 'starred' || slug === 'important') {
        return Star;
    }

    return Folder;
}

export function initials(name: string | null | undefined, email: string): string {
    const source = (name || email).trim();

    return (source.charAt(0) || '?').toUpperCase();
}

export function isImageFile(file: File): boolean {
    return file.type.startsWith('image/') || /\.(jpe?g|png|gif|webp|bmp)$/i.test(file.name);
}

export function isImageMime(mime: string | null | undefined, filename?: string): boolean {
    if (mime?.startsWith('image/')) {
        return true;
    }

    return /\.(jpe?g|png|gif|webp|bmp)$/i.test(filename ?? '');
}

export function triggerFileDownloads(urls: string[], delayMs = 300): void {
    urls.forEach((url, index) => {
        window.setTimeout(() => {
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', '');
            link.rel = 'noopener';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }, index * delayMs);
    });
}

export function fileToDataUrl(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result));
        reader.onerror = () => reject(reader.error);
        reader.readAsDataURL(file);
    });
}

const loadingScripts = new Map<string, Promise<void>>();

export function loadScript(src: string, isReady?: () => boolean): Promise<void> {
    const cached = loadingScripts.get(src);

    if (cached) {
        return cached;
    }

    const promise = new Promise<void>((resolve, reject) => {
        let settled = false;
        const timeout = window.setTimeout(() => {
            if (settled) {
                return;
            }

            settled = true;
            loadingScripts.delete(src);
            reject(new Error(`Timed out loading script: ${src}`));
        }, 15000);

        const succeed = (): void => {
            if (settled) {
                return;
            }

            settled = true;
            window.clearTimeout(timeout);
            resolve();
        };

        const finish = (): void => {
            if (!isReady || isReady()) {
                succeed();

                return;
            }

            window.setTimeout(finish, 30);
        };

        if (isReady?.()) {
            succeed();

            return;
        }

        const existing = document.querySelector(`script[src="${src}"]`);

        if (existing) {
            finish();

            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => finish();
        script.onerror = () => {
            if (settled) {
                return;
            }

            settled = true;
            window.clearTimeout(timeout);
            loadingScripts.delete(src);
            reject(new Error(`Failed to load script: ${src}`));
        };
        document.head.appendChild(script);
    });

    loadingScripts.set(src, promise);

    return promise;
}
