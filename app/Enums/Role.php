<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'Owner';
    case Administrator = 'Administrator';
    case SupportAgent = 'Support Agent';
    case Viewer = 'Viewer';

    /**
     * All role values, e.g. for seeding.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /**
     * Human-friendly label for the role.
     */
    public function label(): string
    {
        return $this->value;
    }
}
