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
use App\Http\Resources\MeterTrendsResource;

use Carbon\Carbon;

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
        // Log::info('MeterController@store');
        // Validate the request
        $request->validate([
            'meter_number' => 'required',
            'meter_type' => 'required',
            'meter_location' => 'required',
            'customer_id' => 'required'
        ]);

        try {
            // Create a meter
            $meter = new Meter();
            $meter->meter_number = $request->input('meter_number');
            $meter->meter_type = $request->input('meter_type');
            $meter->meter_location = $request->input('meter_location');
            $meter->meter_status = 'active'; // default to 'active
            $meter->customer_id = auth()->user()->id;
            $meter->save();

            // fail safe: after creating a new meter create a new meter reading with default values
            $meterReading = new MeterReading();
            $meterReading->meter_id = $meter->id;
            $meterReading->flow_rate = 0;
            $meterReading->total_volume = 0;
            $meterReading->meter_reading_date = now();

        // Return a single meter
        return $this->sendResponse(new MeterResource($meter), 'CREATE_SUCCESS');
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->sendError('CREATE_FAILED', $th->getMessage());
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
            'units' => 'required',
            'timestamp' => 'required',
            'meter_reading_status' => 'nullable',
            'meter_reading_image' => 'nullable',
            'meter_reading_comment' => 'nullable',
            'meter_number' => 'required'
            // 'meter_id' => 'required|exists:meters,id'
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // validate if meter exists
        try {
            if (!Meter::where('meter_number', $request->meter_number)->first()) {
                return $this->sendError('NOT_FOUND', 404);
            } else {
                $meter = Meter::where('meter_number', $request->meter_number)->first();
                // Get meters for the authenticated user
                // $meters = Meter::where('customer_id', Auth::user()->id)->pluck('id')->toArray();
                $meters = Meter::all()->pluck('id')->toArray();

                // if the meter is not owned by the authenticated user, return error
                if (!in_array($meter->id, $meters)) {
                    return $this->sendError('NOT_FOUND', 404);
                } else {

                    // convert given units to actual volume
                    $units = $request->input('units');
                    $total_volume = $units * config('constants.UNIT_CONVERSION_FACTOR');

                    $meterReading = new MeterReading();
                    $meterReading->meter_id = $meter->id;
                    $meterReading->flow_rate = $request->input('flow_rate');
                    $meterReading->total_volume = $total_volume;
                    $meterReading->meter_reading_date = $request->input('timestamp');
                    $meterReading->meter_reading_status = $request->input('meter_reading_status') ?? 'normal';
                    $meterReading->save();

                    // Return a single meter reading
                    // return $this->sendResponse(new MeterReadingResource($meterReading), 'RETRIEVE_SUCCESS');
                    return response(['status' => 'success']);
                }
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response(['status' => 'failed', 'message' => $th->getMessage()], 500);
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

    // update meter
    public function updateMeter(Request $request)
    {
        // Process the meter update and perform necessary actions
        $response = [
            'meter_id' => $request->input('meter_id'),
            'units' => $request->input('units'),
            'volume' => $request->input('volume'),
        ];
        // Return a response indicating the update was handled successfully
        return response()->json([
            'data' => $response,
            'success' => true
        ]);
    }

    public function getUpdatedMeterReading(Request $request, $meterNumber)
    {
        try {
            $meter = Meter::where('meter_number', $meterNumber)->first();

            // var_dump($meter); die;

            if (!$meter) {
                return $this->sendError('NOT_FOUND', 404);
            } else {
                $meterReading = MeterReading::where('meter_id', $meter->id)->latest()->first();

                // var_dump($meterReading); die;

                $data = [
                    'units' => ($meterReading->total_volume) / config('constants.UNIT_CONVERSION_FACTOR'),
                    'flow_rate' => $meterReading->flow_rate
                ];

                return response($data, 200);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response(['status' => 'failed', 'message' => $th->getMessage()], 500);
        }
    }

    // get meter trends
    public function getMeterTrends(){
        try {
            // Program execution
            // 1. Get all meters for the authenticated user
            // 2. Get all meter readings for each meter
            // 3. Group meter readings by
            //      a. Date
            //      b. Meter
            // 4. Get the sum of the total volume for each meter, for each date and convert to units
            // 5. Return the data, grouped by date and categorized by meter

            // Get meters for the authenticated user
            $meters = Meter::where('customer_id', Auth::user()->id)->pluck('id')->toArray();

            // Get meter readings for each meter
            $meterReadings = MeterReading::whereIn('meter_id', $meters)->get();

            // changed manipulation, getting values on a weekly basis
            // $startDate = Carbon::now()->subDays(7);
            // $endDate = Carbon::now();
            // $dates = [];


            // while ($startDate <= $endDate) {
            //     $formattedDate = $startDate->format('D, d M');
            //     $dates[] = $formattedDate;

            //     $startDate->addDay();
            // }

            // var_dump($data); die;

            // Group meter readings by date and meter
            $meterReadings = $meterReadings->groupBy(function ($item, $key) {
                return [
                    'date' => $item->meter_reading_date->format('Y-m-d')
                ];
            })->map(function ($item, $key) {
                return $item->groupBy('meter_id');
            });

            // var_dump($meterReadings); die;

            // Get the sum of the total volume for each meter, for each date and convert to units, display the meter ids too
            $meterReadings = $meterReadings->map(function ($item, $key) {
                return $item->map(function ($item, $key) {
                    return [
                        'meter_id' => $key,
                        'units' => $item->sum('total_volume') / config('constants.UNIT_CONVERSION_FACTOR')
                    ];
                });
            });

            // $data = [
            //     'dates' => $dates,
            //     'meters' => $meterReadings
            // ];

            // var_dump($data); die;


            // Return the data, grouped by date and categorized by meter
            return $this->sendResponse(new MeterTrendsResource($meterReadings), 'RETRIEVE_SUCCESS');


        } catch (\Throwable $th) {
            Log::error($th);
            return $this->sendError('SERVER_ERROR', $th->getMessage(), 500);
        }
    }

    public function getMeterTrendsV2()
    {
        try {
            // get all meters for the authenticated user
            $meters = Meter::where('customer_id', Auth::user()->id)->pluck('id')->toArray();

            // Get meter readings for each meter
            $meterReadings = MeterReading::whereIn('meter_id', $meters)->get();

            $meterReadings = $meterReadings->groupBy(function ($item, $key) {
                return [
                    'date' => $item->meter_reading_date->format('Y-m-d')
                ];
            })->map(function ($item, $key) {
                return $item->groupBy('meter_id');
            });

            var_dump($meterReadings); die;

            $meterReadings = $meterReadings->map(function ($item, $key) {
                return $item->map(function ($item, $key) {
                    return [
                        'meter_id' => $key,
                        'units' => $item->sum('total_volume') / config('constants.UNIT_CONVERSION_FACTOR')
                    ];
                });
            });

            var_dump($meterReadings); die;

            // get values on a weekly basis
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now();
            $dates = [];

            while ($startDate <= $endDate) {
                $formattedDate = $startDate->format('D, d M');
                $dates[] = $formattedDate;

                $startDate->addDay();
            }

            var_dump("worse"); die;

        } catch (\Throwable $th) {
            Log::error($th);
            return $this->sendError('SERVER_ERROR', $th->getMessage(), 500);
        }
    }
}
