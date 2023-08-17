<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\ClientProfile;
use App\WorkInformation;
use App\ClientProperties;
use App\ClientComembers;
use App\Verification;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Mail\UserRegistered;
use App\Mail\GolfUserRegistered;
use Illuminate\Support\Facades\Mail;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class RegisterController extends Controller
{
    //

    public function register(RegisterRequest $request) {
        // return $request->all();
        DB::beginTransaction();

        /*
         * Create client account
         */
        // password
        // password_confirmation
        // email
        // first_name
        // middle_name
        // last_name

        $newUser = User::create([
            'object_id' => (string) Str::orderedUuid(),
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'middle_name' => $request['middle_name'],
            'user_type' => 'client',
        ]);

        /*
         * Create client profile
         */
        // prefix
        // contact_number
        //
        // birth_date
        // birth_place
        // nationality
        // residence_address
        // telephone_number
        // photo
        // valid_id

        $createClientProfile = new ClientProfile([
                'prefix' => $request['prefix'],
                'contact_number' => $request['contact_number'],
                'birth_date' => date('Y-m-d', strtotime($request['birth_date'])),
                'birth_place' => $request['birth_place'],
                'nationality' => $request['nationality'],
                'residence_address' => $request['residence_address'],
                'telephone_number' => $request['telephone_number'],
                'photo' => $request['photo']['file']['response'],
                'valid_id' => $request['valid_id']['file']['response'],
                'assisted_by' => $request['assisted_by'],
        ]);

        $newUser->clientProfile()->save($createClientProfile);

        /**
         * Create work information record
         */
        // company_name
        // business_telephone_number
        // business_address
        // industry

        if ($request['non_hoa'] == true) {

            $createWorkInformation = new WorkInformation([
                'company_name' => $request['company_name'],
                'business_telephone_number' => $request['business_telephone_number'],
                'business_address' => $request['business_address'],
                'industry' => $request['industry'],
            ]);

            $newUser->workInformation()->save($createWorkInformation);
            
        }

        /*
         * Create client property record
         */
        // area
        // block_number
        // client_number
        // lot_number
        // subdivision

        if ($request['non_hoa'] != true) {
            $createClientProperties = new ClientProperties([
                'area' => $request['area'],
                'block_number' => $request['block_number'],
                'client_number' => $request['client_number'],
                'lot_number' => $request['lot_number'],
                'subdivision' => $request['subdivision'],
            ]);

            $newUser->clientProperties()->save($createClientProperties);
        }

        /*
         * Create client co-member record
         */
        // comembers
        // relationship
        // first_name
        // last_name

        $createClientComembers = [];

        if (count((array)$request->comembers) > 0) {
            foreach ($request->comembers as $comember) {
                $createClientComembers[] = new ClientComembers([
                    'birthdate' => date('Y-m-d', strtotime($comember['birthdate'])),
                    'relationship' => $comember['relationship'],
                    'first_name' => $comember['first_name'],
                    'middle_name' => $comember['middle_name'],
                    'last_name' => $comember['last_name'],
                ]);
            }

            $newUser->clientComembers()->saveMany($createClientComembers);
        }

        $activate_code = str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);

        $newUser->verification()->save(new Verification([
            'token' => $activate_code,
            'expiry' => null,
        ]));


        if (!$newUser) {
            DB::rollBack();
            return response()->json(['error' => "Registration has failed."], 400);
        }

        DB::commit();

        
        /*
         * Send email confirmation to user
         */
        if ($request->golf) {
            Mail::to($request->email)->send(new GolfUserRegistered($newUser, env('APP_URL') ."/"."activate/".$newUser->email."/?code=".$activate_code."&golf=1"));
        } else {
            Mail::to($request->email)->send(new UserRegistered($newUser, env('APP_URL') ."/"."activate/".$newUser->email."/?code=".$activate_code));
        }

        return $newUser;
    }

    public function saveUpload(Request $request)
    {
        $path = Storage::putFile('public', $request->file('file'));

        return env('APP_URL').Storage::url($path);
    }
}
