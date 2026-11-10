<?php

namespace App\Domain\Tasks;

use App\Domain\DomainRuleException;
use App\Support\Enums\TaskStatus;

/**
 * Aturan transisi status task (Bagian 7.1).
 *
 *   todo ─► in_progress ─► done
 *     │          └─► waiting ─► in_progress / todo
 *     └──────────────► cancelled
 *   done ─► todo / in_progress   (dibuka kembali)
 *
 * Tambahan pragmatis (perlu dicatat di ADR): todo → done/waiting langsung,
 * in_progress → todo/cancelled, waiting → cancelled, cancelled → todo (pulihkan).
 */
class TaskStatusRules
{
    /** @var array<string, list<TaskStatus>> */
    private const TRANSITIONS = [
        'todo' => [TaskStatus::InProgress, TaskStatus::Waiting, TaskStatus::Done, TaskStatus::Cancelled],
        'in_progress' => [TaskStatus::Todo, TaskStatus::Waiting, TaskStatus::Done, TaskStatus::Cancelled],
        'waiting' => [TaskStatus::InProgress, TaskStatus::Todo, TaskStatus::Cancelled],
        'done' => [TaskStatus::Todo, TaskStatus::InProgress],
        'cancelled' => [TaskStatus::Todo],
    ];

    public static function canTransition(TaskStatus $from, TaskStatus $to): bool
    {
        return $from === $to || in_array($to, self::TRANSITIONS[$from->value], true);
    }

    /** @return list<TaskStatus> */
    public static function allowedFrom(TaskStatus $from): array
    {
        return [$from, ...self::TRANSITIONS[$from->value]];
    }

    /** @throws DomainRuleException */
    public static function assert(TaskStatus $from, TaskStatus $to, ?string $waitingReason): void
    {
        if (! self::canTransition($from, $to)) {
            throw new DomainRuleException(
                sprintf('Status tidak bisa diubah dari "%s" ke "%s".', $from->label(), $to->label()),
                'status',
            );
        }

        if ($to === TaskStatus::Waiting && blank($waitingReason)) {
            throw new DomainRuleException('Status "Menunggu" wajib disertai alasan menunggu.', 'waiting_reason');
        }
    }
}

