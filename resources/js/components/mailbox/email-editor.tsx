import { EditorContent, useEditor } from '@tiptap/react';
import { Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import { Color, TextStyle } from '@tiptap/extension-text-style';
import {
    Bold,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Strikethrough,
    Underline as UnderlineIcon,
} from 'lucide-react';
import { forwardRef, useEffect, useImperativeHandle, useState } from 'react';
import MailboxImageToolbar from '@/components/mailbox/mailbox-image-toolbar';
import { Toggle } from '@/components/ui/toggle';
import {
    detectEmailImageSize,
    emailImageHtml,
    EMAIL_IMAGE_SIZE_STYLES,
    type EmailImageAction,
    type EmailImageSize,
} from '@/lib/email-image';
import { cn } from '@/lib/utils';

const MailSignature = Node.create({
    name: 'emailSignature',
    group: 'block',
    content: 'block+',
    defining: true,
    isolating: true,
    parseHTML() {
        return [{ tag: 'div.email-signature' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { class: 'email-signature' }), 0];
    },
});

const MailImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            style: {
                default: EMAIL_IMAGE_SIZE_STYLES.bestfit,
                parseHTML: (element) => element.getAttribute('style'),
                renderHTML: (attributes) =>
                    attributes.style ? { style: attributes.style } : {},
            },
            imgSize: {
                default: 'bestfit',
                parseHTML: (element) => detectEmailImageSize(element),
                renderHTML: (attributes) =>
                    attributes.imgSize
                        ? { 'data-img-size': attributes.imgSize }
                        : {},
            },
        };
    },
});

function selectedImageElement(
    editor: NonNullable<ReturnType<typeof useEditor>>,
): HTMLImageElement | null {
    const dom = editor.view.nodeDOM(editor.state.selection.from);

    if (dom instanceof HTMLImageElement) {
        return dom;
    }

    if (dom instanceof HTMLElement) {
        return dom.querySelector('img');
    }

    return null;
}

export type EmailEditorHandle = {
    insertEmoji: (emoji: string) => void;
    insertImage: (src: string, alt?: string) => void;
    promptLink: () => void;
    focus: () => void;
    hasSelectedImage: () => boolean;
    getHtml: () => string;
};

type Props = {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    minHeightClass?: string;
    className?: string;
};

const EmailEditor = forwardRef<EmailEditorHandle, Props>(function EmailEditor(
    { value, onChange, placeholder = 'Message...', minHeightClass = 'min-h-[180px]', className },
    ref,
) {
    const [selectedImage, setSelectedImage] = useState<HTMLImageElement | null>(null);

    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                link: {
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: {
                        class: 'text-[#1967d2] underline',
                    },
                },
            }),
            TextStyle,
            Color,
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            MailImage.configure({
                inline: false,
                allowBase64: true,
            }),
            MailSignature,
            Placeholder.configure({ placeholder }),
        ],
        content: value || '',
        editorProps: {
            attributes: {
                class: cn(
                    'email-editor-content max-w-full px-1 py-2 text-sm focus:outline-none',
                ),
            },
        },
        onUpdate: ({ editor: current }) => {
            onChange(current.getHTML());
        },
        onSelectionUpdate: ({ editor: current }) => {
            setSelectedImage(
                current.isActive('image') ? selectedImageElement(current) : null,
            );
        },
    });

    useEffect(() => {
        if (!editor) {
            return;
        }

        const current = editor.getHTML();
        const incoming = value || '';

        if (incoming === current) {
            return;
        }

        if (incoming === '' && (current === '<p></p>' || current === '<p><br></p>')) {
            return;
        }

        editor.commands.setContent(incoming, { emitUpdate: false });
    }, [editor, value]);

    useImperativeHandle(ref, () => ({
        insertEmoji(emoji: string) {
            editor?.chain().focus().insertContent(emoji).run();
        },
        insertImage(src: string, alt = '') {
            editor?.chain().focus().insertContent(emailImageHtml(src, alt)).run();
        },
        promptLink() {
            if (!editor) {
                return;
            }

            const previous = editor.getAttributes('link').href as string | undefined;
            const url = window.prompt('Insert link', previous ?? 'https://');

            if (url === null) {
                return;
            }

            if (url === '') {
                editor.chain().focus().unsetLink().run();

                return;
            }

            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },
        focus() {
            editor?.chain().focus().run();
        },
        hasSelectedImage() {
            return editor?.isActive('image') ?? false;
        },
        getHtml() {
            return editor?.getHTML() ?? '';
        },
    }), [editor]);

    function applyImageAction(action: EmailImageAction): void {
        const img = selectedImage;

        if (!editor || !img) {
            return;
        }

        if (action === 'remove') {
            editor.chain().focus().deleteSelection().run();
            setSelectedImage(null);

            return;
        }

        if (action === 'alt') {
            const next = window.prompt(
                'Alt text (description for accessibility):',
                img.getAttribute('alt') ?? '',
            );

            if (next !== null) {
                editor.chain().focus().updateAttributes('image', { alt: next }).run();
            }

            return;
        }

        const size = action as EmailImageSize;
        let pos: number | null = null;

        try {
            pos = editor.view.posAtDOM(img, 0);
        } catch {
            pos = null;
        }

        const chain = editor.chain().focus();

        if (pos !== null) {
            chain.setNodeSelection(pos);
        }

        chain
            .updateAttributes('image', {
                style: EMAIL_IMAGE_SIZE_STYLES[size],
                imgSize: size,
            })
            .run();
        onChange(editor.getHTML());
        setSelectedImage(selectedImageElement(editor));
    }

    return (
        <div className={cn('email-editor flex h-full min-h-0 flex-1 flex-col overflow-hidden', className)}>
            {editor && (
                <div className="flex flex-wrap items-center gap-0.5 border-b py-1">
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('bold')}
                        onPressedChange={() => editor.chain().focus().toggleBold().run()}
                        aria-label="Bold"
                    >
                        <Bold />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('italic')}
                        onPressedChange={() => editor.chain().focus().toggleItalic().run()}
                        aria-label="Italic"
                    >
                        <Italic />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('underline')}
                        onPressedChange={() => editor.chain().focus().toggleUnderline().run()}
                        aria-label="Underline"
                    >
                        <UnderlineIcon />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('strike')}
                        onPressedChange={() => editor.chain().focus().toggleStrike().run()}
                        aria-label="Strikethrough"
                    >
                        <Strikethrough />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('bulletList')}
                        onPressedChange={() => editor.chain().focus().toggleBulletList().run()}
                        aria-label="Bulleted list"
                    >
                        <List />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('orderedList')}
                        onPressedChange={() => editor.chain().focus().toggleOrderedList().run()}
                        aria-label="Numbered list"
                    >
                        <ListOrdered />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('link')}
                        onPressedChange={() => {
                            if (editor.isActive('link')) {
                                editor.chain().focus().unsetLink().run();

                                return;
                            }

                            const url = window.prompt('Insert link', 'https://');

                            if (url) {
                                editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
                            }
                        }}
                        aria-label="Link"
                    >
                        <LinkIcon />
                    </Toggle>
                    <input
                        type="color"
                        className="h-7 w-7 cursor-pointer rounded border bg-transparent p-0"
                        title="Text color"
                        onChange={(event) =>
                            editor.chain().focus().setColor(event.target.value).run()
                        }
                    />
                </div>
            )}

            <div className={cn('min-h-0 flex-1 overflow-auto', minHeightClass)}>
                <EditorContent editor={editor} />
            </div>

            <MailboxImageToolbar image={selectedImage} onAction={applyImageAction} />
        </div>
    );
});

export default EmailEditor;
