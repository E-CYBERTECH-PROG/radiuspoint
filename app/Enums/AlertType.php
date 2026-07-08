<?php

namespace App\Enums;

enum AlertType: string
{
    case RouterOffline = 'router_offline';
    case RouterOnline = 'router_online';
    case RouterCriticalLog = 'router_critical_log';
    case MpesaGatewayDown = 'mpesa_gateway_down';
    case MpesaGatewayRecovered = 'mpesa_gateway_recovered';

    public function label(): string
    {
        return match ($this) {
            self::RouterOffline => 'Router went offline',
            self::RouterOnline => 'Router came back online',
            self::RouterCriticalLog => 'Router reported a critical log entry',
            self::MpesaGatewayDown => 'M-Pesa payments appear to be failing',
            self::MpesaGatewayRecovered => 'M-Pesa payments are working again',
        };
    }
}
