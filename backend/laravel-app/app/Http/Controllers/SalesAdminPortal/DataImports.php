<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\SalesAdminPortal\Inventory;

class DataImports extends Controller {

    public function import_lot_inventory(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.Import.Inventory')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        try {

            $validation = Validator::make($request->all(), ['import_inventory' => 'required|mimes:xlsx'], ['mimes' => 'Please select an excel (.xlxs) file.']);

            if( $validation->fails() ) {
                $messages = $validation->getMessageBag()->toArray();
                return response($messages['import_inventory'][0], 400);
            }

            $inventory = New Inventory;
            $inventory->property_type = 'lot';
            Excel::import($inventory, request()->file('import_inventory'));
            return $inventory->response;

        } catch (\Exeption $e)  {
            return $e->getMessage();
        }
       
    }

    public function import_condo_inventory(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.Import.Inventory')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        try {

            $validation = Validator::make($request->all(), ['import_inventory' => 'required|mimes:xlsx'], ['mimes' => 'Please select an excel (.xlxs) file.']);

            if( $validation->fails() ) {
                $messages = $validation->getMessageBag()->toArray();
                return response($messages['import_inventory'][0], 400);
            }

            $inventory = New Inventory;
            $inventory->property_type = 'condo';
            Excel::import($inventory, request()->file('import_inventory'));
            return $inventory->response;

        } catch (\Exeption $e)  {
            return $e->getMessage();
        }
       
    }

}