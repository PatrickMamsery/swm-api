<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use App\Models\Bill;
use App\Http\Resources\BillResource;

class BillController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // get all bills for the authenticated user
        $customer = Auth::user();
        $bills = Bill::where('customer_id', $customer->id);

        if (count($bills->get()) == 0) {
            return $this->sendError('RETRIEVE_MANY_FAILED', 404);
        } else {
            // return a collection of $bills with pagination
            return $this->sendResponse(BillResource::collection($bills->latest('updated_at')->paginate()), 'RETRIEVE_SUCCESS');
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
        Log::info('BillController@store');
        // get the authenticated user
        $customer = Auth::user();

        // make sure the bill request is valid
        $validator = Validator::make($request->all(), [
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric',
            'bill_status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('VALIDATION_ERROR', 400, $validator->errors());
        }

        try {
            // create a new bill
            $bill = new Bill();
            $bill->customer_id = $customer->id;
            $bill->bill_date = $request->input('bill_date');
            $bill->bill_amount = $request->input('bill_amount');
            $bill->bill_status = $request->input('bill_status');
            $bill->save();

            // return the newly created bill
            return $this->sendResponse(new BillResource($bill), 'CREATE_SUCCESS');
        } catch (\Exception $e) {
            Log::error('BillController@store - ' . $e->getMessage());
            return $this->sendError('CREATE_FAILED', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        Log::info('BillController@show');
        // get the authenticated user
        $customer = Auth::user();

        // get the bill
        $bill = Bill::where('customer_id', $customer->id)->where('id', $id)->first();

        if (is_null($bill)) {
            return $this->sendError('RETRIEVE_FAILED', 404);
        } else {
            // return the bill
            return $this->sendResponse(new BillResource($bill), 'RETRIEVE_SUCCESS');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
