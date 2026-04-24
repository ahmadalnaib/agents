<?php

namespace App\Http\Controllers;

use App\Ai\Agents\WorkflowStepCoachAgent;
use App\Models\WorkflowStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    public function toggle(Request $request, WorkflowStep $step): JsonResponse
    {
        if ($step->workflow->user_id !== $request->user()?->id) {
            abort(403);
        }

        $step->update([
            'status' => $step->status === 'done' ? 'pending' : 'done',
        ]);

        $workflow = $step->workflow()->with('steps')->firstOrFail();
        $doneCount = $workflow->steps->where('status', 'done')->count();
        $totalCount = $workflow->steps->count();

        return response()->json([
            'step' => [
                'id' => $step->id,
                'status' => $step->status,
            ],
            'progress' => [
                'done' => $doneCount,
                'total' => $totalCount,
                'percentage' => $totalCount > 0 ? (int) round(($doneCount / $totalCount) * 100) : 0,
            ],
        ]);
    }

    public function ask(Request $request, WorkflowStep $step): JsonResponse
    {
        if ($step->workflow->user_id !== $request->user()?->id) {
            abort(403);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:1000'],
        ]);

        $prompt = trim(<<<PROMPT
Workflow title: {$step->workflow->title}
Original user problem: {$step->workflow->input}
Step {$step->step_number}: {$step->title}
Step description: {$step->description}

User question about this step:
{$validated['question']}

Answer in a practical way for Germany. Keep it concise.
PROMPT);

        $agent = WorkflowStepCoachAgent::make($request->user());

        if (filled($step->qa_conversation_id)) {
            $response = $agent
                ->continue($step->qa_conversation_id, as: $request->user())
                ->prompt($prompt);
        } else {
            $response = $agent
                ->forUser($request->user())
                ->prompt($prompt);

            if (filled($response->conversationId ?? null)) {
                $step->update([
                    'qa_conversation_id' => $response->conversationId,
                ]);
            }
        }

        return response()->json([
            'answer' => $this->formatAnswer((string) $response),
            'step' => [
                'id' => $step->id,
            ],
        ]);
    }

    private function formatAnswer(string $answer): string
    {
        $formatted = trim($answer);

        // Remove common markdown styles from provider output.
        $formatted = preg_replace('/\*\*(.*?)\*\*/', '$1', $formatted) ?? $formatted;
        $formatted = preg_replace('/__(.*?)__/', '$1', $formatted) ?? $formatted;
        $formatted = preg_replace('/`([^`]+)`/', '$1', $formatted) ?? $formatted;

        // Normalize bullet starts and whitespace.
        $formatted = preg_replace('/^\s*[-*]\s+/m', '- ', $formatted) ?? $formatted;
        $formatted = preg_replace('/\r\n?/', "\n", $formatted) ?? $formatted;
        $formatted = preg_replace('/\n{3,}/', "\n\n", $formatted) ?? $formatted;

        // Keep answers compact to avoid overwhelming UI cards.
        return mb_strimwidth(trim($formatted), 0, 900, '...');
    }
}
