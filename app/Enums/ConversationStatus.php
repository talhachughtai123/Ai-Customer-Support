<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Waiting = 'waiting';
    case Assigned = 'assigned';
    case Closed = 'closed';
    case Spam = 'spam';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
