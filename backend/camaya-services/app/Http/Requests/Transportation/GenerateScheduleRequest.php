<?php

namespace App\Http\Requests\Transportation;

use Illuminate\Foundation\Http\FormRequest;

class GenerateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->user_type == 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            // 'seat_allocations.*' => 'distinct|date',
            'transportation_id' => 'required|exists:camaya_booking_db.transportations,id',
            'route_id' => 'required|exists:camaya_booking_db.routes,id',
            'date_range' => 'required|array',

            'seat_allocations' => 'required',
            'seat_allocations.*.name' => 'required|distinct',
            'seat_allocations.*.quantity' => 'required|integer',

            'seat_allocations.*.seat_segments.*.name' => 'required|distinct',
            'seat_allocations.*.seat_segments.*.allocated' => 'required|integer',
            'seat_allocations.*.seat_segments.*.booking_type' => 'required',
            'seat_allocations.*.seat_segments.*.status' => 'required',
        ];
    }
}
