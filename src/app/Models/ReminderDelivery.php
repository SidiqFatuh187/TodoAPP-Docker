<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReminderDelivery extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public static function identity(int $userId, string $type, ?int $taskId, string $revision, string $urgency = ''): string
    {
        return hash('sha256', json_encode([$userId, $type, $taskId, $revision, $urgency], JSON_THROW_ON_ERROR));
    }

    public static function deadlineRevision(Todo $todo): string
    {
        return Carbon::parse($todo->getRawOriginal('deadline'), 'Asia/Jakarta')->format('Y-m-d H:i:s');
    }
}
