<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use App\Models\Query;

use App\Http\Resources\QueryResource;

class QueryController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // get all queries for the authenticated user
        $customer = Auth::user();
        $queries = Query::where('customer_id', $customer->id);

        if (count($queries->get()) == 0) {
            return $this->sendError('RETRIEVE_MANY_FAILED', 404);
        } else {
            // return a collection of $queries with pagination
            return $this->sendResponse(QueryResource::collection($queries->latest('updated_at')->paginate()), 'RETRIEVE_SUCCESS');
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
        Log::info('QueryController@store');
        // get the authenticated user
        $customer = Auth::user();

        // make sure the query request is valid
        $validator = Validator::make($request->all(), [
            'query_date' => 'required|date',
            'query_action' => 'required|string',
            'description' => 'nullable|string',
            'query_status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('VALIDATION_ERROR', 400, $validator->errors());
        }

        try {
            // create a new query
            $query = new Query();
            $query->customer_id = $customer->id;
            $query->query_date = $request->input('query_date') ?? now();
            $query->query_action = $request->input('query_action') ?? 'QUERY';
            $query->description = $request->input('description') ?? '';
            $query->query_status = $request->input('query_status') ?? 'pending';
            $query->save();

            // create Log
            addLog("edit", "[" . $customer->email . "] " . "created a new query", "application");

            // return the newly created query
            return $this->sendResponse(new QueryResource($query), 'CREATE_SUCCESS');
        } catch (\Throwable $th) {
            //throw $th;
            Log::error($th);
            return $this->sendError('CREATE_FAILED', 500, $th->getMessage());
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
        if (!(Query::where('id', $id)->exists())) {
            return $this->sendError('NOT_FOUND', 404);
        } else {
            // return the query
            $query = Query::find($id);
            $queries = Query::where('customer_id', Auth::user()->id)->pluck('id')->toArray();

            if (!in_array($query->id, $queries)) {
                return $this->sendError('RETRIEVE_AUTHORIZATION_ERROR', 403);
            }

            return $this->sendResponse(new QueryResource(Query::find($id)), 'RETRIEVE_SUCCESS');
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
        Log::info('QueryController@update');
        // get the authenticated user
        $customer = Auth::user();

        // make sure the query request is valid
        $validator = Validator::make($request->all(), [
            'query_date' => 'nullable|date',
            'query_action' => 'nullable|string',
            'description' => 'nullable|string',
            'query_status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('VALIDATION_ERROR', 400, $validator->errors());
        }

        try {
            // update the query
            $query = Query::find($id);
            $queries = Query::where('customer_id', Auth::user()->id)->pluck('id')->toArray();

            if (!in_array($query->id, $queries)) {
                return $this->sendError('UPDATE_AUTHORIZATION_ERROR', 403);
            } else {
                $query->customer_id = $customer->id;
                $query->query_date = $request->input('query_date') ?? now();
                $query->query_action = $request->input('query_action') ? $request->input('query_action') : $query->query_action;
                $query->description = $request->input('description') ? $request->input('description') : $query->description;
                $query->query_status = $request->input('query_status') ? $request->input('query_status') : $query->query_status;
                $query->save();

                // create Log
                addLog("edit", "[" . $customer->email . "] " . "updated query " . $query->id, "application");

                // return the updated query
                return $this->sendResponse(new QueryResource($query), 'UPDATED_SUCCESSFULLY');
            }

        } catch (\Throwable $th) {
            //throw $th;
            Log::error($th);
            return $this->sendError('UPDATE_FAILED', 500, $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $query = Query::find($id);
        $queries = Query::where('customer_id', Auth::user()->id)->pluck('id')->toArray();

        if (!in_array($query->id, $queries)) {
            return $this->sendError('DELETE_AUTHORIZATION_ERROR', 403);
        } else {
            try {
                $query->delete();
                // create Log
                addLog("delete", "[" . Auth::user()->email . "] " . "deleted query " . $query->id, "application");
                return $this->sendResponse([], 'DELETED_SUCCESSFULLY');
            } catch (\Throwable $th) {
                //throw $th;
                Log::error($th);
                return $this->sendError('DELETE_FAILED', 500, $th->getMessage());
            }
        }
    }
}
