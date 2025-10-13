<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardianController extends Controller
{
    public function getByAuth()
    {
        $auth = Auth::user();
        $data = $auth->parent;
        return response()->json([
            'data' => $data,
            'message' => 'Berhasil Mendapatkan Data'
        ]);
    }
    public function index()
    {
        $data = Guardian::all();
        return response()->json([
            'data' => $data,
            'message' => 'Berhasil Mendapatkan Data'
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Guardian $guardian)
    {
        //
    }

    public function edit(Guardian $guardian)
    {
        //
    }

    public function update(Request $request, Guardian $guardian)
    {
        //
    }

    public function destroy(Guardian $guardian)
    {
        //
    }
}
