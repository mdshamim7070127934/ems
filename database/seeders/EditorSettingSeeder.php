<?php

namespace Database\Seeders;

use App\Enums\Master\EditorDriver;
use App\Enums\Master\Status;
use App\Models\Master\EditorSetting;
use App\Services\Master\EditorSettingService;
use Illuminate\Database\Seeder;

class EditorSettingSeeder extends Seeder
{
    /**
     * Seed the application's default mailbox editors.
     */
    public function run(): void
    {
        $editors = [
            [
                'code' => EditorDriver::Tiptap,
                'name' => 'Tiptap',
                'description' => 'Built-in rich text editor used by compose and reply.',
                'sort_order' => 1,
                'is_default' => true,
            ],
            [
                'code' => EditorDriver::Ckeditor,
                'name' => 'CKEditor',
                'description' => 'CKEditor 4 loaded for compose and reply.',
                'sort_order' => 2,
                'is_default' => false,
            ],
            [
                'code' => EditorDriver::Tinymce,
                'name' => 'TinyMCE',
                'description' => 'TinyMCE loaded for compose and reply.',
                'sort_order' => 3,
                'is_default' => false,
            ],
        ];

        foreach ($editors as $editor) {
            EditorSetting::query()->firstOrCreate(
                ['code' => $editor['code']],
                [
                    'name' => $editor['name'],
                    'description' => $editor['description'],
                    'status' => Status::Active,
                    'sort_order' => $editor['sort_order'],
                    'is_default' => $editor['is_default'],
                ],
            );
        }

        if (! EditorSetting::query()->where('is_default', true)->exists()) {
            EditorSetting::query()
                ->where('code', EditorDriver::Tiptap)
                ->update(['is_default' => true, 'status' => Status::Active->value]);
        }

        app(EditorSettingService::class)->forgetCache();
    }
}
