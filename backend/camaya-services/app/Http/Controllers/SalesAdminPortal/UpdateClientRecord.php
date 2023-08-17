<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\RealEstate\Client;
use App\Models\RealEstate\ClientInformation;
use App\Models\RealEstate\ClientInformationAttachment;
use App\Models\RealEstate\ClientSpouse;
use App\Models\RealEstate\SalesClient;
use DB;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class UpdateClientRecord extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.Edit.Client')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $checkIfUserExists = User::where('email', $request->email)
                                ->where('id', '!=', $request->client_id)
                                ->first();

        if ($checkIfUserExists) {
            return response()->json(['error' => 'EMAIL_EXISTS', 'message' => 'Email address already exists.'], 400);
        }

        $with_bis = 1;

        // return $request->all();
        $validator = Validator::make($request->all(), [

            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email:rfc,dns|max:255|exists:users',

            'gender' => $with_bis ? 'required' : '',
            'birth_date' => $with_bis ? 'required' : '',
            'birth_place' => $with_bis ? 'required' : '',
            'citizenship' => $with_bis ? 'required' : '',
            'monthly_household_income' => $with_bis ? 'required' : '',
            'government_issued_identification' => $with_bis ? 'required' : '',
            // 'id_issuance_place' => $with_bis ? 'required' : '',
            // 'id_issuance_date' => $with_bis ? 'required' : '',
            'tax_identification_number' => $with_bis ? 'required' : '',
            'occupation' => $with_bis ? 'required' : '',
            'company_name' => $with_bis ? 'required' : '',
            'company_address' => $with_bis ? 'required' : '',
            'spouse_first_name' => $request->with_spouse && $with_bis ? 'required' : '',
            // 'spouse_middle_name' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_last_name' => $request->with_spouse && $with_bis ? 'required' : '',
            // 'spouse_extension' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_gender' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_birth_date' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_birth_place' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_citizenship' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_government_issued_identification' => $request->with_spouse && $with_bis ? 'required' : '',
            // 'spouse_id_issuance_place' => $request->with_spouse && $with_bis ? 'required' : '',
            // 'spouse_id_issuance_date' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_tax_identification_number' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_occupation' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_company_name' => $request->with_spouse && $with_bis ? 'required' : '',
            'spouse_company_address' => $request->with_spouse && $with_bis ? 'required' : '',
            'home_phone' => $with_bis ? 'required' : '',
            'business_phone' => $with_bis ? 'required' : '',
            'email' => $with_bis ? 'required' : '',
            'mobile_number' => $with_bis ? 'required' : '',
            'permanent_home_address_house_number' => $with_bis ? 'required' : '',
            'permanent_home_address_street' => $with_bis ? 'required' : '',
            'permanent_home_address_baranggay' => $with_bis ? 'required' : '',
            'permanent_home_address_city' => $with_bis ? 'required' : '',
            'permanent_home_address_province' => $with_bis ? 'required' : '',
            'permanent_home_address_zip_code' => $with_bis ? 'required' : '',

            'current_home_address_house_number' => $with_bis && !$request->current_home_address_international ? 'required' : '',
            'current_home_address_street' => $with_bis ? 'required' : '',
            'current_home_address_baranggay' => $with_bis && !$request->current_home_address_international ? 'required' : '',
            'current_home_address_city' => $with_bis ? 'required' : '',
            'current_home_address_province' => $with_bis && !$request->current_home_address_international ? 'required' : '',
            'current_home_address_zip_code' => $with_bis ? 'required' : '',
            'preferred_mailing_address' => $with_bis ? 'required' : '',

        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();
            return response()->json(['message' => 'Validation failed', 'errors' => $errors], 400);
        }

        DB::beginTransaction();
        //
        // return $request->all();
        $updateClient = User::where('id', $request->client_id)
        ->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'user_type' => 'client',
            'email' => $request->email,
            'email_verified_at' => null,
            'password' => Str::random(10)
        ]);

        // Client Information
        $updateClientInfo = ClientInformation::where('user_id', $request->client_id)
            ->update([
            
            // 'client_number' => 'C'.Str::padLeft($newClient->id, 9, '0'),

            'extension' => $request->extension,
            'contact_number' => $request->contact_number,

            'gender' => $request->gender,
            'birth_date' => Carbon::parse($request->birth_date)->setTimezone('Asia/Manila'),
            'birth_place' => $request->birth_place,
            'citizenship' => $request->citizenship,
            'monthly_household_income' => $request->monthly_household_income,
            'government_issued_id' => $request->government_issued_identification,
            'government_issued_id_issuance_place' => $request->id_issuance_place,
            'government_issued_id_issuance_date' => ( !is_null($request->id_issuance_date) ) ? Carbon::parse($request->id_issuance_date)->setTimezone('Asia/Manila') : null,
            'tax_identification_number' => $request->tax_identification_number,
            'occupation' => $request->occupation,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            // Spouse
            'home_phone' => $request->home_phone,
            'business_phone' => $request->business_phone,
            // 'email' => $request->email,
            'mobile_number' => $request->mobile_number,

            // Permanent home
            'permanent_home_address_house_number' => $request->permanent_home_address_house_number,
            'permanent_home_address_street' => $request->permanent_home_address_street,
            'permanent_home_address_baranggay' => $request->permanent_home_address_baranggay,
            'permanent_home_address_city' => $request->permanent_home_address_city,
            'permanent_home_address_province' => $request->permanent_home_address_province,
            'permanent_home_address_zip_code' => $request->permanent_home_address_zip_code,

            // Current home
            'current_home_address_international' => $request->current_home_address_international,
            'current_home_address_country' => $request->current_home_address_international ? $request->current_home_address_country : 'Philippines',
            'current_home_address_house_number' => $request->current_home_address_international ? '' : $request->current_home_address_house_number,
            'current_home_address_street' => $request->current_home_address_street,
            'current_home_address_baranggay' => $request->current_home_address_international ? '' : $request->current_home_address_baranggay,
            'current_home_address_city' => $request->current_home_address_city,
            'current_home_address_province' => $request->current_home_address_international ? '' : $request->current_home_address_province,
            'current_home_address_zip_code' => $request->current_home_address_zip_code,
            'preferred_mailing_address' => $request->preferred_mailing_address,

            // 'status' => 'for_review',
        ]);

        /**
         * Create spouse record
         */
        if ($request->with_spouse) {
            ClientSpouse::updateOrCreate(
                    ['client_id' => $request->client_id],
                    [
                        'spouse_first_name' => $request->spouse_first_name,
                        'spouse_middle_name' => $request->spouse_middle_name,
                        'spouse_last_name' => $request->spouse_last_name,
                        'spouse_extension' => $request->spouse_extension,
                        'spouse_gender' => $request->spouse_gender,
                        'spouse_birth_date' => Carbon::parse($request->spouse_birth_date)->setTimezone('Asia/Manila'),
                        'spouse_birth_place' => $request->spouse_birth_place,
                        'spouse_citizenship' => $request->spouse_citizenship,
                        'spouse_government_issued_identification' => $request->spouse_government_issued_identification,
                        'spouse_id_issuance_place' => $request->spouse_id_issuance_place,
                        'spouse_id_issuance_date' => ( !is_null($request->spouse_id_issuance_date) ) ? Carbon::parse($request->spouse_id_issuance_date)->setTimezone('Asia/Manila') : null,
                        'spouse_tax_identification_number' => $request->spouse_tax_identification_number,
                        'spouse_occupation' => $request->spouse_occupation,
                        'spouse_company_name' => $request->spouse_company_name,
                        'spouse_company_address' => $request->spouse_company_address,
                    ]
                );
        }

        // Sales Client
        $salesClient = SalesClient::where('client_id', $request->client_id)
        ->update([
            'sales_id' => $request->assigned_agent,
        ]);

        if (!$updateClient || !$updateClientInfo) {
            DB::rollBack();
            return response()->json(['message' => 'Could not save client record.'], 400);
        }

        $client_record = Client::where('id', $request->client_id)
                        ->with(['information.attachments' => function ($q) {
                            $q->whereNull('deleted_at');
                        }])->with('spouse')->with('agent.agent_details')->first();

        DB::commit();

        return response()->json([
            'client' => $client_record,
        ], 200);
    }
}
