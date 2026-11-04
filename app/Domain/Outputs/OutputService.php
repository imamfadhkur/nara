<?php

namespace App\Domain\Outputs;

use App\Domain\Activity\ActivityLogger;
use App\Models\Output;
use App\Models\OutputFile;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\OutputType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    public function record(User $user, array $data, ?UploadedFile $file = null): Output
    {
        return DB::transaction(function () use ($user, $data, $file) {
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

            // Simpan berkas lampiran jika diunggah
            if ($file) {
                $path = $file->store("outputs/{$user->id}", 'local');
                OutputFile::create([
                    'output_id' => $output->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }

            // Sentuh last_activity_at project jika ada (Bagian 7.10)
            if ($output->project_id && $project = Project::find($output->project_id)) {
                $project->last_activity_at = now();
                $project->saveQuietly();
            }

            $this->logger->log($output, ActivityEvent::OutputAdded, null, [
                'title' => $output->title,
                'output_type' => $output->output_type->value,
                'has_file' => $file !== null,
            ]);

            return $output;
        });
    }

    public function delete(Output $output): void
    {
        foreach ($output->files as $f) {
            Storage::disk('local')->delete($f->file_path);
            $f->delete();
        }

        $output->delete();
    }
}

