<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Http\Resources\PaymentResource;

class PaymentController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all payments for the authenticated user
        $customer = Auth::user();
        $customerPaymentIds = CustomerPayment::where('customer_id', $customer->id)->pluck('payment_id')->toArray();
        $payments = Payment::whereIn('id', $customerPaymentIds)->get();

        // var_dump($payments); die;

        if (count($payments) == 0) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            // Return a collection of $payments with pagination
            return $this->sendResponse(PaymentResource::collection($payments->paginate()), 'RETRIEVE_SUCCESS');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        // Get the payment
        // $payment = Payment::find($payment->id);

        // if not found, return error
        if (!Payment::find($payment->id)) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            $payment = Payment::find($payment->id);
            // Get payments for the authenticated user
            $payments = CustomerPayment::where('customer_id', Auth::user()->id)->pluck('payment_id')->toArray();

            // if not in the array of payments, return error
            if (!in_array($payment->id, $payments)) {
                return $this->sendError('RETRIEVE_ERROR', 'You are not authorized to view this payment.');
            }

            // Return a single payment
            return $this->sendResponse(new PaymentResource($payment), 'RETRIEVE_SUCCESS');
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
