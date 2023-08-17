<?php

namespace App\Http\Controllers\RealEstate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\RealEstate\RealestatePromo;
use Carbon\Carbon;

use Illuminate\Support\Facades\Http;

class Promos extends Controller
{
    // Restfull controllers

    // GET
    public function index()
    {
        $promos = RealestatePromo::orderBy('created_at', 'DESC')->get();
        return $promos;
    }

    // GET
    public function show(Request $request)
    {
        $promos = RealestatePromo::where($request->column, $request->value)
            ->orderBy('name', 'ASC')->get();
        return $promos;
    }

    // Add promos POST
    public function store(Request $request)
    {
        $name = $request->name;
        $promo_type = $request->promo_type;
        $description = $request->description;
        $status = $request->status;

        try {

            $promo = new RealestatePromo;

            if( $promo->where('promo_type', $promo_type)->exists() ) {
                return response(['message' => 'Promo Code is already exists.'], 201);
            }

            $add = $promo->create([
                'name' => $name,
                'promo_type' => $promo_type,
                'description' => $description,
                'status' => $status,
            ]);

            if( $add ) {
                return response(['message' => 'Promo is successfully added.'], 200);
            } else {
                return response(['message' => 'Error adding promo.'], 500);
            }
            
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    // update specific promo details PUT/PATCH
    public function update(Request $request)
    {
        $id = $request->id;
        $name = $request->name;
        $promo_type = $request->promo_type;
        $description = $request->description;
        $status = $request->status;
    
        try {
            $update = RealestatePromo::where('id', $id)
                ->update([
                    'name' => $name,
                    'promo_type' => $promo_type,
                    'description' => $description,
                    'status' => $status,
                ]);

            if( $update ) {
                return response(['message' => 'Promo is successfully updated.'], 200);
            } else {
                return response(['message' => 'Error updating promo.'], 500);
            }
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // delete promo DELETE
    public function destroy(Request $request)
    {
        $id = $request->promo_id;

        try {
            $delete = RealestatePromo::where('id', $id)->delete();

            if( $delete ) {
                return response(['message' => 'Promo is successfully deleted.'], 200);
            } else {
                return response(['message' => 'Error deleting promo.'], 500);
            }

        } catch (Exception $e) {
            return $e->getMessage();
        }
        
    }

}
