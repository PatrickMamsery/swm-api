<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Http\Resources\MeterResource;
use App\Http\Resources\MeterReadingResource;

class MeterController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all meters for the authenticated user
        $customer = Auth::user();
        $meters = Meter::where('customer_id', $customer->id)->get();

        if (count($meters) == 0) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            // Return a collection of $meters with pagination
            return $this->sendResponse(MeterResource::collection($meters), 'RETRIEVE_SUCCESS');
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // if not found, return error
        if (!Meter::find($id)) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            $meter = Meter::find($id);
            // Get meters for the authenticated user
            $meters = Meter::where('customer_id', Auth::user()->id)->pluck('id')->toArray();
            // if the meter is not owned by the authenticated user, return error
            if (!in_array($meter->id, $meters)) {
                return $this->sendError('NOT_FOUND', 404);
            } else {
                // Return a single meter
                return $this->sendResponse(new MeterResource($meter), 'RETRIEVE_SUCCESS');
            }
        }
    }

    public function getMeterReadings($id)
    {
        // if not found, return error
        if (!Meter::find($id)) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            $meter = Meter::find($id);
            // Get meters for the authenticated user
            $meters = Meter::where('customer_id', Auth::user()->id)->pluck('id')->toArray();

            // if the meter is not owned by the authenticated user, return error
            if (!in_array($meter->id, $meters)) {
                return $this->sendError('NOT_FOUND', 404);
            } else {
                // Return a single meter reading
                $meterReadings = MeterReading::where('meter_id', $meter->id)->paginate();
                return $this->sendResponse(MeterReadingResource::collection($meterReadings), 'RETRIEVE_SUCCESS');
            }
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
