<?php

namespace App\Domain\Activity;

use App\Models\ActivityLog;
use App\Models\Goal;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\ActivitySource;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu pintu pencatatan aktivitas (Bagian 6.9).
 *
 * Dipanggil oleh service, bukan observer, agar `source` dan konteks jelas.
 * Didaftarkan sebagai singleton (AppServiceProvider).
 */
class ActivityLogger
{
    private ActivitySource $source = ActivitySource::Web;

    /**
     * Jalankan $callback dengan sumber tertentu (mis. Telegram, System).
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function withSource(ActivitySource $source, Closure $callback): mixed
    {
        $previous = $this->source;
        $this->source = $source;

        try {
            return $callback();
        } finally {
            $this->source = $previous;
        }
    }

    public function currentSource(): ActivitySource
    {
        return $this->source;
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(Model $subject, ActivityEvent $event, ?array $before = null, ?array $after = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $this->ownerId($subject),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'event' => $event,
            'before_value' => $before,
            'after_value' => $after,
            'source' => $this->source,
            'created_at' => now(),
        ]);
    }

    /**
     * Catat perubahan atribut setelah save(): hanya kolom yang berubah.
     * Mengembalikan null bila tidak ada perubahan berarti.
     *
     * @param  list<string>  $ignore
     */
    public function logChanges(Model $subject, ActivityEvent $event = ActivityEvent::Updated, array $ignore = []): ?ActivityLog
    {
        $ignore = array_merge($ignore, ['updated_at', 'created_at']);
        $after = array_diff_key($subject->getChanges(), array_flip($ignore));

        if ($after === []) {
            return null;
        }

        $before = array_intersect_key($subject->getPrevious(), $after);

        return $this->log($subject, $event, $before, $after);
    }

    private function ownerId(Model $subject): ?int
    {
        $id = match (true) {
            $subject instanceof LifeDomain, $subject instanceof Project => $subject->user_id,
            $subject instanceof Goal => $subject->lifeDomain->user_id,
            $subject instanceof Task => $subject->project->user_id,
            $subject instanceof TaskSubtask => $subject->task->project->user_id,
            default => $subject->getAttribute('user_id'),
        };

        return $id ?? auth()->id();
    }
}

