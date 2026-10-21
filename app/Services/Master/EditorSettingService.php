<?php

namespace App\Services\Master;

use App\Enums\Master\EditorDriver;
use App\Enums\Master\Status;
use App\Models\Master\EditorSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EditorSettingService
{
    public const CACHE_KEY = 'editor_setting:default';

    /**
     * @param  array{name: string, description?: string|null, sort_order: int, status: Status|string}  $data
     */
    public function update(EditorSetting $setting, array $data): bool
    {
        $status = $data['status'] instanceof Status
            ? $data['status']
            : Status::from((string) $data['status']);

        if ($setting->is_default && $status === Status::Inactive) {
            return false;
        }

        $setting->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'],
            'status' => $status,
        ]);

        $this->forgetCache();

        return true;
    }

    public function makeDefault(EditorSetting $setting): bool
    {
        if ($setting->status !== Status::Active) {
            return false;
        }

        DB::transaction(function () use ($setting): void {
            EditorSetting::query()
                ->where('id', '!=', $setting->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $setting->update([
                'is_default' => true,
                'status' => Status::Active,
            ]);
        });

        $this->forgetCache();

        return true;
    }

    public function delete(EditorSetting $setting): bool
    {
        if ($setting->is_default) {
            return false;
        }

        $setting->delete();
        $this->forgetCache();

        return true;
    }

    public function activeCode(): EditorDriver
    {
        $code = Cache::remember(self::CACHE_KEY, 3600, function (): string {
            $setting = EditorSetting::query()
                ->active()
                ->where('is_default', true)
                ->first();

            return $setting instanceof EditorSetting
                ? $setting->code->value
                : EditorDriver::Tiptap->value;
        });

        return match ($code) {
            EditorDriver::Ckeditor->value => EditorDriver::Ckeditor,
            EditorDriver::Tinymce->value => EditorDriver::Tinymce,
            default => EditorDriver::Tiptap,
        };
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
