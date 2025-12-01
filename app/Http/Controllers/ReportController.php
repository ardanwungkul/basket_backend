<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $data = DB::table('members')
            // ->selectRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age, COUNT(*) AS total')
            ->selectRaw('YEAR(CURDATE()) - YEAR(date_of_birth) AS age, COUNT(*) AS total')
            ->groupBy('age')
            ->orderBy('age')
            ->get();
        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }
    public function getMemberByAge($age, $year, $type)
    {
        $getYear = now()->year - $age;
        if ($type == 'attendance') {
            $members = Member::with(['sibling', 'attendance' => function ($q) use ($year) {
                $q->whereYear('date', $year);
            }])
                ->whereYear('date_of_birth', $getYear)
                ->get()
                ->each(function ($member) {
                    $member->setRelation(
                        'sibling',
                        $member->sibling->where('id', '!=', $member->id)->values()
                    );
                });
        } elseif ($type == 'payment') {
            $members = [];
        }
        return response()->json(['data' => $members, 'message' => 'Berhasil Mendapatkan Data']);
    }
}
