<?php

namespace App\Http\Controllers;

use App\Models\TrainingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class TrainingScheduleController extends Controller
{
    public function index()
    {
        $data = TrainingSchedule::orderBy('date', 'desc')->get();
        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = TrainingSchedule::create([
            'id' => Str::uuid(),
            'title' => $request->title,
            'date' => $request->date,
        ]);

        return response()->json([
            'message' => 'Jadwal latihan berhasil ditambahkan',
            'data' => $schedule
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = TrainingSchedule::findOrFail($id);
        $schedule->update([
            'title' => $request->title,
            'date' => $request->date,
        ]);

        return response()->json([
            'message' => 'Jadwal latihan berhasil diupdate',
            'data' => $schedule
        ]);
    }

    public function destroy($id)
    {
        $schedule = TrainingSchedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'data' => $schedule,
            'message' => 'Berhasil Menghapus Data'
        ]);
    }
}
