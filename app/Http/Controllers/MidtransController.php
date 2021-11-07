<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Midtrans\Notification;

class MidtransController extends Controller
{
    // API Checkout Midtrans Callback
    public function callback(Request $request)
    {
        // Configuration Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Create Instance Midtrans Notification
        $notification = new Notification();

        // Assign to Variable
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // Search Transaction by ID
        $transaction = Transaction::findOrFail($order_id);

        // Handle Notification Status Midtrans
        if ($status == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $transaction->status = 'PENDING';
                } else {
                    $transaction->status = 'unfinish';
                }
            }
        } else if ($status == 'settlement') {
            $transaction->status = 'SUCCESS';
        } else if ($status == 'pending') {
            $transaction->status = 'PENDING';
        } else if ($status == 'deny') {
            $transaction->status = 'CANCELLED';
        } else if ($status == 'expire') {
            $transaction->status = 'CANCELLED';
        } else if ($status == 'cancel') {
            $transaction->status = 'CANCELLED';
        }

        // Save Transaction
        $transaction->save();
    }

    // Create Page Success Transaction
    public function success()
    {
        return view('midtrans.success');
    }

    // Create Page unfinish Transaction
    public function unfinish()
    {
        return view('midtrans.unfinish');
    }

    // Create Page Error Transaction
    public function error()
    {
        return view('midtrans.error');
    }
}
