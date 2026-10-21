import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            mailbox: {
                editor: 'tiptap' | 'ckeditor' | 'tinymce';
                signatures: {
                    items: Array<{
                        id: number;
                        name: string;
                        html: string;
                    }>;
                    default_html: string | null;
                };
            };
            [key: string]: unknown;
        };
    }
}
