<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\TrainingSchedule;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = Attendance::with(['member', 'coach', 'trainingSchedule'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($attendances);
    }

     // Tambah absensi (manual input)
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|uuid',
            'training_schedule_id' => 'required|uuid',
            'status' => 'required|in:present,absent',
            'reason' => 'nullable|string',
        ]);

        $coach_id = Auth::id() ?? Str::uuid();
        $attendance = Attendance::create([
            'id' => Str::uuid(),
            'member_id' => $request->member_id,
            'coach_id' => $coach_id,
            'training_schedule_id' => $request->training_schedule_id,
            'date' => Carbon::today()->toDateString(),
            'time' => Carbon::now()->format('H:i:s'),
            'method' => 'manual',
            'status' => $request->status,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Absensi berhasil disimpan',
            'data' => $attendance,
        ]);
    }

    // Scan QR — otomatis input absensi berdasarkan QR member
   public function scan(Request $request)
    {
        $request->validate([
            'qr_code' => 'required',
            'training_schedule_id' => 'required|uuid',
        ]);

        try {
            $member_id = Crypt::decryptString($request->qr_code);
        } catch (\Exception $e) {
            return response()->json(['message' => 'QR Code tidak valid'], 400);
        }

        $member = Member::find($member_id);
        if (!$member) return response()->json(['message' => 'Member tidak ditemukan'], 404);

        $schedule = TrainingSchedule::find($request->training_schedule_id);
        if (!$schedule) return response()->json(['message' => 'Jadwal latihan tidak ditemukan'], 404);

        $already = Attendance::where('member_id', $member_id)
            ->where('training_schedule_id', $request->training_schedule_id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($already) {
            return response()->json(['message' => 'Member sudah absen hari ini'], 409);
        }

        $attendance = Attendance::create([
            'id' => Str::uuid(),
            'member_id' => $member_id,
            'coach_id' => Auth::id() ?? Str::uuid(),
            'training_schedule_id' => $request->training_schedule_id,
            'date' => Carbon::today()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'method' => 'qr',
            'status' => 'present',
            'reason' => null,
        ]);

        return response()->json([
            'message' => 'Absensi QR berhasil disimpan',
            'data' => [
                'member_name' => $member->name ?? '(Tanpa Nama)',
                'time' => $attendance->time,
                'date' => $attendance->date,
            ]
        ]);
    }

    // Update absensi (ubah status atau alasan)
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->update($request->only('status', 'reason'));

        return response()->json([
            'message' => 'Status absensi diperbarui',
            'data' => $attendance
        ]);
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json(['message' => 'Data absensi dihapus']);
    }
}
