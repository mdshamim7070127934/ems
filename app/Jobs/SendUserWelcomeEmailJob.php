<?php

namespace App\Jobs;

use App\Mail\UserRegisteredMail;
use App\Models\Master\EmailType;
use App\Models\User;
use App\Services\Mail\OutboundMailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendUserWelcomeEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 120];

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(public User $user)
    {
        $this->onQueue('emails');
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->user->id;
    }

    public function handle(OutboundMailService $mail): void
    {
        $mail->sendForType(
            EmailType::CODE_REGISTRATION,
            new UserRegisteredMail($this->user),
            $this->user->email,
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('User welcome email failed.', [
            'user_id' => $this->user->id,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }
}
