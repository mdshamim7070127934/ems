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

type TinyMceEditor = {
    setContent: (html: string) => void;
    getContent: () => string;
    insertContent: (html: string) => void;
    focus: () => void;
    execCommand: (name: string) => void;
    fire: (event: string) => void;
    on: (event: string, listener: () => void) => void;
    getContainer?: () => HTMLElement;
    nodeChanged?: () => void;
    dom?: {
        setAttrib: (elm: Element, name: string, value: string) => void;
    };
    selection: {
        getNode: () => Element;
    };
};

declare global {
    interface Window {
        tinyMCEPreInit?: {
            base: string;
            suffix: string;
        };
        tinymce?: {
            init: (config: Record<string, unknown>) => Promise<TinyMceEditor[]> | void;
            get: (id: string) => TinyMceEditor | null | undefined;
            remove: (editor?: string | TinyMceEditor) => void;
        };
    }
}

const TINYMCE_BASE = 'https://cdn.jsdelivr.net/npm/tinymce@6.8.4';
const TINYMCE_SRC = `${TINYMCE_BASE}/tinymce.min.js`;

function editorHeight(wrap: HTMLDivElement | null): number {
    const height = wrap?.clientHeight ?? 0;

    return height > 0 ? height : 200;
}

const TinymceEmailEditor = forwardRef<EmailEditorHandle, Props>(
    function TinymceEmailEditor(
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
        const editorRef = useRef<TinyMceEditor | null>(null);
        const onChangeRef = useRef(onChange);
        const lastEmitted = useRef(value);
        const textareaId = `mailbox-tinymce-${useId().replaceAll(':', '')}`;
        const [failed, setFailed] = useState(false);
        const [selectedImage, setSelectedImage] = useState<HTMLElement | null>(null);

        onChangeRef.current = onChange;

        function emit(instance: TinyMceEditor, force = false): void {
            const html = instance.getContent();

            if (!force && html === lastEmitted.current) {
                return;
            }

            lastEmitted.current = html;
            onChangeRef.current(html);
        }

        function syncToolbar(instance: TinyMceEditor): void {
            const node = instance.selection.getNode();

            setSelectedImage(node instanceof HTMLImageElement ? node : null);
        }

        useEffect(() => {
            let cancelled = false;

            if (!window.tinymce) {
                window.tinyMCEPreInit = {
                    base: TINYMCE_BASE,
                    suffix: '.min',
                };
            }

            loadScript(TINYMCE_SRC, () => window.tinymce !== undefined)
                .then(async () => {
                    if (cancelled || !window.tinymce || !textareaRef.current) {
                        return;
                    }

                    window.tinymce.remove(textareaId);

                    await window.tinymce.init({
                        selector: `#${textareaId}`,
                        base_url: TINYMCE_BASE,
                        suffix: '.min',
                        skin_url: `${TINYMCE_BASE}/skins/ui/oxide`,
                        content_css: `${TINYMCE_BASE}/skins/content/default/content.min.css`,
                        menubar: false,
                        branding: false,
                        promotion: false,
                        statusbar: false,
                        height: editorHeight(wrapRef.current),
                        min_height: 120,
                        placeholder,
                        plugins: 'link lists',
                        toolbar:
                            'bold italic underline strikethrough | bullist numlist | link',
                        convert_urls: false,
                        extended_valid_elements: 'img[*],div[*]',
                        content_style: `body { font-family: Instrument Sans, ui-sans-serif, sans-serif; font-size: 14px; } ${emailImageContentCss()}`,
                        setup: (instance: TinyMceEditor) => {
                            instance.on('init', () => {
                                if (
                                    cancelled ||
                                    window.tinymce?.get(textareaId) !== instance
                                ) {
                                    return;
                                }

                                instance.setContent(lastEmitted.current || '');
                                editorRef.current = instance;

                                const fit = (): void => {
                                    const container = instance.getContainer?.();
                                    const wrap = wrapRef.current;

                                    if (!container || !wrap) {
                                        return;
                                    }

                                    container.style.height = `${wrap.clientHeight}px`;
                                    container.style.width = '100%';
                                };

                                fit();

                                if (wrapRef.current && window.ResizeObserver) {
                                    const observer = new ResizeObserver(fit);

                                    observer.observe(wrapRef.current);
                                    instance.on('remove', () => observer.disconnect());
                                }
                            });
                            instance.on('change', () => emit(instance));
                            instance.on('keyup', () => emit(instance));
                            instance.on('input', () => emit(instance));
                            instance.on('undo', () => emit(instance));
                            instance.on('redo', () => emit(instance));
                            instance.on('NodeChange', () => syncToolbar(instance));
                            instance.on('click', () => syncToolbar(instance));
                        },
                    });

                    if (cancelled) {
                        window.tinymce.remove(textareaId);
                    }
                })
                .catch(() => {
                    if (!cancelled) {
                        setFailed(true);
                    }
                });

            return () => {
                cancelled = true;
                window.tinymce?.remove(textareaId);
                editorRef.current = null;
            };
        }, [placeholder, textareaId]);

        useEffect(() => {
            if (value === lastEmitted.current) {
                return;
            }

            lastEmitted.current = value;
            editorRef.current?.setContent(value || '');
        }, [value]);

        useImperativeHandle(
            ref,
            () => ({
                insertEmoji(emoji: string) {
                    editorRef.current?.focus();
                    editorRef.current?.insertContent(emoji);
                },
                insertImage(src: string, alt = '') {
                    editorRef.current?.focus();
                    editorRef.current?.insertContent(emailImageHtml(src, alt));
                },
                promptLink() {
                    editorRef.current?.execCommand('mceLink');
                },
                focus() {
                    editorRef.current?.focus();
                },
                hasSelectedImage() {
                    return (
                        editorRef.current?.selection.getNode().nodeName === 'IMG'
                    );
                },
                getHtml() {
                    return editorRef.current?.getContent() ?? lastEmitted.current;
                },
            }),
            [],
        );

        function applyImageAction(action: EmailImageAction): void {
            const editor = editorRef.current;
            const node = editor?.selection.getNode();
            const img =
                node instanceof HTMLImageElement
                    ? node
                    : selectedImage instanceof HTMLImageElement
                      ? selectedImage
                      : null;

            if (!editor || !img) {
                return;
            }

            if (action === 'remove') {
                img.remove();
                setSelectedImage(null);
                editor.fire('change');
                emit(editor);

                return;
            }

            if (action === 'alt') {
                const next = window.prompt(
                    'Alt text (description for accessibility):',
                    img.getAttribute('alt') ?? '',
                );

                if (next !== null) {
                    img.setAttribute('alt', next);
                    editor.fire('change');
                    emit(editor);
                }

                return;
            }

            applyEmailImageSize(img, action as EmailImageSize);
            editor.dom?.setAttrib(img, 'style', img.getAttribute('style') ?? '');
            editor.dom?.setAttrib(img, 'data-img-size', action);
            editor.nodeChanged?.();
            editor.fire('change');
            emit(editor, true);
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
                        TinyMCE failed to load. You can still type in the box
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

export default TinymceEmailEditor;
