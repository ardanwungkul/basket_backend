<?php

namespace App\Http\Controllers;

use App\Models\TrainingSchedule;
use App\Models\Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class TrainingScheduleController extends Controller
{
    public function index()
    {
        $data = TrainingSchedule::with('pivots.member')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'member_ids' => 'array',
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

        if ($request->has('member_ids') && is_array($request->member_ids)) {
            foreach ($request->member_ids as $memberId) {
                Pivot::create([
                    'id' => Str::uuid(),
                    'member_id' => $memberId,
                    'training_schedule_id' => $schedule->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Jadwal latihan berhasil ditambahkan',
            'data' => $schedule->load('pivots.member')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'member_ids' => 'array',
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

        if ($request->has('member_ids')) {
            $schedule->pivots()->delete();
            foreach ($request->member_ids as $memberId) {
                Pivot::create([
                    'id' => Str::uuid(),
                    'member_id' => $memberId,
                    'training_schedule_id' => $schedule->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Jadwal latihan berhasil diupdate',
            'data' => $schedule->load('pivots.member')
        ]);
    }

    public function destroy($id)
    {
        $schedule = TrainingSchedule::findOrFail($id);
        $schedule->pivots()->delete();
        $schedule->delete();

        return response()->json([
            'data' => $schedule,
            'message' => 'Berhasil Menghapus Data'
        ]);
    }
}
