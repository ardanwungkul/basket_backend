<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberBill;
use App\Models\MemberFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required',
            'birth_certificate' => 'required',
            'family_card' => 'required',
        ]);

        $auth = Auth::user();
        $parent = $auth->parent;

        $member = Member::find($request->member_id);
        if ($member->parent_id == $parent->id) {
            $file = new MemberFile();

            $file_path = public_path('storage/images/files/' . $request->member_id . '/');
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photo_name = time() . '-photo-' . $request->member_id . '.' . $photo->getClientOriginalExtension();
                $photo->move($file_path, $photo_name);
                $file->photo = $photo_name;
            }
            if ($request->hasFile('birth_certificate')) {
                $birth_certificate = $request->file('birth_certificate');
                $birth_certificate_name = time() . '-birth-certificate-' . $request->member_id . '.' . $birth_certificate->getClientOriginalExtension();
                $birth_certificate->move($file_path, $birth_certificate_name);
                $file->birth_certificate = $birth_certificate_name;
            }
            if ($request->hasFile('family_card')) {
                $family_card = $request->file('family_card');
                $family_card_name = time() . '-family-card-' . $request->member_id . '.' . $family_card->getClientOriginalExtension();
                $family_card->move($file_path, $family_card_name);
                $file->family_card = $family_card_name;
            }
            if ($request->hasFile('club_release_letter')) {
                $club_release_letter = $request->file('club_release_letter');
                $club_release_letter_name = time() . '-club-release-letter-' . $request->member_id . '.' . $club_release_letter->getClientOriginalExtension();
                $club_release_letter->move($file_path, $club_release_letter_name);
                $file->club_release_letter = $club_release_letter_name;
            }
            if ($request->hasFile('bpjs')) {
                $bpjs = $request->file('bpjs');
                $bpjs_name = time() . '-bpjs-' . $request->member_id . '.' . $bpjs->getClientOriginalExtension();
                $bpjs->move($file_path, $bpjs_name);
                $file->bpjs = $bpjs_name;
            }
            $file->member_id = $request->member_id;
            $file->save();

            $member_bill = new MemberBill();
            $member_bill->bill_type = 'registration';
            $member_bill->member_id = $member->id;
            $member_bill->amount = 400000;
            $member_bill->due_date = Carbon::now()->addMonth();
            $member_bill->status = 'UNPAID';
            $member_bill->save();

            $monthly_member_bill = new MemberBill();
            $monthly_member_bill->bill_type = 'monthly';
            $monthly_member_bill->period_from = Carbon::now();
            $monthly_member_bill->period_to = Carbon::now()->addMonth();
            $monthly_member_bill->member_id = $member->id;
            $monthly_member_bill->amount = $member->monthly_fee;
            $monthly_member_bill->due_date = Carbon::now()->addMonth();
            $monthly_member_bill->status = 'UNPAID';
            $monthly_member_bill->save();
            return response()->json(['data' => $file, 'message' => 'Berhasil Mengirim Dokumen']);
        } else {
            return response()->json(['message' => 'Error']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MemberFile $memberFile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MemberFile $memberFile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MemberFile $memberFile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MemberFile $memberFile)
    {
        //
    }
}
