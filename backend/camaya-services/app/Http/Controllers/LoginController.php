<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\ClientProfile;
use App\Verification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function authenticate(Request $request)
    {

        // return $request->all();
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            // 'device_name' => 'required'
        ]);
    
        $user = User::where('email', $request->email)->first();

        // Check if credentials are correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => trans('auth.failed')], 400);
        }

        // Check if user email has been verified
        if (!$user->email_verified_at) {
            return response()->json(['error'=> trans('auth.unverified')], 400);
        }

        // Check if can login using login_type
        if (!isset($request->portal)) {
            if ($user->user_type != $request->login_type) {
                return response()->json(['error'=> trans('auth.access_not_allowed')], 400);
            }
        }

        if (!$request->login_type) {
            // Kiosk login
            return response()->json(['error'=> 'login type required.'], 400);
        }

        /**
         * Portal checking
         */

        if ($request->portal == 'kiosk' && !in_array($user->user_type, ['admin', 'kiosk'])) {
            // Kiosk login
            return response()->json(['error'=> trans('auth.access_not_allowed')], 400);
        }

        if ($request->portal == 'website') {
            if (!in_array($user->user_type, ['customer', 'client', 'agent', 'employee'])) {
                return response()->json(['error'=> trans('auth.access_not_allowed')], 400);
            }
        }

        if ($request->portal == 'agent_portal') {
            if (!in_array($user->user_type, ['agent'])) {
                return response()->json(['error'=> trans('auth.access_not_allowed')], 400);
            }
        }
        
        /////

        if ($user->user_type != $request->login_type && $request->portal != 'website') {
            return response()->json(['error'=> trans('auth.access_not_allowed')], 400);
        }

        // Check if the user is already logged
        // if ($user && count($user->tokens)) {
        //     // $user->tokens()->delete();
        // }

        switch ($user->user_type) {
            case 'admin':
                break;
            case 'client':
                $user
                    ->load('clientProfile')
                    ->load('clientProperties')
                    ->load('clientComembers');
                break;
            case 'customer':
                $user->load('customerProfile');
                break;
            case 'agent':
                $user->load(['parent_team' => function ($q) {
                    $q->select('id','team_id','user_id','role');
                    $q->where('role', 'member');
                    $q->whereNull('deleted_at');
                    $q->whereHas('team', function ($q) {
                        $q->whereNull('parent_id');
                    });
                    $q->with(['team' => function ($q) {
                        $q->select('id', 'owner_id', 'name');
                        $q->with('teamowner:id,first_name,last_name');
                    }]);
                }]);
                break;
            case 'employee':
                break;
        }

        // $user_data = [
        //     'email' => $user->email,
        //     'first_name' => $user->first_name,
        //     'middle_name' => $user->middle_name,
        //     'last_name' => $user->last_name,
        //     'object_id' => $user->object_id,
        //     'user_type' => $user->user_type,
        //     'created_at' => $user->created_at,
        // ];

        $permissions = [];
        
        foreach ( $user->getAllPermissions()->pluck('name') as $permission ) {

            $permission = explode('.', $permission);

            $permissions[] = [
                'subject' => $permission[0],
                'action' => $permission[1].".".$permission[2],
            ];
        }
        
        return [
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $permissions,
        ];

    }

    public function getAuthenticatedUser(Request $request)
    {
        $user = $request->user();

        switch ($user->user_type) {
            case 'admin':
                break;
            case 'client':
                $user
                    ->load('clientProfile')
                    ->load('clientProperties')
                    ->load('clientComembers');
                
                if ($request->portal == 'golf') {
                    $user['golf_membership_status'] = $user->clientProfile->checkGolfMemberStatus();
                }
                break;
            case 'customer':
                $user->load('customerProfile');
                break;
            case 'agent':
                $user->load(['parent_team' => function ($q) {
                    $q->select('id','team_id','user_id','role');
                    $q->where('role', 'member');
                    $q->whereNull('deleted_at');
                    $q->whereHas('team', function ($q) {
                        $q->whereNull('parent_id');
                    });
                    $q->with(['team' => function ($q) {
                        $q->select('id', 'owner_id', 'name');
                        $q->with('teamowner:id,first_name,last_name');
                    }]);
                }]);
                break;
            case 'employee':
                break;
        }

        // $user_data = [
        //     'email' => $user->email,
        //     'first_name' => $user->first_name,
        //     'middle_name' => $user->middle_name,
        //     'last_name' => $user->last_name,
        //     'object_id' => $user->object_id,
        //     'user_type' => $user->user_type,
        //     'created_at' => $user->created_at,
        // ];

        $permissions = [];
        
        foreach ( $user->getAllPermissions()->pluck('name') as $permission ) {

            $permission = explode('.', $permission);

            $permissions[] = [
                'subject' => $permission[0],
                'action' => $permission[1].".".$permission[2],
            ];
        }
        
        $reponse = [
            // 'token' => $user->createToken('web')->plainTextToken,
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $permissions,
        ];

        return response()->json($reponse, 200);
    }

    public function activateAccount(Request $request)
    {
        // return $request->all();
        if (!$request->email || !$request->code) {
            return redirect('/');
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return "User not found.";
        }

        $id = $user->id;

        if ($user->email_verified_at) {
            return "Account already activated.";
        }

        if ($user->verification && $user->verification->token == $request->code) {

            // Update user verified
            $user->where('id', $id)->update([
                'email_verified_at' => date('Y-m-d H:m:s',time())
            ]);

            // Delete
            Verification::where('token', $user->verification->token)->delete();
        }

        $url = $request->golf && $request->golf == 1 ? env('CAMAYA_GOLF_PORTAL') : env('CAMAYA_PAYMENT_PORTAL');
        return redirect()->away($url."/?verified=true");
    }

    public function getUserByToken(Request $request) {
        return $request->user();
    }
}
