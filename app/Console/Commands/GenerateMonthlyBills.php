<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\MemberBill;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateMonthlyBills extends Command
{

    protected $signature = 'app:generate-monthly-bills';
    protected $description = 'Generate monthly membership bills for active members and deactivate them afterwards';


    public function handle()
    {
        $now = Carbon::now();
        $start = $now->startOfMonth()->format('Y-m-d');
        $end = $now->endOfMonth()->format('Y-m-d');

        $members = Member::whereHas('bill', function ($query) {
            $query->where('bill_type', 'registration')
                ->where('status', 'PAID');
        })->get();

        foreach ($members as $member) {

            $exists = MemberBill::where('member_id', $member->id)
                ->where('bill_type', 'monthly')
                ->whereMonth('period_from', Carbon::now()->month)
                ->whereYear('period_from', Carbon::now()->year)
                ->exists();

            if ($exists) {
                continue;
            }

            $member_bill = new MemberBill();
            $member_bill->member_id = $member->id;
            $member_bill->bill_type = 'monthly';
            $member_bill->period_from = $start;
            $member_bill->period_to = $end;
            $member_bill->amount = $member->monthly_fee;
            $member_bill->due_date = $end;
            $member_bill->status = 'UNPAID';
            $member_bill->save();

            $member->status = 'inactive';
            $member->save();
        }

        $this->info('Monthly bills generated and members set to inactive successfully.');
    }
}
