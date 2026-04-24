<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchInternetTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search public internet sources and return links with short snippets for the given query.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) ($request['query'] ?? ''));

        if ($query === '') {
            return json_encode(['results' => []], JSON_UNESCAPED_SLASHES);
        }

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->retry(2, 200)
            ->acceptJson()
            ->get('https://api.duckduckgo.com/', [
                'q' => $query,
                'format' => 'json',
                'no_html' => 1,
                'skip_disambig' => 1,
            ]);

        if (! $response->successful()) {
            return json_encode(['results' => []], JSON_UNESCAPED_SLASHES);
        }

        $payload = $response->json();
        $topics = collect(data_get($payload, 'RelatedTopics', []))
            ->flatMap(function (mixed $item): array {
                if (is_array($item) && isset($item['Topics']) && is_array($item['Topics'])) {
                    return $item['Topics'];
                }

                return [$item];
            })
            ->filter(fn (mixed $item): bool => is_array($item) && ! empty($item['FirstURL']) && ! empty($item['Text']))
            ->take(6)
            ->map(fn (array $item): array => [
                'title' => mb_substr((string) $item['Text'], 0, 120),
                'url' => (string) $item['FirstURL'],
                'snippet' => (string) $item['Text'],
            ])
            ->values()
            ->all();

        if ($topics === [] && filled(data_get($payload, 'AbstractURL'))) {
            $topics[] = [
                'title' => (string) (data_get($payload, 'Heading') ?: 'Search result'),
                'url' => (string) data_get($payload, 'AbstractURL'),
                'snippet' => (string) data_get($payload, 'AbstractText', ''),
            ];
        }

        return json_encode(['results' => $topics], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required(),
        ];
    }
}
