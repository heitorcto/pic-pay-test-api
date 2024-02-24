<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductControler extends Controller
{
    public function findAll(Request $request)
    {
        return response()->json(
            Product::leftJoin('product_categories', 'category_id', 'product_categories.id')
                ->paginate(15, [
                    'products.name',
                    'products.value',
                    'products.description',
                    'products.created_at',
                    'product_categories.name AS category'
                ]),
            200
        );
    }
}
