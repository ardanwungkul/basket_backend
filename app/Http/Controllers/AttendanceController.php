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

    private function calculateAgeGroup($dateOfBirth)
    {
        if (!$dateOfBirth) return null;

        $birthYear = Carbon::parse($dateOfBirth)->year;
        $currentYear = Carbon::now()->year;
        return $currentYear - $birthYear;
    }

    private function getScheduleKUs($trainingScheduleId)
    {
        $schedule = TrainingSchedule::with('members')->find($trainingScheduleId);
        if (!$schedule || !$schedule->members) return [];

        $kuSet = [];
        foreach ($schedule->members as $member) {
            if ($member->date_of_birth) {
                $ku = $this->calculateAgeGroup($member->date_of_birth);
                if ($ku) $kuSet[] = $ku;
            }
        }

        return array_unique($kuSet);
    }

    // validasi member cocok tidak dengan KU
    private function validateMemberKU($memberId, $trainingScheduleId)
    {
        $member = Member::find($memberId);
        if (!$member || !$member->date_of_birth) {
            return false;
        }

        $memberKU = $this->calculateAgeGroup($member->date_of_birth);
        $scheduleKUs = $this->getScheduleKUs($trainingScheduleId);

        return in_array($memberKU, $scheduleKUs);
    }

    // absen manual
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|uuid',
            'training_schedule_id' => 'required|uuid',
            'status' => 'required|in:present,absent',
            'reason' => 'nullable|string',
        ]);

        $member = Member::find($request->member_id);
        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        if ($member->status !== 'active') {
            return response()->json(['message' => 'Member tidak aktif. Tidak dapat melakukan absensi.'], 403);
        }

        if (!$this->validateMemberKU($request->member_id, $request->training_schedule_id)) {
            $memberKU = $this->calculateAgeGroup($member->date_of_birth);
            $scheduleKUs = $this->getScheduleKUs($request->training_schedule_id);
            $scheduleKUsString = implode(', ', $scheduleKUs);

            return response()->json([
                'message' => "Member tidak sesuai dengan jadwal! KU {$memberKU} tidak termasuk dalam KU jadwal ini ({$scheduleKUsString})"
            ], 422);
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
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Absensi berhasil disimpan',
            'data' => $attendance,
        ]);
    }

    // absen via qr
    public function scanQR(Request $request)
    {
        $request->validate([
            'encrypted_member_id' => 'required|string',
            'training_schedule_id' => 'required|uuid',
        ]);

        try {
            $decoded = base64_decode($request->encrypted_member_id);
            if (!$decoded) {
                return response()->json(['message' => 'QR Code tidak valid'], 400);
            }

            $member_id = $decoded;
            $member = Member::find($member_id);

            if (!$member) {
                return response()->json(['message' => 'Member tidak ditemukan'], 404);
            }

            if ($member->status !== 'active') {
                return response()->json(['message' => 'Member tidak aktif. Tidak dapat melakukan absensi.'], 403);
            }

            $trainingSchedule = TrainingSchedule::find($request->training_schedule_id);
            if (!$trainingSchedule) {
                return response()->json(['message' => 'Jadwal latihan tidak ditemukan'], 404);
            }

            if (!$this->validateMemberKU($member_id, $request->training_schedule_id)) {
                $memberKU = $this->calculateAgeGroup($member->date_of_birth);
                $scheduleKUs = $this->getScheduleKUs($request->training_schedule_id);
                $scheduleKUsString = implode(', ', $scheduleKUs);

                return response()->json([
                    'message' => "Member tidak sesuai dengan jadwal! KU {$memberKU} tidak termasuk dalam KU jadwal ini ({$scheduleKUsString})"
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

    // update absensi
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $member = Member::find($attendance->member_id);

        if ($member && $member->status !== 'active') {
            return response()->json(['message' => 'Member tidak aktif. Tidak dapat mengubah absensi.'], 403);
        }

        if ($request->has('training_schedule_id') && $request->training_schedule_id !== $attendance->training_schedule_id) {
            if (!$this->validateMemberKU($attendance->member_id, $request->training_schedule_id)) {
                $memberKU = $this->calculateAgeGroup($member->date_of_birth);
                $scheduleKUs = $this->getScheduleKUs($request->training_schedule_id);
                $scheduleKUsString = implode(', ', $scheduleKUs);

                return response()->json([
                    'message' => "Member tidak sesuai dengan jadwal! KU {$memberKU} tidak termasuk dalam KU jadwal ini ({$scheduleKUsString})"
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
