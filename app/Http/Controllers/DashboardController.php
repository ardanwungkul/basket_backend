<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberBill;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function admin()
    {
        $today = Carbon::today();
        $member = Member::count();
        $payment = Payment::where('status', 'PENDING')->count();
        $bill = MemberBill::where('status', 'UNPAID')
            ->where(function ($query) use ($today) {
                $query->where('bill_type', 'registration')
                    ->orWhere(function ($q) use ($today) {
                        $q->where('bill_type', 'monthly')
                            ->whereDate('period_from', '<=', $today)
                            ->whereDate('period_to', '>=', $today);
                    });
            })
            ->count();
        return response()->json([
            'data' => [
                'member' => $member,
                'payment' => $payment,
                'bill' => $bill
            ],
            'message' => 'Berhasil Mendapatkan Data'
        ]);
    }
    public function member()
    {
        $today = Carbon::today();
        $auth = Auth::user();
        $member = $auth->parent->member->count();
        $payment = Payment::where('parent_id', $auth->parent->id)->where('status', 'UNPAID')->count();
        $bill = MemberBill::whereHas('member', function ($query) use ($auth) {
            $query->where('parent_id', $auth->parent->id);
        })
            ->where('status', 'UNPAID')
            ->where(function ($query) use ($today) {
                $query
                    ->where('bill_type', 'registration')
                    ->orWhere(function ($q) use ($today) {
                        $q
                            ->where('bill_type', 'monthly')
                            ->whereDate('period_from', '<=', $today)
                            ->whereDate('period_to', '>=', $today);
                    });
            })
            ->count();


        return response()->json([
            'data' => [
                'member' => $member,
                'payment' => $payment,
                'bill' => $bill
            ],
            'message' => 'Berhasil Mendapatkan Data'
        ]);
    }
}
