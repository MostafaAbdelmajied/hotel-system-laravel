<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected readonly PaymentService $payment){}

    public function success(Request $request)
    {
        return $this->payment->handleSuccess($request);
    }

    public function cancel(Payment $payment)
    {
        return $this->payment->handleCancel($payment);
    }

    public function webhook(Request $request)
    {
        return $this->payment->handleWebhook($request);
    }
}
