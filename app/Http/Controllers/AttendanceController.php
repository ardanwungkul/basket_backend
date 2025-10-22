<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\TrainingSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::query();

        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
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

        // Cek status member
        $member = Member::find($request->member_id);
        if (! $member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        if ($member->status !== 'active') {
            return response()->json(['message' => 'Member tidak aktif. Tidak dapat melakukan absensi.'], 403);
        }

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

    public function scanQR(Request $request)
    {
        $request->validate([
            'encrypted_member_id' => 'required|string',
            'training_schedule_id' => 'required|uuid',
        ]);

        try {
            // Decode base64
            $decoded = base64_decode($request->encrypted_member_id);
            if (! $decoded) {
                return response()->json(['message' => 'QR Code tidak valid'], 400);
            }

            $member_id = $decoded;

            // Validasi member
            $member = Member::find($member_id);
            if (! $member) {
                return response()->json(['message' => 'Member tidak ditemukan'], 404);
            }

            if ($member->status !== 'active') {
                return response()->json(['message' => 'Member tidak aktif. Tidak dapat melakukan absensi.'], 403);
            }

            // Validasi jadwal latihan
            $trainingSchedule = TrainingSchedule::find($request->training_schedule_id);
            if (! $trainingSchedule) {
                return response()->json(['message' => 'Jadwal latihan tidak ditemukan'], 404);
            }

            // Cek apakah sudah absen hari ini untuk jadwal ini
            $existingAttendance = Attendance::where('member_id', $member_id)
                ->where('training_schedule_id', $request->training_schedule_id)
                ->where('date', Carbon::today()->toDateString())
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'message' => 'Member sudah melakukan absensi untuk jadwal ini hari ini',
                    'data' => $existingAttendance,
                ], 409);
            }

            $coach_id = Auth::id() ?? Str::uuid();

            // Buat absensi
            $attendance = Attendance::create([
                'id' => Str::uuid(),
                'member_id' => $member_id,
                'coach_id' => $coach_id,
                'training_schedule_id' => $request->training_schedule_id,
                'date' => Carbon::today()->toDateString(),
                'time' => Carbon::now()->format('H:i:s'),
                'method' => 'qr',
                'status' => 'present',
                'reason' => null,
            ]);

            return response()->json([
                'message' => 'Absensi berhasil disimpan via QR Scan',
                'data' => $attendance->load('member'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses QR Code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update absensi (ubah status atau alasan)
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // Cek status member saat update
        $member = Member::find($attendance->member_id);
        if ($member && $member->status !== 'active') {
            return response()->json(['message' => 'Member tidak aktif. Tidak dapat mengubah absensi.'], 403);
        }

        $attendance->update($request->only('status', 'reason'));

        return response()->json([
            'message' => 'Status absensi diperbarui',
            'data' => $attendance,
        ]);
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json(['message' => 'Data absensi dihapus']);
    }
}
