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

    public function getByAuth(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Attendance::query();

        if ($user->user_type === 'parent') {
            $memberIds = $user->guardian->members->pluck('id');
            $query->whereIn('member_id', $memberIds);
        }

        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data Attendance oleh Parent']);
    }

    // Helper untuk menghitung KU berdasarkan tahun lahir
    private function calculateAgeGroup($dateOfBirth)
    {
        if (! $dateOfBirth) {
            return null;
        }

        $birthYear = Carbon::parse($dateOfBirth)->year;
        $currentYear = Carbon::now()->year;

        return $currentYear - $birthYear;
    }

    // Validasi apakah member termasuk dalam jadwal latihan
    private function validateMemberInSchedule($memberId, $trainingScheduleId)
    {
        $schedule = TrainingSchedule::with('members')->find($trainingScheduleId);

        if (! $schedule) {
            return false;
        }

        // Cek langsung dari relasi members
        return $schedule->members->contains('id', $memberId);
    }

    // Validasi KU
    private function validateMemberKU($memberId, $trainingScheduleId)
    {
        $member = Member::find($memberId);
        if (! $member || ! $member->date_of_birth) {
            return false;
        }

        $memberKU = $this->calculateAgeGroup($member->date_of_birth);
        $scheduleKUs = $this->getScheduleKUs($trainingScheduleId);

        return in_array($memberKU, $scheduleKUs);
    }

    private function getScheduleKUs($trainingScheduleId)
    {
        $schedule = TrainingSchedule::with('members')->find($trainingScheduleId);
        if (! $schedule || ! $schedule->members) {
            return [];
        }

        $kuSet = [];
        foreach ($schedule->members as $member) {
            if ($member->date_of_birth) {
                $ku = $this->calculateAgeGroup($member->date_of_birth);
                if ($ku) {
                    $kuSet[] = $ku;
                }
            }
        }

        return array_unique($kuSet);
    }

    // Absen manual dengan validasi lengkap
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|uuid',
            'training_schedule_id' => 'required|uuid',
            'status' => 'required|in:present,absent',
            'reason' => 'nullable|string|required_if:status,absent',
        ]);

        $member = Member::find($request->member_id);
        if (! $member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        if ($member->status !== 'active') {
            return response()->json(['message' => 'Member tidak aktif. Tidak dapat melakukan absensi.'], 403);
        }

        // Validasi 1: Cek apakah member termasuk dalam jadwal
        if (! $this->validateMemberInSchedule($request->member_id, $request->training_schedule_id)) {
            return response()->json([
                'message' => 'Member tidak terdaftar dalam jadwal latihan ini',
            ], 422);
        }

        // Validasi 2: Cek kesesuaian KU
        if (! $this->validateMemberKU($request->member_id, $request->training_schedule_id)) {
            $memberKU = $this->calculateAgeGroup($member->date_of_birth);
            $scheduleKUs = $this->getScheduleKUs($request->training_schedule_id);
            $scheduleKUsString = implode(', ', $scheduleKUs);

            return response()->json([
                'message' => "Member tidak sesuai dengan KU jadwal! KU {$memberKU} tidak termasuk dalam KU jadwal ini ({$scheduleKUsString})",
            ], 422);
        }

        // Cek apakah sudah ada absensi untuk hari ini
        $existingAttendance = Attendance::where('member_id', $request->member_id)
            ->where('training_schedule_id', $request->training_schedule_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Member sudah melakukan absensi untuk jadwal ini hari ini',
                'data' => $existingAttendance,
            ], 409);
        }

        $attendance = Attendance::create([
            'id' => Str::uuid(),
            'member_id' => $request->member_id,
            'coach_id' => Auth::id() ?? Str::uuid(),
            'training_schedule_id' => $request->training_schedule_id,
            'date' => Carbon::today()->toDateString(),
            'time' => Carbon::now()->format('H:i:s'),
            'method' => 'manual',
            'status' => $request->status,
            'reason' => $request->status === 'absent' ? $request->reason : null,
        ]);

        return response()->json([
            'message' => 'Absensi berhasil disimpan',
            'data' => $attendance,
        ]);
    }

    // Absen via QR dengan validasi lengkap
    public function scanQR(Request $request)
    {
        $request->validate([
            'encrypted_member_id' => 'required|string',
            'training_schedule_id' => 'required|uuid',
        ]);

        try {
            $decoded = base64_decode($request->encrypted_member_id);
            if (! $decoded) {
                return response()->json(['message' => 'QR Code tidak valid'], 400);
            }

            $member_id = $decoded;
            $member = Member::find($member_id);

            if (! $member) {
                return response()->json(['message' => 'Member tidak ditemukan'], 404);
            }

            if ($member->status !== 'active') {
                return response()->json(['message' => 'Member tidak aktif. Tidak dapat melakukan absensi.'], 403);
            }

            $trainingSchedule = TrainingSchedule::find($request->training_schedule_id);
            if (! $trainingSchedule) {
                return response()->json(['message' => 'Jadwal latihan tidak ditemukan'], 404);
            }

            // Validasi 1: Cek apakah member termasuk dalam jadwal
            if (! $this->validateMemberInSchedule($member_id, $request->training_schedule_id)) {
                return response()->json([
                    'message' => 'Member tidak terdaftar dalam jadwal latihan ini',
                ], 422);
            }

            // Validasi 2: Cek kesesuaian KU
            if (! $this->validateMemberKU($member_id, $request->training_schedule_id)) {
                $memberKU = $this->calculateAgeGroup($member->date_of_birth);
                $scheduleKUs = $this->getScheduleKUs($request->training_schedule_id);
                $scheduleKUsString = implode(', ', $scheduleKUs);

                return response()->json([
                    'message' => "Member tidak sesuai dengan KU jadwal! KU {$memberKU} tidak termasuk dalam KU jadwal ini ({$scheduleKUsString})",
                ], 422);
            }

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

            $attendance = Attendance::create([
                'id' => Str::uuid(),
                'member_id' => $member_id,
                'coach_id' => Auth::id() ?? Str::uuid(),
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

    // Update absensi dengan validasi
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $member = Member::find($attendance->member_id);

        if ($member && $member->status !== 'active') {
            return response()->json(['message' => 'Member tidak aktif. Tidak dapat mengubah absensi.'], 403);
        }

        // Jika training_schedule_id diubah, validasi ulang
        if ($request->has('training_schedule_id') && $request->training_schedule_id !== $attendance->training_schedule_id) {
            // Validasi 1: Cek apakah member termasuk dalam jadwal baru
            if (! $this->validateMemberInSchedule($attendance->member_id, $request->training_schedule_id)) {
                return response()->json([
                    'message' => 'Member tidak terdaftar dalam jadwal latihan ini',
                ], 422);
            }

            // Validasi 2: Cek kesesuaian KU dengan jadwal baru
            if (! $this->validateMemberKU($attendance->member_id, $request->training_schedule_id)) {
                $memberKU = $this->calculateAgeGroup($member->date_of_birth);
                $scheduleKUs = $this->getScheduleKUs($request->training_schedule_id);
                $scheduleKUsString = implode(', ', $scheduleKUs);

                return response()->json([
                    'message' => "Member tidak sesuai dengan KU jadwal! KU {$memberKU} tidak termasuk dalam KU jadwal ini ({$scheduleKUsString})",
                ], 422);
            }
        }

        $attendance->update($request->only('status', 'reason', 'training_schedule_id'));

        return response()->json([
            'message' => 'Status absensi diperbarui',
            'data' => $attendance,
        ]);
    }

    // Hapus absensi
    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json(['message' => 'Data absensi dihapus']);
    }
}
