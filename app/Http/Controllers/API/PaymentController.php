<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\CustomerPayment;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\PurchasedUnit;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentSummaryResource;

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
        $payments = Payment::whereIn('id', $customerPaymentIds);

        // var_dump(count($payments->get())); die;

        if (count($payments->get()) == 0) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            // Return a collection of $payments with pagination
            return $this->sendResponse(PaymentResource::collection($payments->latest('updated_at')->paginate()), 'RETRIEVE_SUCCESS');
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
        // get the authenticated user
        $customer = Auth::user();

        // make sure the payment request is valid
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'meter_id' => 'required|numeric|exists:meters,id', // TODO: validate the meter number
            // 'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string',
        ]);



        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // validate meter entry
        try {
            $meter = Meter::where('id', $request->meter_id)->where('customer_id', $customer->id)->first();

            if (!$meter) {
                return $this->sendError('NOT_FOUND', 428);
            }

            // payment amount should be greater than zero
            if ($request->amount <= 0) {
                return $this->sendError('INVALID_AMOUNT', 422);
            }

            // check payment method if cash make a bill
            if ($request->payment_method == 'CASH') {
                $bill = new Bill;
                $bill->title = 'Bill for meter ' . $meter->meter_number;
                $bill->customer_id = $customer->id;
                $bill->amount = (floatval($request->amount));
                $bill->reference_number = $request->reference_number ? $request->reference_number : Str::upper(referenceNumber());
                $bill->status = 'unpaid'; // We'll mark it as paid when the payment is made
                $bill->save();

                addLog("transaction", "[". $customer->email ."] requested a bill of TSH ". $request->amount ." for meter ". $meter->meter_number . " to be paid in CASH", "application");
            }

            // create a new payment
            $payment = new Payment;
            $payment->title = 'Payment for meter ' . $meter->meter_number;
            $payment->amount = (floatval($request->amount));
            $payment->payment_method = $request->payment_method ? $request->payment_method : 'CASH';
            $payment->reference_number = $request->reference_number ? $request->reference_number : Str::upper(referenceNumber());
            $payment->save();

            // create a new customer_payment
            $customerPayment = new CustomerPayment;
            $customerPayment->customer_id = $customer->id;
            $customerPayment->payment_id = $payment->id;
            $customerPayment->save();

            addLog("transaction", "[". $customer->email ."] made a payment of TSH ". $request->amount ." for meter ". $meter->meter_number, "application");

            // calculate the corresponding units for the payment
            $units = $request->amount / config('constants.UNIT_PRICE'); // 1 unit = 1000 TSH

            // get and update the customer's units
            $purchaseUnits = new PurchasedUnit;
            $purchaseUnits->meter_id = $meter->id;
            $purchaseUnits->units = $units;
            $purchaseUnits->status = 1;
            $purchaseUnits->save();

            // get and update the customer's units
            // add failsafe to check if there's past readings on the meter
            $currentUnits = 0;

            if ($meter->readings->count() == 0) {
                $currentUnits = 0;
            } else {
                // fail safe for new meters
                if (is_null($meter->readings->last())){
                    $currentUnits = 0;
                } else {
                    $currentUnits = $meter->readings->last()->total_volume / config('constants.UNIT_CONVERSION_FACTOR');
                }
            }

            // $currentUnits = $meter->readings->last()->total_volume / config('constants.UNIT_PRICE');
            $newUnits = $currentUnits + $units;
            $newVolume = $newUnits * config('constants.UNIT_CONVERSION_FACTOR');

            // var_dump($newUnits); die;

            // update the meter's units
            // $meterReading = new MeterReading;
            // $meterReading->meter_id = $meter->id;
            // $meterReading->flow_rate = !is_null($meter->readings->last()) ? $meter->readings->last()->flow_rate : 0; // TODO:: get the flow rate from the meter GSM module
            // $meterReading->total_volume = $newVolume;
            // $meterReading->meter_reading_date = now();
            // $meterReading->meter_reading_status = 'normal';
            // $meterReading->save();

            addLog("transaction", "[". $customer->email ."] updated meter ". $meter->meter_number ." with new units of ". $newUnits, "application");
            // var_dump($meterReading); die;

            // return the payment in new resource
            $paymentSummary = [
                'payment_id' => $payment->id,
                'meter_id' => $meter->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'units' => $newUnits,
                'volume' => $newVolume,
            ];

            // $data = [
            //     'meter_id' => $meter->id,
            //     'units' => $newUnits,
            //     'volume' => $newVolume,
            // ];

            // var_dump($data); die;

            // call the method
            // $this->sendPaymentUpdate($data);

            return $this->sendResponse(new PaymentSummaryResource($paymentSummary), 'CREATE_SUCCESS');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->sendError('CREATE_ERROR', $th->getMessage());
        }
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
                return $this->sendError('RETRIEVE_AUTHORIZATION_ERROR', 'You are not authorized to view this payment.');
            }

            // Return a single payment
            return $this->sendResponse(new PaymentResource($payment), 'RETRIEVE_SUCCESS');
        }

    }

    // get the payments according to meter
    // public function getPaymentsByMeter($meterId)
    // {
    //     // get the meter
    //     $meter = Meter::find($meterId);

    //     // if not found, return error
    //     if (!$meter) {
    //         return $this->sendError('NOT_FOUND', 404);
    //     } else {
    //         // check if the meter belongs to the authenticated user
    //         if ($meter->customer_id != Auth::user()->id) {
    //             return $this->sendError('RETRIEVE_AUTHORIZATION_ERROR', 'You are not authorized to view this meter.');
    //         }
    //     }

    // }


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

    // miscellaneous functions

    // send response to meter about the payment
    // public function sendPaymentUpdate($data)
    // {
    //     Http::post('http://localhost:8000/api/webhook/meter/update', $data);
    // }
}
