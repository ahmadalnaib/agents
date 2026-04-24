<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable ;

class ListProductTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Use this tool to list products based on a search query. The query can include keywords, category names, or other relevant information to filter the products.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $category = $request['category'] ?? null;

        $products = Product::when($category, function ($query) use ($category) {
            $query->whereHas('categories', function ($query) use ($category) {
                $query->where('title', $category);
            });
        })->get();

        return $products;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->required(),
        ];
    }
}
