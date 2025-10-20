<?php

namespace App\Http\Controllers;

use App\Models\MemberBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberBillController extends Controller
{
    public function getByAuth(Request $request)
    {
        $auth = Auth::user();
        $query = MemberBill::whereHas('member', function ($query) use ($auth) {
            $query->where('parent_id', $auth->parent->id);
        });

        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->get();
        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MemberBill $memberBill)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MemberBill $memberBill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MemberBill $memberBill)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MemberBill $memberBill)
    {
        //
    }
}
