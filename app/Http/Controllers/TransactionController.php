<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class  TransactionController extends Controller
{
    // API Get Data Transaction All
    public function all(Request $request)
    {
        // Create Parameter Transaction Filter
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if ($id) {

            // Relation Transaction to user & food
            $transaction = Transaction::with(['food', 'user'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Success Get Data Transaction By ID'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data Transaction Not Found',
                    404
                );
            }
        }

        // Get Data Transaction by User was Login
        $transaction = Transaction::with(['food', 'user'])
            ->where('user_id', Auth::user()->id);

        // Get Data Transaction By ID Food
        if ($food_id) {
            $transaction->where('food_id', $food_id);
        }

        // Get Data Transaction By Status
        if ($status) {
            $transaction->where('status', $status);
        }

        // Response Data
        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Success Get Data List Transaction'
        );
    }

    // API Update Data Transaction by ID
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Success Update Transaction');
    }

    // API Checkout use Midtrans
    public function checkout(Request $request)
    {
        // Create Validation
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required',
        ]);

        // Create Transaction
        $transaction = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',
        ]);

        // Configuration Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // $key =  config('services.midtrans.serverKey');
        // var_dump($key);
        // die;

        // Call Transaction after created
        $transaction = Transaction::with(['food', 'user'])->find($transaction->id);

        // Create Variable for move to Midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enabled_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // Call Midtrans
        try {
            // Get Page Payment Midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

            // Update Data in Database
            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            // Return Data to API
            return ResponseFormatter::success($transaction, 'Transaction Success');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'Transaction Failed');
        }
    }
}
