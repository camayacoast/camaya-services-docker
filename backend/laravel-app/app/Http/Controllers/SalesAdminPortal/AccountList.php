<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RealEstate\Reservation;

class AccountList extends Controller
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
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Account')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }


        return \App\Models\RealEstate\Reservation::where('old_reservation', '!=', 1)->with('client.information')
                                        ->with('co_buyers.details')
                                        ->where('status', 'approved')
                                        ->where('client_number', '!=', '')
                                        ->get();
    }

    public function index(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Account')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $pagination = $request->pagination;
        $current_page = $pagination['current'];
        $pageSize = $pagination['pageSize'];
        $filters = isset( $request->filters ) ? $request->filters : ['status' => ['approved']];
        $order = isset( $request->order ) ? $request->order : false;
        $order_field = isset( $request->field ) ? $request->field : false;
        $search = isset($request->search) ? $request->search : false;

        $query = Reservation::select(
                '*', 
                'reservations.id as reservation_id',
                'reservations.created_at as reservation_date_created'
            )
            ->where('old_reservation', '!=', 1)
            ->join('users', 'reservations.client_id', '=', 'users.id')
            ->where('reservations.client_number', '!=', '')
            ->whereIn('reservations.status', ['approved'])
            ->with('client.information')
            ->with('co_buyers.details');

        if( $filters ) {
            foreach( $filters as $field => $filter ) {
                if( !is_null($filter) && !empty($filter) ) {
                    $query->whereIn('reservations.'.$field, $filter);
                }
            }
        }

        if( $order ) {
            $order = ($order == 'ascend') ? 'ASC' : 'DESC';
            if( $order_field == 'reservation_date_created' ) {
                $order_field = 'created_at';
            }
            $query->orderBy('reservations.'.$order_field, $order);
        }

        if( $search ) {
            $query->where(function($q) use ($search){
                $q->where('reservations.reservation_number', 'like', '%'. $search);
                $q->orWhere('reservations.client_number', 'like', '%'. $search);
                $q->orWhere(\DB::raw('concat( reservations.subdivision, " - Block ", reservations.block, " - Lot ", reservations.lot)'), 'like', '%'. $search.'%');
                $q->orWhere(\DB::raw('concat( users.first_name, " ", users.last_name)'), 'like', '%'. $search.'%');
            });
        }

        // Paginate
        return $query->paginate($pageSize);
    }
}
