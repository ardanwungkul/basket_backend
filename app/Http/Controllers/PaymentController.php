<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function getByAuth(Request $request)
    {
        $auth = Auth::user();
        $query = Payment::where('parent_id', $auth->parent->id);
        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }
    public function index(Request $request)
    {
        $query = Payment::query();
        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    public function confirmPayment(Request $request)
    {
        $payment = Payment::find($request->id);
        $payment->payment_date = Carbon::now();
        $payment->status = 'SUCCESS';
        $payment->save();

        foreach ($payment->details as $detail) {
            $bill = $detail->bill;
            $bill->status = 'PAID';
            $bill->save();


            $hasUnpaidRegistrationBill = $bill->member->bill()
                ->where('bill_type', 'registration')
                ->where('status', 'UNPAID')
                ->exists();

            $hasMonthlyBillThisMonth = $bill->member->bill()
                ->where('bill_type', 'monthly')
                ->whereMonth('period_from', now()->month)
                ->whereYear('period_from', now()->year)
                ->where('status', 'UNPAID')
                ->exists();

            if (!$hasUnpaidRegistrationBill && !$hasMonthlyBillThisMonth) {
                $member = $bill->member;
                $member->status = 'active';
                $member->save();
            }
        }

        return response()->json(['data' => $payment, 'message' => 'Berhasil Mengkonfirmasi Pembayaran']);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payment = Payment::find($request->id);
        $payment->payment_method = $request->payment_method;
        $file_path = public_path('storage/files/payment/' . $payment->parent_id . '/');
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $file_name = time() . '-file-' . $request->id . '.' . $file->getClientOriginalExtension();
            $file->move($file_path, $file_name);
            $payment->file = $file_name;
        }
        $payment->status = 'PENDING';
        $payment->save();
        return response()->json(['data' => $payment, 'message' => 'Berhasil Melakukan Pembayaran']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        $data = $payment->load(['details', 'details.bill', 'details.bill.member']);
        return response(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
