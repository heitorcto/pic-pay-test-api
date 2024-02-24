<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\VendorLoginRequest;
use App\Http\Requests\VendorRegisterRequest;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VendorController extends Controller
{
    public function register(VendorRegisterRequest $request): JsonResponse
    {
        if (Vendor::create($request->validated())) {
            return response()->json([
                'message' => 'Success'
            ], 201);
        }

        return response()->json([
            'message' => 'Error, try again later'
        ], 500);
    }

    public function login(VendorLoginRequest $request): JsonResponse
    {
        $request->validated();

        $vendor = Vendor::where('email', $request->login)
            ->orWhere('cpf', $request->login)
            ->orWhere('cnpj', $request->login)
            ->first();

        if ($vendor) {
            if (Hash::check($request->password, $vendor->password)) {
                return response()->json([
                    'token' =>  $vendor->createToken('win')
                        ->plainTextToken
                ], 200);
            }
        }

        return response()->json([
            'error' => 'Vendor not found'
        ], 422);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(
            Vendor::where('id', $request->user()->id)
                ->first([
                    'name', 'email', 'cpf', 'cnpj', 'wallet'
                ]),
            200
        );
    }

    public function addProduct(ProductRequest $request)
    {
        $data = $request->validated();
        $data['vendor_id'] = $request->user()->id;

        if (Product::create($data)) {
            return response()->json([
                'message' => 'Success'
            ], 201);
        }

        return response()->json([
            'message' => 'Error, try again later'
        ], 500);
    }

    public function myProducts(Request $request)
    {
        return response()->json(
            Product::where('vendor_id', $request->user()->id)
                ->leftJoin('product_categories', 'category_id', 'product_categories.id')
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
