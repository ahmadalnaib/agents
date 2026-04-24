<?php

namespace App\Http\Controllers;

use App\Ai\Agents\GermanyWorkflowAgent;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowAdvisorController extends Controller
{
    public function advice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'problem' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $rawResponse = (string) GermanyWorkflowAgent::make($request->user())
            ->forUser($request->user())
            ->prompt($validated['problem']);

        $decoded = json_decode($rawResponse, true);

        if (! is_array($decoded)) {
            $decoded = [
                'title' => 'Germany Problem Workflow',
                'summary' => $rawResponse,
                'steps' => [
                    [
                        'title' => 'Review the proposed solution',
                        'description' => $rawResponse,
                        'status' => 'pending',
                    ],
                ],
                'links' => [],
            ];
        }

        $title = trim((string) ($decoded['title'] ?? 'Germany Problem Workflow'));
        $summary = trim((string) ($decoded['summary'] ?? ''));

        $steps = collect($decoded['steps'] ?? [])
            ->filter(fn(mixed $step): bool => is_array($step))
            ->map(function (array $step): array {
                $status = (string) ($step['status'] ?? 'pending');

                return [
                    'title' => trim((string) ($step['title'] ?? 'Untitled Step')),
                    'description' => trim((string) ($step['description'] ?? '')),
                    'status' => $status === 'done' ? 'done' : 'pending',
                ];
            })
            ->filter(fn(array $step): bool => $step['title'] !== '' && $step['description'] !== '')
            ->values();

        if ($steps->isEmpty()) {
            $steps = collect([
                [
                    'title' => 'Clarify your main issue',
                    'description' => $summary !== '' ? $summary : $validated['problem'],
                    'status' => 'pending',
                ],
            ]);
        }

        $links = collect($decoded['links'] ?? [])
            ->filter(fn(mixed $link): bool => is_array($link))
            ->map(fn(array $link): array => [
                'title' => trim((string) ($link['title'] ?? 'Reference')),
                'url' => trim((string) ($link['url'] ?? '')),
            ])
            ->filter(fn(array $link): bool => filter_var($link['url'], FILTER_VALIDATE_URL) !== false)
            ->unique('url')
            ->take(6)
            ->values();

        $workflow = DB::transaction(function () use ($request, $title, $validated, $steps): Workflow {
            $workflow = Workflow::create([
                'user_id' => $request->user()?->id,
                'title' => $title,
                'input' => $validated['problem'],
                'status' => 'in_progress',
            ]);

            $steps->each(function (array $step, int $index) use ($workflow): void {
                $workflow->steps()->create([
                    'step_number' => $index + 1,
                    'title' => $step['title'],
                    'description' => $step['description'],
                    'status' => $step['status'],
                ]);
            });

            return $workflow->load('steps');
        });

        return response()->json([
            'workflow' => [
                'id' => $workflow->id,
                'title' => $workflow->title,
                'summary' => $summary,
                'status' => $workflow->status,
            ],
            'steps' => $workflow->steps
                ->sortBy('step_number')
                ->values()
                ->map(fn($step): array => [
                    'number' => $step->step_number,
                    'title' => $step->title,
                    'description' => $step->description,
                    'status' => $step->status,
                ]),
            'links' => $links,
            'raw' => $rawResponse,
        ]);
    }
}
