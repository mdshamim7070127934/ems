import {
    forwardRef,
    useEffect,
    useId,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';
import type { EmailEditorHandle } from '@/components/mailbox/email-editor';
import MailboxImageToolbar from '@/components/mailbox/mailbox-image-toolbar';
import {
    applyEmailImageSize,
    emailImageContentCss,
    emailImageHtml,
    type EmailImageAction,
    type EmailImageSize,
} from '@/lib/email-image';
import { loadScript } from '@/lib/mailbox';
import { cn } from '@/lib/utils';

type Props = {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    minHeightClass?: string;
    className?: string;
};

type CkElement = {
    getName: () => string;
    getAttribute: (name: string) => string | undefined;
    setAttribute: (name: string, value: string) => void;
    removeAttribute: (name: string) => void;
    remove: () => void;
    $: HTMLElement;
};

type CkEditorInstance = {
    name: string;
    setData: (html: string, options?: { noSnapshot?: boolean }) => void;
    getData: () => string;
    insertHtml: (html: string) => void;
    focus: () => void;
    destroy: (noUpdate?: boolean) => void;
    resize: (width: string | number, height: number) => void;
    on: (event: string, listener: () => void) => void;
    fire: (event: string) => void;
    editable?: () => {
        attachListener: (
            target: unknown,
            event: string,
            listener: () => void,
        ) => void;
    } | null;
    getSelection: () => {
        getSelectedElement?: () => CkElement | null;
        getStartElement: () => CkElement | null;
    } | null;
    execCommand: (name: string) => void;
};

declare global {
    interface Window {
        CKEDITOR_BASEPATH?: string;
        CKEDITOR?: {
            replace: (
                element: string | HTMLTextAreaElement,
                config?: Record<string, unknown>,
            ) => CkEditorInstance;
            instances: Record<string, CkEditorInstance | undefined>;
            disableAutoInline?: boolean;
            addCss?: (css: string) => void;
            config: Record<string, unknown>;
        };
    }
}

const CKEDITOR_BASE = 'https://cdn.ckeditor.com/4.22.1/standard/';
const CKEDITOR_SRC = `${CKEDITOR_BASE}ckeditor.js`;
let ckImageCssAdded = false;

function editorHeight(wrap: HTMLDivElement | null): number {
    const height = wrap?.clientHeight ?? 0;

    return height > 0 ? height : 200;
}

function contentHeight(wrap: HTMLDivElement | null): number {
    if (!wrap) {
        return 160;
    }

    const toolbar = wrap.querySelector('.cke_top, .tox-editor-header');
    const footer = wrap.querySelector('.cke_bottom, .tox-statusbar');
    const chrome =
        (toolbar instanceof HTMLElement ? toolbar.offsetHeight : 0) +
        (footer instanceof HTMLElement ? footer.offsetHeight : 0);

    return Math.max(wrap.clientHeight - chrome, 120);
}

function selectedCkImage(editor: CkEditorInstance | null): CkElement | null {
    try {
        const selection = editor?.getSelection();
        const selected = selection?.getSelectedElement?.();

        if (selected?.getName() === 'img') {
            return selected;
        }

        const start = selection?.getStartElement();

        if (start?.getName() === 'img') {
            return start;
        }
    } catch {
        return null;
    }

    return null;
}

const CkeditorEmailEditor = forwardRef<EmailEditorHandle, Props>(
    function CkeditorEmailEditor(
        {
            value,
            onChange,
            placeholder = 'Message...',
            minHeightClass = 'min-h-[180px]',
            className,
        },
        ref,
    ) {
        const wrapRef = useRef<HTMLDivElement>(null);
        const textareaRef = useRef<HTMLTextAreaElement>(null);
        const editorRef = useRef<CkEditorInstance | null>(null);
        const selectedCkRef = useRef<CkElement | null>(null);
        const onChangeRef = useRef(onChange);
        const lastEmitted = useRef(value);
        const textareaId = `mailbox-ckeditor-${useId().replaceAll(':', '')}`;
        const [failed, setFailed] = useState(false);
        const [selectedImage, setSelectedImage] = useState<HTMLElement | null>(null);

        onChangeRef.current = onChange;

        function emit(force = false): void {
            const html = editorRef.current?.getData() ?? '';

            if (!force && html === lastEmitted.current) {
                return;
            }

            lastEmitted.current = html;
            onChangeRef.current(html);
        }

        function syncToolbar(): void {
            const img = selectedCkImage(editorRef.current);

            selectedCkRef.current = img;
            setSelectedImage(img?.$ ?? null);
        }

        useEffect(() => {
            let cancelled = false;

            window.CKEDITOR_BASEPATH = CKEDITOR_BASE;

            loadScript(CKEDITOR_SRC, () => window.CKEDITOR !== undefined)
                .then(() => {
                    if (cancelled || !window.CKEDITOR || !textareaRef.current) {
                        return;
                    }

                    window.CKEDITOR.disableAutoInline = true;
                    window.CKEDITOR.config.versionCheck = false;

                    if (!ckImageCssAdded && window.CKEDITOR.addCss) {
                        window.CKEDITOR.addCss(emailImageContentCss());
                        ckImageCssAdded = true;
                    }

                    window.CKEDITOR.instances[textareaId]?.destroy(true);

                    const instance = window.CKEDITOR.replace(textareaId, {
                        height: editorHeight(wrapRef.current),
                        extraAllowedContent:
                            'div(email-quoted-reply,email-signature)[*]{*}; img[*]{*}; table[*]{*}',
                        toolbar: [
                            {
                                name: 'basicstyles',
                                items: ['Bold', 'Italic', 'Underline', 'Strike'],
                            },
                            {
                                name: 'paragraph',
                                items: ['NumberedList', 'BulletedList'],
                            },
                            { name: 'links', items: ['Link', 'Unlink'] },
                            { name: 'colors', items: ['TextColor'] },
                        ],
                        removePlugins: 'elementspath,exportpdf',
                        resize_enabled: false,
                        versionCheck: false,
                        title: placeholder,
                    });

                    editorRef.current = instance;

                    instance.on('instanceReady', () => {
                        if (
                            cancelled ||
                            window.CKEDITOR?.instances[textareaId] !== instance
                        ) {
                            return;
                        }

                        instance.setData(lastEmitted.current || '');
                        instance.resize('100%', contentHeight(wrapRef.current));

                        if (wrapRef.current && window.ResizeObserver) {
                            const observer = new ResizeObserver(() => {
                                if (cancelled || editorRef.current !== instance) {
                                    return;
                                }

                                instance.resize('100%', contentHeight(wrapRef.current));
                            });

                            observer.observe(wrapRef.current);
                            instance.on('destroy', () => observer.disconnect());
                        }
                    });
                    instance.on('change', emit);
                    instance.on('key', emit);
                    instance.on('blur', emit);
                    instance.on('selectionChange', syncToolbar);
                    instance.on('contentDom', () => {
                        const editable = instance.editable?.();

                        editable?.attachListener(editable, 'click', syncToolbar);
                        syncToolbar();
                    });
                })
                .catch(() => {
                    if (!cancelled) {
                        setFailed(true);
                    }
                });

            return () => {
                cancelled = true;
                window.CKEDITOR?.instances[textareaId]?.destroy(true);
                editorRef.current = null;
                selectedCkRef.current = null;
            };
        }, [placeholder, textareaId]);

        useEffect(() => {
            if (value === lastEmitted.current) {
                return;
            }

            lastEmitted.current = value;
            editorRef.current?.setData(value || '');
        }, [value]);

        useImperativeHandle(
            ref,
            () => ({
                insertEmoji(emoji: string) {
                    editorRef.current?.focus();
                    editorRef.current?.insertHtml(emoji);
                },
                insertImage(src: string, alt = '') {
                    editorRef.current?.focus();
                    editorRef.current?.insertHtml(emailImageHtml(src, alt));
                },
                promptLink() {
                    editorRef.current?.execCommand('link');
                },
                focus() {
                    editorRef.current?.focus();
                },
                hasSelectedImage() {
                    return selectedCkImage(editorRef.current) !== null;
                },
                getHtml() {
                    return editorRef.current?.getData() ?? lastEmitted.current;
                },
            }),
            [],
        );

        function applyImageAction(action: EmailImageAction): void {
            const img = selectedCkRef.current ?? selectedCkImage(editorRef.current);

            if (!img) {
                return;
            }

            if (action === 'remove') {
                img.remove();
                selectedCkRef.current = null;
                setSelectedImage(null);
                editorRef.current?.fire('change');
                emit();

                return;
            }

            if (action === 'alt') {
                const next = window.prompt(
                    'Alt text (description for accessibility):',
                    img.getAttribute('alt') ?? '',
                );

                if (next !== null) {
                    img.setAttribute('alt', next);
                    editorRef.current?.fire('change');
                    emit();
                }

                return;
            }

            applyEmailImageSize(img.$, action as EmailImageSize);
            img.setAttribute('style', img.$.getAttribute('style') ?? '');
            img.setAttribute('data-img-size', action);
            editorRef.current?.fire('saveSnapshot');
            editorRef.current?.fire('change');
            emit(true);
        }

        return (
            <div
                ref={wrapRef}
                className={cn(
                    'email-editor flex h-full min-h-0 flex-1 flex-col overflow-hidden',
                    minHeightClass,
                    className,
                )}
            >
                {failed && (
                    <p className="text-destructive px-2 py-1 text-xs">
                        CKEditor failed to load. You can still type in the box
                        below.
                    </p>
                )}
                <textarea
                    id={textareaId}
                    ref={textareaRef}
                    className="min-h-0 w-full flex-1 border-0 p-2 text-sm"
                    defaultValue={value}
                    placeholder={placeholder}
                    onChange={(event) => {
                        if (editorRef.current) {
                            return;
                        }

                        lastEmitted.current = event.target.value;
                        onChange(event.target.value);
                    }}
                />
                <MailboxImageToolbar
                    image={selectedImage}
                    onAction={applyImageAction}
                />
            </div>
        );
    },
);

export default CkeditorEmailEditor;
