export const SIGNATURE_MARKER = '<!-- SIGNATURE -->';
export const SIGNATURE_CLASS = 'email-signature';

export function wrapSignatureHtml(signatureHtml: string): string {
    const html = signatureHtml.trim();

    if (html === '') {
        return '';
    }

    if (html.includes(SIGNATURE_CLASS)) {
        return html;
    }

    return `<div class="${SIGNATURE_CLASS}">${html}</div>`;
}

export function seededBodyHtml(existing: string | null | undefined, defaultHtml: string | null): string {
    if (existing) {
        return existing;
    }

    const signature = wrapSignatureHtml(defaultHtml ?? '');

    if (signature === '') {
        return '';
    }

    return `${SIGNATURE_MARKER}<br><br>${signature}`;
}

export function applySignatureHtml(body: string, signatureHtml: string): string {
    const signature = wrapSignatureHtml(signatureHtml);

    if (body.includes(SIGNATURE_MARKER)) {
        const prefix = body.split(SIGNATURE_MARKER)[0];

        if (signature === '') {
            return prefix;
        }

        return `${prefix}${SIGNATURE_MARKER}<br><br>${signature}`;
    }

    if (typeof DOMParser === 'undefined') {
        return signature === '' ? body : `${body}<br><br>${signature}`;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div id="ems-sig-root">${body}</div>`, 'text/html');
    const root = doc.getElementById('ems-sig-root');

    if (!root) {
        return signature === '' ? body : `${body}<br><br>${signature}`;
    }

    const blocks = Array.from(root.querySelectorAll(`.${SIGNATURE_CLASS}`)).filter(
        (element) => element.parentElement?.closest(`.${SIGNATURE_CLASS}`) === null,
    );

    if (blocks.length === 0) {
        if (signature === '') {
            return body;
        }

        root.insertAdjacentHTML('beforeend', `<br><br>${signature}`);

        return root.innerHTML;
    }

    if (signature === '') {
        blocks.forEach((element) => element.remove());

        return root.innerHTML;
    }

    const [first, ...extras] = blocks;

    first.outerHTML = signature;
    extras.forEach((element) => element.remove());

    return root.innerHTML;
}
