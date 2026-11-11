<?php
namespace App\Services;

use App\Models\BuilderTemporary;
use App\Models\Product;

class CarBuilderHelper
{
    /**
     * Get common tags among all selected parts in the builder temporary table.
     */
    public static function getBuildCommonTags($userId)
    {
        $allTags = BuilderTemporary::where('builder_temporary.user_id', $userId)
            ->join('products', 'builder_temporary.product_id', '=', 'products.id')
            ->pluck('products.tags')
            ->toArray();

        if (empty($allTags)) {
            return [];
        }

        $common = [];
        $isFirstSet = true;

        for ($i = 0; $i < count($allTags); $i = $i + 1) {
            $tagsString = $allTags[$i];
            $specificTags = [];

            // If tags are NULL or empty, we treat it as 'universal'
            if ($tagsString === null || $tagsString === "") {
                $specificTags[] = 'universal';
            } else {
                $currentTags = explode(',', $tagsString);
                for ($j = 0; $j < count($currentTags); $j = $j + 1) {
                    $tag = strtolower(trim($currentTags[$j]));
                    
                    if ($tag !== "" && $tag !== "null") {
                        $specificTags[] = $tag;
                    }
                }
            }

            if ($isFirstSet === true) {
                $common = $specificTags;
                $isFirstSet = false;
            } else {
                // Check for Universal logic
                $hasUniversalInCommon = false;
                for ($u = 0; $u < count($common); $u = $u + 1) {
                    if ($common[$u] == 'universal') { 
                        $hasUniversalInCommon = true; 
                    }
                }

                $hasUniversalInCurrent = false;
                for ($u = 0; $u < count($specificTags); $u = $u + 1) {
                    if ($specificTags[$u] == 'universal') { 
                        $hasUniversalInCurrent = true; 
                    }
                }

                if ($hasUniversalInCurrent === true) {
                    // Current part fits everything, keep existing common tags
                    continue;
                } else if ($hasUniversalInCommon === true) {
                    // Previous parts were universal, now this specific part sets the standard
                    $common = $specificTags;
                } else {
                    // Manual intersection for common tags
                    $newCommon = [];
                    for ($k = 0; $k < count($common); $k = $k + 1) {
                        for ($l = 0; $l < count($specificTags); $l = $l + 1) {
                            if ($common[$k] == $specificTags[$l]) {
                                $newCommon[] = $common[$k];
                            }
                        }
                    }
                    $common = $newCommon;
                }
            }
        }

        return $common;
    }

    /**
     * Get product details safely using 'quantity' and 'status' columns.
     */
    public static function getProductForBuilder($id)
    {
        $product = Product::select('id', 'product_name', 'image', 'price', 'quantity', 'status', 'tags', 'hp')
            ->where('id', $id)
            ->first();

        return $product ? $product->toArray() : null;
    }
}