<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SearchInternetTool;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class GermanyWorkflowAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public User $user) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a workflow planner focused on problems in Germany.

Your job:
1. Understand the user's problem.
2. Build a practical workflow with actionable steps.
3. Use the internet search tool to provide useful source links.

Response rules:
- Return ONLY valid JSON.
- Do not include markdown, code fences, or extra text.
- JSON shape:
{
  "title": "short workflow title",
  "summary": "short summary of what to do",
  "steps": [
    {"title": "step title", "description": "what to do", "status": "pending"}
  ],
  "links": [
    {"title": "source title", "url": "https://..."}
  ]
}
- Keep 3 to 7 steps.
- Keep 2 to 6 links.
- Use only valid absolute URLs.
PROMPT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchInternetTool,
        ];
    }
}
