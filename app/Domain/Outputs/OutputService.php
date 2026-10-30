<?php

namespace App\Domain\Outputs;

use App\Domain\Activity\ActivityLogger;
use App\Models\Output;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\OutputType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OutputService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param array{
     *     title: string,
     *     output_type?: OutputType|string,
     *     project_id?: int|null,
     *     task_id?: int|null,
     *     evidence_url?: string|null,
     *     produced_at?: string|Carbon|null,
     *     note?: string|null
     * } $data
     */
    public function record(User $user, array $data): Output
    {
        return DB::transaction(function () use ($user, $data) {
            $outputType = match (true) {
                ($data['output_type'] ?? null) instanceof OutputType => $data['output_type'],
                is_string($data['output_type'] ?? null) => OutputType::tryFrom($data['output_type']) ?? OutputType::Other,
                default => OutputType::Other,
            };

            $output = Output::create([
                'user_id' => $user->id,
                'project_id' => $data['project_id'] ?? null,
                'task_id' => $data['task_id'] ?? null,
                'title' => $data['title'],
                'output_type' => $outputType,
                'evidence_url' => $data['evidence_url'] ?? null,
                'produced_at' => $data['produced_at'] ?? now()->toDateString(),
                'note' => $data['note'] ?? null,
            ]);

            // Sentuh last_activity_at project jika ada (Bagian 7.10)
            if ($output->project_id && $project = Project::find($output->project_id)) {
                $project->last_activity_at = now();
                $project->saveQuietly();
            }

            $this->logger->log($output, ActivityEvent::OutputAdded, null, [
                'title' => $output->title,
                'output_type' => $output->output_type->value,
            ]);

            return $output;
        });
    }

    public function delete(Output $output): void
    {
        $output->delete();
    }
}
