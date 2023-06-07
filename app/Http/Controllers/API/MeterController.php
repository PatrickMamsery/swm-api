<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        Log::info('MeterController@store');
        // Validate the request
        $request->validate([
            'meter_number' => 'required',
            'meter_type' => 'required',
            'meter_location' => 'required',
            'customer_id' => 'required'
        ]);

        // Create a meter
        $meter = new Meter();
        $meter->meter_number = $request->input('meter_number');
        $meter->meter_type = $request->input('meter_type');
        $meter->meter_location = $request->input('meter_location');
        $meter->meter_status = 'active'; // default to 'active
        $meter->customer_id = auth()->user()->id;
        $meter->save();

        // Return a single meter
        return $this->sendResponse(new MeterResource($meter), 'CREATE_SUCCESS');
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
     * Populate meter reading values
     *
     */
    public function storeMeterReadings(Request $request)
    {
        // validate the request
        $validator = Validator::make($request->all(), [
            'flow_rate' => 'required',
            'total_volume' => 'required',
            'timestamp' => 'required',
            'meter_reading_status' => 'nullable',
            'meter_reading_image' => 'nullable',
            'meter_reading_comment' => 'nullable',
            'meter_id' => 'required|exists:meters,id'
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // validate if meter exists
        if (!Meter::find($request->meter_id)) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            $meter = Meter::find($request->meter_id);
            // Get meters for the authenticated user
            // $meters = Meter::where('customer_id', Auth::user()->id)->pluck('id')->toArray();
            $meters = Meter::all()->pluck('id')->toArray();

            // if the meter is not owned by the authenticated user, return error
            if (!in_array($meter->id, $meters)) {
                return $this->sendError('NOT_FOUND', 404);
            } else {

                $meterReading = new MeterReading();
                $meterReading->meter_id = $meter->id;
                $meterReading->flow_rate = $request->input('flow_rate');
                $meterReading->total_volume = $request->input('total_volume');
                $meterReading->meter_reading_date = $request->input('timestamp');
                $meterReading->meter_reading_status = $request->input('meter_reading_status') ?? 'normal';
                $meterReading->save();

                // Return a single meter reading
                return $this->sendResponse(new MeterReadingResource($meterReading), 'RETRIEVE_SUCCESS');
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
