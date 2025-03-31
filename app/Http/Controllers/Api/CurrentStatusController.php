<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurrentStatus;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;

class CurrentStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statuses = CurrentStatus::pluck('status');
        return response()->json($statuses);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'status' => 'required|in:Free,Cashier Approve,At Kitchen,Cooking,Prepare,Already'
        ]);

        $status = new CurrentStatus();
        $status->status = $request->status;
        $status->save();
        return response()->json(['message' => 'Status updated successfully'], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
