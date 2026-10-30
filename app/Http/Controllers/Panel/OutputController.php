<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Outputs\OutputService;
use App\Http\Controllers\Controller;
use App\Models\Output;
use App\Support\Enums\OutputType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OutputController extends Controller
{
    public function __construct(private readonly OutputService $outputs) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $projects = $user->projects()->orderBy('name')->get(['id', 'name']);

        $query = Output::query()
            ->where('user_id', $user->id)
            ->with(['project', 'task'])
            ->orderByDesc('produced_at')
            ->orderByDesc('id');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if ($request->filled('output_type')) {
            $query->where('output_type', $request->string('output_type'));
        }

        $outputs = $query->paginate(15)->withQueryString();

        return view('panel.outputs.index', compact('outputs', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'output_type' => ['required', Rule::enum(OutputType::class)],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('user_id', $request->user()->id)],
            'task_id' => ['nullable', 'integer'],
            'evidence_url' => ['nullable', 'url', 'max:1000'],
            'produced_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->outputs->record($request->user(), $data);

        return redirect()->back()->with('toast', 'Output hasil karya berhasil dicatat!');
    }

    public function destroy(Request $request, Output $output): RedirectResponse
    {
        abort_unless($output->user_id === $request->user()->id, 403);
        $this->outputs->delete($output);

        return redirect()->route('panel.outputs.index')->with('toast', 'Catatan output dihapus.');
    }
}

