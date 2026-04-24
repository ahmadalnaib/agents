<?php

namespace App\Ai\Agents;

use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class WorkflowStepCoachAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(public User $user) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a practical assistant for Germany-related workflows.

Rules:
- Focus only on the step the user asks about.
- Give concrete actions, required documents, where to go, and expected timing when possible.
- Keep answers concise and practical.
- If information can vary by city or case, say so clearly.
- Return plain text only.
- Do not use markdown, bold text, bullet symbols, or code formatting.
- Do not ask follow-up questions.
- Keep the answer to at most 6 short lines.
- Prefer this line format:
    Documents: ...
    Where: ...
    Time: ...
    Notes: ...
PROMPT;
    }
}
