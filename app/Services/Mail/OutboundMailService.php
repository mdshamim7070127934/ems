<?php

namespace App\Services\Mail;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\SmtpEncryption;
use App\Exceptions\EmailProviderException;
use App\Models\Master\EmailAccount;
use App\Services\Mail\OAuth\OAuthConnectionService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class OutboundMailService
{
    public function __construct(
        private OAuthConnectionService $oauth,
    ) {}

    public function sendForType(string $typeCode, Mailable $mailable, string $to): void
    {
        $account = $this->accountForType($typeCode);

        if ($account === null) {
            Log::error('No email account is assigned to this email type.', [
                'email_type' => $typeCode,
            ]);

            return;
        }

        if ($account->usesOAuth()) {
            $account = $this->oauth->ensureValidAccessToken($account);
        }

        $mailable->from($account->from_email, $account->from_name);
        $mailable->to($to);

        match ($account->mailer) {
            EmailMailer::Smtp => $this->sendSmtp($account, $mailable, $to),
            EmailMailer::GmailApi => $this->sendGmail($account, $mailable, $to),
            EmailMailer::GraphApi => $this->sendGraph($account, $mailable, $to),
        };
    }

    public function accountForType(string $typeCode): ?EmailAccount
    {
        return EmailAccount::query()
            ->forType($typeCode)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    private function sendSmtp(EmailAccount $account, Mailable $mailable, string $to): void
    {
        if (blank($account->smtp_host) || $account->smtp_port === null) {
            Log::error('SMTP email account is missing host or port.', [
                'email_account_id' => $account->id,
            ]);

            throw EmailProviderException::sendFailed();
        }

        $mailer = 'email_account_'.$account->id;

        config([
            "mail.mailers.{$mailer}" => [
                'transport' => 'smtp',
                'host' => $account->smtp_host,
                'port' => $account->smtp_port,
                'encryption' => $account->smtp_encryption === SmtpEncryption::None
                    ? null
                    : $account->smtp_encryption?->value,
                'username' => $account->username ?: $account->from_email,
                'password' => $account->password,
                'timeout' => 15,
            ],
        ]);

        try {
            Mail::mailer($mailer)->to($to)->send($mailable);
        } catch (Throwable) {
            Log::error('SMTP send failed.', [
                'email_account_id' => $account->id,
            ]);

            throw EmailProviderException::sendFailed();
        }
    }

    private function sendGmail(EmailAccount $account, Mailable $mailable, string $to): void
    {
        $response = $this->http()
            ->withToken((string) $account->oauth_access_token)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $this->gmailRawMessage($mailable, $account, $to),
            ]);

        if ($response->successful()) {
            return;
        }

        Log::error('Gmail send failed.', [
            'email_account_id' => $account->id,
            'status' => $response->status(),
        ]);

        throw EmailProviderException::sendFailed();
    }

    private function sendGraph(EmailAccount $account, Mailable $mailable, string $to): void
    {
        $content = $this->renderedContent($mailable);

        $response = $this->http()
            ->withToken((string) $account->oauth_access_token)
            ->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                'message' => [
                    'subject' => $content['subject'],
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $content['html'],
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $to,
                            ],
                        ],
                    ],
                ],
                'saveToSentItems' => true,
            ]);

        if ($response->successful()) {
            return;
        }

        Log::error('Microsoft Graph send failed.', [
            'email_account_id' => $account->id,
            'status' => $response->status(),
        ]);

        throw EmailProviderException::sendFailed();
    }

    private function gmailRawMessage(Mailable $mailable, EmailAccount $account, string $to): string
    {
        $content = $this->renderedContent($mailable);

        $email = (new Email)
            ->from(new Address($account->from_email, $account->from_name))
            ->to($to)
            ->subject($content['subject'])
            ->html($content['html']);

        return rtrim(strtr(base64_encode($email->toString()), '+/', '-_'), '=');
    }

    /**
     * @return array{subject: string, html: string}
     */
    private function renderedContent(Mailable $mailable): array
    {
        $html = $mailable->render();

        return [
            'subject' => $mailable->subject,
            'html' => $html,
        ];
    }

    private function http(): PendingRequest
    {
        return Http::connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }
}
