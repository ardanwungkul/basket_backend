<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberBill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getByAuth(Request $request)
    {
        $auth = Auth::user();
        if ($auth->parent) {
            $query = $auth->parent->member();
        } else {
            $query = Member::query();
        }
        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }
    public function show(Request $request, $member)
    {
        $query = Member::query()->where('id', $member);
        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->first();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
    }
    public function index(Request $request)
    {
        $query = Member::query();

        if ($request->with) {
            $withRelations = $request->query('with', '');
            $relations = $withRelations ? explode(',', $withRelations) : [];
            $query->with($relations);
        }

        $data = $query->get();

        return response()->json(['data' => $data, 'message' => 'Berhasil Mendapatkan Data']);
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
            'name' => 'required',
            'gender' => 'required',
            'place_of_birth' => 'required',
            'date_of_birth' => 'required',
            'school' => 'required',
            'school_grade' => 'required',
            'parent_name' => 'required',
            'parent_phone_number' => 'required',
            'parent_email' => 'required',
            'parent_address' => 'required',
        ]);

        $auth = Auth::user();
        $parent = $auth->parent;


        $member = new Member();
        $member->name = $request->name;
        $member->gender = $request->gender;
        $member->place_of_birth = $request->place_of_birth;
        $member->date_of_birth = $request->date_of_birth;
        $member->school = $request->school;
        $member->school_grade = $request->school_grade;
        $member->disease = $request->disease;
        $member->is_former_club = $request->is_former_club ? $request->is_former_club : false;
        $member->former_club = $request->former_club;
        $member->former_club_year = $request->former_club_year;

        if (!$parent->name || !$parent->phone_number || !$parent->email || !$parent->address) {
            $parent->name = $request->parent_name;
            $parent->phone_number = $request->parent_phone_number;
            $parent->email = $request->parent_email;
            $parent->address = $request->parent_address;
            $parent->save();
        }

        $member->parent_name = $request->parent_name;
        $member->parent_phone_number = $request->parent_phone_number;
        $member->parent_email = $request->parent_email;
        $member->parent_address = $request->parent_address;
        $member->parent_id = $parent ? $parent->id : $request->parent_id;

        if ($parent && $parent->member->count() > 0) {
            if ($parent->member->count() == 1) {
                foreach ($parent->member as $members) {
                    $members->monthly_fee = 250000;
                    $members->save();
                }
                $member->monthly_fee = 250000;
            } else if ($parent->member->count() >= 2) {
                foreach ($parent->member as $members) {
                    $members->monthly_fee = 200000;
                    $members->save();
                }
                $member->monthly_fee = 200000;
            }
        }
        $member->save();

        return response()->json(['data' => $member, 'message' => 'Berhasil Menambahkan Member']);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member)
    {
        $request->validate([
            'name' => 'required',
            'gender' => 'required',
            'place_of_birth' => 'required',
            'date_of_birth' => 'required',
            'school' => 'required',
            'school_grade' => 'required',
            'parent_name' => 'required',
            'parent_phone_number' => 'required',
            'parent_email' => 'required',
            'parent_address' => 'required',
        ]);

        $auth = Auth::user();
        $parent = $auth->parent;
        if ($parent->id == $member->parent_id);

        $member->name = $request->name;
        $member->gender = $request->gender;
        $member->place_of_birth = $request->place_of_birth;
        $member->date_of_birth = $request->date_of_birth;
        $member->school = $request->school;
        $member->school_grade = $request->school_grade;
        $member->disease = $request->disease;
        $member->is_former_club = $request->is_former_club ? $request->is_former_club : false;
        $member->former_club = $request->former_club;
        $member->former_club_year = $request->former_club_year;
        $member->parent_name = $request->parent_name;
        $member->parent_phone_number = $request->parent_phone_number;
        $member->parent_email = $request->parent_email;
        $member->parent_address = $request->parent_address;
        $member->save();

        return response()->json(['data' => $member, 'message' => 'Berhasil Mengubah Data Member']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(['data' => $member, 'message' => 'Berhasil Menghapus Data']);
    }
}
