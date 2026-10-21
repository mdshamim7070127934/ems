<?php

namespace App\Http\Resources\Master;

use App\Models\Master\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailAccount
 */
class EmailAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     provider: string,
     *     provider_label: string,
     *     mailer: string,
     *     mailer_label: string,
     *     from_name: string,
     *     from_email: string,
     *     username: string|null,
     *     smtp_host: string|null,
     *     smtp_port: int|null,
     *     smtp_encryption: string|null,
     *     smtp_encryption_label: string|null,
     *     uses_oauth: bool,
     *     connection_status: string,
     *     connection_status_label: string,
     *     reauthorization_required: bool,
     *     oauth_error: string|null,
     *     oauth_token_expires_at: string|null,
     *     last_token_refresh_at: string|null,
     *     has_password: bool,
     *     has_oauth_access_token: bool,
     *     has_oauth_refresh_token: bool,
     *     is_default: bool,
     *     status: string,
     *     status_label: string,
     *     email_type_ids: list<int>,
     *     email_type_names: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider->value,
            'provider_label' => $this->provider->label(),
            'mailer' => $this->mailer->value,
            'mailer_label' => $this->mailer->label(),
            'from_name' => $this->from_name,
            'from_email' => $this->from_email,
            'username' => $this->username,
            'smtp_host' => $this->smtp_host,
            'smtp_port' => $this->smtp_port,
            'smtp_encryption' => $this->smtp_encryption?->value,
            'smtp_encryption_label' => $this->smtp_encryption?->label(),
            'uses_oauth' => $this->usesOAuth(),
            'connection_status' => $this->connectionStatus(),
            'connection_status_label' => $this->connectionStatusLabel(),
            'reauthorization_required' => $this->reauthorization_required,
            'oauth_error' => $this->oauth_error,
            'oauth_token_expires_at' => $this->oauth_token_expires_at?->format('Y-m-d\TH:i'),
            'last_token_refresh_at' => $this->last_token_refresh_at?->format('Y-m-d H:i'),
            'has_password' => filled($this->password),
            'has_oauth_access_token' => filled($this->oauth_access_token),
            'has_oauth_refresh_token' => filled($this->oauth_refresh_token),
            'is_default' => $this->is_default,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'email_type_ids' => $this->whenLoaded(
                'emailTypes',
                fn (): array => $this->emailTypes->pluck('id')->map(intval(...))->values()->all(),
                [],
            ),
            'email_type_names' => $this->whenLoaded(
                'emailTypes',
                fn (): string => $this->emailTypes->pluck('name')->implode(', '),
                '',
            ),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
