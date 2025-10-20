<?php

namespace App\Http\Controllers;

use App\Models\TrainingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrainingScheduleController extends Controller
{
    
    public function index()
    {
        $schedules = TrainingSchedule::orderBy('date', 'asc')->get();
        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $schedule = TrainingSchedule::create([
            'id' => Str::uuid(),
            'date' => $request->date,
        ]);

        return response()->json([
            'message' => 'Jadwal latihan berhasil ditambahkan',
            'data' => $schedule
        ]);
    }

    public function destroy($id)
    {
        $schedule = TrainingSchedule::findOrFail($id);
        $schedule->delete();

        return response()->json(['message' => 'Jadwal latihan dihapus']);
    }
}
