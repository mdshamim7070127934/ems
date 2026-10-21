<?php

return [

    'sync_max_messages_per_run' => (int) env('MAILBOX_SYNC_MAX_MESSAGES', 100),

    'attachments_disk' => env('MAILBOX_ATTACHMENTS_DISK', 'local'),

    'attachments_directory' => 'email_attachments',

    'attachments_max_kilobytes' => (int) env('MAILBOX_ATTACHMENTS_MAX_KB', 25600),

    'attachments_allowed_extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        'zip', 'rar', '7z', 'txt', 'csv', 'eml',
    ],

    'queues' => [
        'send' => env('MAILBOX_SEND_QUEUE', 'emails'),
        'sync' => env('MAILBOX_SYNC_QUEUE', 'email-sync'),
    ],

];
