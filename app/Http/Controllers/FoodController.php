<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    // API Get Data Food All
    public function all(Request $request)
    {
        // Create Parameter Food Filter
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $types = $request->input('types');

        // Create Parameter Food by Price
        $price_from = $request->input('price_from');
        $price_to = $request->input('price_to');

        // Create Parameter Food by Rate
        $rate_from = $request->input('rate_from');
        $rate_to = $request->input('rate_to');

        // Get Data Food By id
        if ($id) {
            $food = Food::find($id);
            if ($food) {
                return ResponseFormatter::success(
                    $food,
                    'Success Get Data Food By ID'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data Food Not Found',
                    404
                );
            }
        }

        $food = Food::query();

        // Get Data Food By Name
        if ($name) {
            $food->where('name', 'like', '%' . $name . '%');
        }

        // Get Data Food By Types
        if ($types) {
            $food->where('types', 'like', '%' . $types . '%');
        }

        // Get Data Food By Price From
        if ($price_from) {
            $food->where('price', '>=', $price_from);
        }

        // Get Data Food By Price To
        if ($price_to) {
            $food->where('price', '<=', $price_to);
        }

        // Get Data Food By Price Rate From
        if ($rate_from) {
            $food->where('rate', '>=', $rate_from);
        }

        // Get Data Food By Price Rate to
        if ($rate_to) {
            $food->where('rate', '>=', $rate_to);
        }

        // Response Data
        return ResponseFormatter::success(
            $food->paginate($limit),
            'Success Get Data List Food'
        );
    }
}
