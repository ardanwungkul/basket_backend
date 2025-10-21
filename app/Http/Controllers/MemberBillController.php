<?php

namespace App\Http\Controllers;

use App\Models\MemberBill;
use App\Models\Payment;
use App\Models\PaymentDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MemberBillController extends Controller
{
    public function getByAuth(Request $request)
    {
        $auth = Auth::user();
        $query = MemberBill::whereHas('member', function ($query) use ($auth) {
            $query->where('parent_id', $auth->parent->id);
        });

        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->get();
        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = Auth::user();
        $parent = $auth->parent;

        $bills = [];
        foreach ($request->checkbox as $id) {
            $bills[] = MemberBill::find($id['id']);
        }
        $payment = new Payment();
        $payment->parent_id = $parent->id;
        $total = 0;
        foreach ($bills as $bill) {
            $total += $bill->amount;
        }
        $payment->total_amount = $total;
        $payment->payment_method = $request->payment_method;
        $payment->reference_code = Str::uuid();
        $payment->status = 'PENDING';
        $payment->save();

        foreach ($bills as $bill) {
            $payment_detail = new PaymentDetail();
            $payment_detail->payment_id = $payment->id;
            $payment_detail->bill_id = $bill->id;
            $payment_detail->amount = $bill->amount;
            $payment_detail->save();
        }
        return response()->json(['data' => $payment, 'Message' => 'Berhasil Membuat Pembayaran']);
    }

    /**
     * Display the specified resource.
     */
    public function show(MemberBill $memberBill)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MemberBill $memberBill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MemberBill $memberBill)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MemberBill $memberBill)
    {
        //
    }
}
