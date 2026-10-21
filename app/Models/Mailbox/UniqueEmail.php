<?php

namespace App\Models\Mailbox;

use Database\Factories\Mailbox\UniqueEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $email_id
 * @property Carbon|null $created_at
 */
#[Fillable([
    'email_id',
])]
class UniqueEmail extends Model
{
    /** @use HasFactory<UniqueEmailFactory> */
    use HasFactory;

    protected $table = 'ems_unique_email';

    public const UPDATED_AT = null;
}
