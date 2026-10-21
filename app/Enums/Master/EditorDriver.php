<?php

namespace App\Enums\Master;

enum EditorDriver: string
{
    case Tiptap = 'tiptap';
    case Ckeditor = 'ckeditor';
    case Tinymce = 'tinymce';

    public function label(): string
    {
        return match ($this) {
            self::Tiptap => 'Tiptap',
            self::Ckeditor => 'CKEditor',
            self::Tinymce => 'TinyMCE',
        };
    }
}
