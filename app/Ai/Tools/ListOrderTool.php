<?php

namespace App\Ai\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListOrderTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Use this tool to list orders based on a search query. The query can include order status, notes, or other relevant information to filter the orders.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $value = $request['value'] ?? null;

        $orders = Order::when($value, function ($query) use ($value) {
            $query->where('status', 'like', "%{$value}%")
                ->orWhere('notes', 'like', "%{$value}%");
        })->get();

        return $orders;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value' => $schema->string()->required(),
        ];
    }
}
