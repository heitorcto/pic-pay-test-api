<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddMoneyRequest;
use App\Http\Requests\UserAddToCartRequest;
use App\Http\Requests\UserGiveUpRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserSendMoneyRequest;
use App\Mail\MoneyTranference;
use App\Models\Cart;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function register(UserRegisterRequest $request): JsonResponse
    {
        if (User::create($request->validated())) {
            return response()->json([
                'message' => 'Success'
            ], 201);
        }

        return response()->json([
            'message' => 'Error, try again later'
        ], 500);
    }

    public function login(UserLoginRequest $request): JsonResponse
    {
        $request->validated();

        $vendor = User::where('email', $request->login)
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
            User::where('id', $request->user()->id)
                ->first([
                    'name', 'email', 'cpf', 'cnpj', 'wallet'
                ]),
            200
        );
    }

    public function addMoney(UserAddMoneyRequest $request)
    {
        $user = User::where('id', $request->user()->id)
            ->first();

        $user->wallet = $user->wallet + $request->money;

        if ($user->save()) {
            return response()->json([
                'message' => 'Success'
            ], 200);
        }

        return response()->json([
            'error' => 'Error, try again later'
        ], 500);
    }

    public function addToCart(UserAddToCartRequest $request)
    {
        if (
            Cart::create(
                [
                    'user_id' => $request->user()->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity ?? 1,
                ]
            )
        ) {
            return response()->json([
                'message' => 'Success'
            ], 201);
        }

        return response()->json([
            'error' => 'Error, try again later'
        ], 500);
    }

    public function myCart(Request $request)
    {
        $cartBaseQuery = Cart::where('user_id', $request->user()->id)
            ->leftJoin('products', 'product_id', 'products.id');

        $cart = $cartBaseQuery->paginate(15, [
            'products.name',
            'products.value',
            'products.description',
        ]);

        $customCollect = collect(['total_cart_value' => $cartBaseQuery->sum('products.value')]);

        $data = $customCollect->merge($cart);

        return response()->json($data, 200);
    }

    public function checkout(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->leftJoin('products', 'product_id', 'products.id');

        $vendors = $cart->select('products.vendor_id', DB::raw('SUM(products.value) as total_price')) // Soma dos preços dos produtos para cada vendedor
            ->groupBy('products.vendor_id')
            ->get();

        if (TransferService::check()) {
            if ($request->user()->wallet >= $cart->sum('products.value')) {
                User::where('id', $request->user()->id)
                    ->update([
                        'wallet' => $request->user()->wallet - $cart->sum('products.value')
                    ]);

                foreach ($vendors as $vendor) {
                    $vendorModel = Vendor::where('id', $vendor->vendor_id)->first();

                    $vendorModel->wallet = $vendorModel->wallet + $vendor->total_price;

                    $vendorModel->save();

                    Mail::to($vendorModel->email)
                        ->send(new MoneyTranference($vendor->total_price));

                    Transaction::create([
                        'user_sender_id' => $request->user()->id,
                        'vendor_id' => $vendor->vendor_id,
                        'value' => $vendor->total_price,
                    ]);
                }

                Cart::where('user_id', $request->user()->id)
                    ->delete();

                return response()->json([
                    'message' => 'Success'
                ], 200);
            }
        }

        return response()->json([
            'error' => 'Error, try again later'
        ], 500);
    }

    public function transactions(Request $request)
    {
        return response()->json(
            Transaction::where('user_sender_id', $request->user()->id)
                ->leftJoin('vendors', 'vendor_id', 'vendors.id')
                ->paginate(15, ['vendors.name', 'transactions.value', 'transactions.created_at']),
            200
        );
    }

    public function giveUp(UserGiveUpRequest $request)
    {
        $transaction = Transaction::where('id', $request->transaction_id)
            ->where('give_up', 0)
            ->first();

        if ($transaction) {
            $transaction->give_up = true;

            $user = User::where('id', $transaction->user_sender_id)
                ->first();

            $user->wallet = $user->wallet + $transaction->value;

            $vendor = Vendor::where('id', $transaction->vendor_id)
                ->first();

            $vendor->wallet = $vendor->wallet - $transaction->value;

            $user->save();
            $vendor->save();
            $transaction->save();

            return response()->json(
                ['message' => 'Success'],
                200
            );
        }

        return response()->json(
            ['error' => 'Error, try again later'],
            500
        );
    }

    public function sendMoney(UserSendMoneyRequest $request)
    {
        if ($request->user()->wallet >= $request->value) {
            $userSender = User::where('id', $request->user()->id)->first();

            $userReceiver = User::where('id', $request->user_receiver_id)->first();

            $userReceiver->wallet = $userReceiver->wallet + $request->value;

            $userSender->wallet = $userSender->wallet - $request->value;

            $userReceiver->save();
            $userSender->save();

            return response()->json(
                ['message' => 'Success'],
                200
            );
        }

        return response()->json(
            ['error' => 'Error, try again later'],
            500
        );
    }
}
