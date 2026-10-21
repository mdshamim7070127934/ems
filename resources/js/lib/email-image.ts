export type EmailImageSize = 'small' | 'bestfit' | 'original';
export type EmailImageAction = EmailImageSize | 'alt' | 'remove';

export const EMAIL_IMAGE_SIZE_STYLES: Record<EmailImageSize, string> = {
    small: 'max-width:200px !important; width:auto !important; height:auto !important;',
    bestfit: 'max-width:100% !important; width:auto !important; height:auto !important;',
    original: 'max-width:none !important; width:auto !important; height:auto !important;',
};

const EMAIL_IMAGE_CONTENT_CSS = `
html, body { height: 100%; margin: 0; }
body { max-width: 100%; overflow: auto; overflow-wrap: anywhere; word-break: break-word; }
img { max-width: 100% !important; height: auto !important; }
img[data-img-size="small"] { max-width: 200px !important; width: auto !important; height: auto !important; }
img[data-img-size="bestfit"] { max-width: 100% !important; width: auto !important; height: auto !important; }
img[data-img-size="original"] { max-width: none !important; width: auto !important; height: auto !important; }
table { max-width: 100% !important; }
.email-signature, .email-signature img, .email-signature table { max-width: 100% !important; }
`;

export function emailImageContentCss(): string {
    return EMAIL_IMAGE_CONTENT_CSS;
}

export function emailImageHtml(src: string, alt = '', size: EmailImageSize = 'bestfit'): string {
    return `<img src="${escapeAttribute(src)}" alt="${escapeAttribute(alt)}" style="${EMAIL_IMAGE_SIZE_STYLES[size]}" data-img-size="${size}">`;
}

export function stripImageSizeStyles(style: string): string {
    return style
        .replace(/\s*max-width\s*:[^;]+;?/gi, '')
        .replace(/\s*width\s*:[^;]+;?/gi, '')
        .replace(/\s*height\s*:[^;]+;?/gi, '')
        .trim()
        .replace(/^;+|;+$/g, '')
        .trim();
}

export function applyEmailImageSize(img: HTMLElement, size: EmailImageSize): void {
    const cleaned = stripImageSizeStyles(img.getAttribute('style') ?? '');
    const next = EMAIL_IMAGE_SIZE_STYLES[size];

    img.setAttribute('style', cleaned === '' ? next : `${cleaned}; ${next}`);
    img.setAttribute('data-img-size', size);

    if (size === 'original') {
        img.removeAttribute('width');
        img.removeAttribute('height');
    }
}

export function detectEmailImageSize(img: HTMLElement): EmailImageSize {
    const forced = (img.getAttribute('data-img-size') ?? '').toLowerCase();

    if (forced === 'small' || forced === 'bestfit' || forced === 'original') {
        return forced;
    }

    const style = (img.getAttribute('style') ?? '').toLowerCase();

    if (/max-width\s*:\s*200px/.test(style)) {
        return 'small';
    }

    if (/max-width\s*:\s*none/.test(style)) {
        return 'original';
    }

    return 'bestfit';
}

export function toolbarPositionForImage(img: HTMLElement): { left: number; top: number } {
    const rect = img.getBoundingClientRect();
    const frame = img.ownerDocument?.defaultView?.frameElement;
    const frameRect = frame instanceof HTMLElement ? frame.getBoundingClientRect() : null;
    const left = (frameRect?.left ?? 0) + rect.left;
    const below = (frameRect?.top ?? 0) + rect.bottom + 6;
    const above = (frameRect?.top ?? 0) + rect.top - 42;

    return {
        left: Math.max(8, Math.min(left, window.innerWidth - 360)),
        top: below + 40 > window.innerHeight ? Math.max(8, above) : below,
    };
}

function escapeAttribute(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}
