<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\LotInventory;
use App\Models\RealEstate\Reservation;

class LotInventoryList extends Controller
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
        // return $request->all();
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $inventory = LotInventory::orderBy('subdivision', 'asc')
            ->orderBy('block', 'asc')
            ->get();

        return $inventory;
    }

    public function index(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $pagination = $request->pagination;
        $current_page = $pagination['current'];
        $pageSize = $pagination['pageSize'];
        $filters = isset( $request->filters ) ? $request->filters : false;
        $order = isset( $request->order ) ? $request->order : false;
        $order_field = isset( $request->field ) ? $request->field : false;
        $search = isset($request->search) ? $request->search : false;
        $custom_filters = isset($request->custom_filters) ? $request->custom_filters : false;

        $inventory = LotInventory::where('property_type', $request->type)
            ->orderBy('subdivision', 'asc')
            ->orderBy('block', 'asc');

        if( $filters ) {
            foreach( $filters as $field => $filter ) {
                if( !empty($filter) ) {
                    $inventory->whereIn($field, $filter);
                }
            }
        }

        if( $custom_filters ) {
            foreach( $custom_filters as $field => $value ) {
                if( !is_null($value) ) {
                    $inventory->where(str_replace('_search', '', $field), $value);
                }
            }
        }

        $paginate = $inventory->paginate($pageSize);

        if( count($paginate->items()) > 0 ) {
            foreach( $paginate->items() as $key => $item ) {
                $subdivision = $item['subdivision'];
                $block = $item['block'];
                $lot = $item['lot'];
                $type = $item['type'];
                $area = $item['area'];
                $has_reservation_agreement = Reservation::select('status')
                    ->where('subdivision', $subdivision)
                    ->where('subdivision', $subdivision)
                    ->where('block', $block)
                    ->where('lot', $lot)
                    ->where('type', $type)
                    ->whereNotIn('status', ['cancelled', 'void', 'draft'])
                    ->exists();
                $paginate->items()[$key]['has_reservation_agreement'] = $has_reservation_agreement;
            }
        }
        
        return $paginate;
    }

    public function subdivision_list(Request $request)
    {
        $type = $request->type;

        // select('subdivision', 'subdivision_name', 'phase', 'block', 'lot', 'area', 'type', 'price_per_sqm', 'status')

        $subdivisions = LotInventory::select('subdivision', 'subdivision_name')
            ->where('property_type', $request->type)
            ->orderBy('subdivision', 'asc')
            ->orderBy('block', 'asc')
            ->groupBy('subdivision')
            ->get();
            
        return $subdivisions;
    }

    public function dashboard_counts(Request $request)
    {
        $available = LotInventory::select('status')
            ->where('property_type', $request->type)
            ->where('status', 'available')
            ->count();

        $reserved = LotInventory::select('status')
            ->where('property_type', $request->type)
            ->where('status', 'reserved')
            ->count();

        $sold = LotInventory::select('status')
            ->where('property_type', $request->type)
            ->where('status', 'sold')
            ->count();

        $pending_migration = LotInventory::select('status')
            ->where('property_type', $request->type)
            ->where('status', 'pending_migration')
            ->count();

        $not_saleable = LotInventory::select('status')
            ->where('property_type', $request->type)
            ->where('status', 'not_saleable')
            ->count();

        return [
            'available' => $available,
            'reserved' => $reserved,
            'sold' => $sold,
            'pending_migration' => $pending_migration,
            'not_saleable' => $not_saleable,
        ];
    }

    public function custom_filter(Request $request)
    {
        $type = $request->type;
        $subdivision = $request->subdivision_search;

        $inventory = LotInventory::where('property_type', $request->type)
            ->where('subdivision', $subdivision)
            ->orderBy('subdivision', 'asc')
            ->orderBy('block', 'asc')
            ->get();

        return $inventory;
    }

    public function listing(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $inventory = LotInventory::where('property_type', $request->type)
            ->orderBy('subdivision', 'asc')
            ->orderBy('block', 'asc')
            ->get();

        return $inventory;
    }
}
