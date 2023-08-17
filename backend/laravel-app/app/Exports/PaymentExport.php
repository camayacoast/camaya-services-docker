<?php

namespace App\Exports;

use App\PaymentTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;

class PaymentExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PaymentTransaction::take(2)->get();
    }
}
