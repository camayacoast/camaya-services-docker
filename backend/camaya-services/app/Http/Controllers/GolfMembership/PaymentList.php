<?php

namespace App\Http\Controllers\GolfMembership;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\PaymentTransaction;

class PaymentList extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        return PaymentTransaction::with('payer')
                ->whereIn('item', [
                    // 'hoa_membership_fee',
                    // 'hoa_monthly_dues',
                    // 'hoa_monthly_dues_promo',
                    // 'hoa_monthly_dues_6months_promo',
                    // 'fmf_privilege_activation_fee',
                    // 'fmf_monthly_dues',
                    // 'fmf_monthly_dues_promo',
                    'golf_membership_fee',
                    'golf_monthly_dues',
                    'golf_membership_fee_promo',
                    'golf_monthly_dues_promo',
                    'non_hoa_3yr_golf_membership_fee',
                    'non_hoa_3yr_golf_membership_fee_promo',
                    'non_hoa_golf_monthly_dues',
                    'non_hoa_3yr_golf_dues_promo',
                    'non_hoa_golf_annual_dues_promo',
                    'non_hoa_golf_membership_fee_2021',
                    'non_hoa_advance_golf_dues_12_months'])
                ->get();
    }
}
