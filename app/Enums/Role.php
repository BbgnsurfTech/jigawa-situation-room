<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin        = 'super_admin';
    case StateAdmin        = 'state_admin';
    case Supervisor        = 'supervisor';
    case WardAgent         = 'ward_agent';
    case PollingUnitAgent  = 'polling_unit_agent';

    public function isFieldAgent(): bool
    {
        return in_array($this, [self::WardAgent, self::PollingUnitAgent]);
    }

    public function canManageReports(): bool
    {
        return in_array($this, [self::SuperAdmin, self::StateAdmin, self::Supervisor]);
    }

    public function canAccessDashboard(): bool
    {
        return !$this->isFieldAgent();
    }

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin       => 'Super Administrator',
            self::StateAdmin       => 'State Administrator',
            self::Supervisor       => 'Supervisor',
            self::WardAgent        => 'Ward Agent',
            self::PollingUnitAgent => 'Polling Unit Agent',
        };
    }

    public static function fieldAgentValues(): array
    {
        return [self::WardAgent->value, self::PollingUnitAgent->value];
    }

    public static function managementValues(): array
    {
        return [self::SuperAdmin->value, self::StateAdmin->value, self::Supervisor->value];
    }
}
