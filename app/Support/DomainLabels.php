<?php

namespace App\Support;

use Illuminate\Support\Str;

class DomainLabels
{
    public const QUOTATION_STATUSES = [
        'draft' => 'Borrador',
        'sent' => 'Enviada',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'expired' => 'Vencida',
    ];

    public const QUOTATION_STATUS_CLASSES = [
        'draft' => 'bg-gray-100 text-gray-800',
        'sent' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-amber-100 text-amber-800',
    ];

    public const CLIENT_TYPES = [
        'prospect' => 'Prospecto',
        'customer' => 'Cliente',
        'active' => 'Activo',
        'past' => 'Anterior',
    ];

    public const EVENT_STATUS_CLASSES = [
        'reserved' => 'bg-amber-100 text-amber-800',
        'tentative' => 'bg-blue-100 text-blue-800',
        'confirmed' => 'bg-green-100 text-green-800',
        'completed' => 'bg-teal-100 text-teal-800',
        'cancelled' => 'bg-gray-200 text-gray-800',
    ];

    public const TRANSACTION_STATUSES = [
        'pending' => 'Pendiente histórico',
        'paid' => 'Pagado',
        'cancelled' => 'Cancelado',
    ];

    public const TRANSACTION_STATUS_CLASSES = [
        'pending' => 'bg-amber-100 text-amber-800',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-gray-200 text-gray-800',
    ];

    public const TRANSACTION_TYPES = [
        'income' => 'Ingreso',
        'expense' => 'Gasto',
    ];

    public const TRANSACTION_METHODS = [
        'transfer' => 'Transferencia',
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'other' => 'Otro',
    ];

    public static function quotationStatus(?string $status): string
    {
        return self::QUOTATION_STATUSES[$status] ?? (string) $status;
    }

    public static function quotationStatusClasses(?string $status): string
    {
        return self::QUOTATION_STATUS_CLASSES[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public static function clientType(?string $type): string
    {
        return self::CLIENT_TYPES[$type] ?? (string) $type;
    }

    public static function eventStatusClasses(?string $status): string
    {
        return self::EVENT_STATUS_CLASSES[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public static function transactionStatus(?string $status): string
    {
        return self::TRANSACTION_STATUSES[$status] ?? (string) $status;
    }

    public static function transactionStatusClasses(?string $status): string
    {
        return self::TRANSACTION_STATUS_CLASSES[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public static function transactionType(?string $type): string
    {
        return self::TRANSACTION_TYPES[$type] ?? (string) $type;
    }

    public static function transactionMethod(?string $method): string
    {
        return self::TRANSACTION_METHODS[$method] ?? ($method ?: '-');
    }

    public static function role(?string $role): string
    {
        return Str::ucfirst((string) $role);
    }
}
