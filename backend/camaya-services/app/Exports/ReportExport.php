<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportExport implements FromView, ShouldAutoSize
{
    public function __construct($template, $data)
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function view(): View
    {
        return view($this->template, [
            'reports' => $this->data,
        ]);
    }
}