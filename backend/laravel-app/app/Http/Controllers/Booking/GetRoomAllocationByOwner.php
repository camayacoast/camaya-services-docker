<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetRoomAllocationByOwner extends Controller
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
        // return "OK";
        // return $request->id;

        $owner = \App\User::where('id', $request->id)->first();

        $entity_match = [
            'rudolph_25ai@yahoo.com' => 'SD Rudolph Cortez',
            // 'cressatupaz.organo@gmail.com' => 'SD Rudolph Cortez',
            'earthfield_marklouie@yahoo.com' => 'SD Louie Paule',
            'luzsdizon521@yahoo.com' => 'SD Luz Dizon',
            'johnzuno@gmail.com' => 'SD John Rizaldy Zuno',
            'brianbeltran@camayacoast.org' => 'SD Brian Beltran',
            'jadetuazon2005@yahoo.com' => 'SD Jake Tuazon',
            'joeybayon@gmail.com' => 'SD Joey Bayon',
            'grace888.camayacoast@yahoo.com' => 'SD Grace Laxa',
            'stephenbalbin@yahoo.com' => 'SD Stephen Balbin',
            'maripaulmilanes@yahoo.com.ph' => 'SD Maripaul Milanes',
            'sdnadcamaya@gmail.com' => 'SD Danny Ngoho',
            'camayaproperties@gmail.com' => 'SD Harry Colo',
            'lhotquiambao@hotmail.com' => 'SD Lhot Quiambao',
        ];
        
        if (!isset($entity_match[$owner['email']])) 
            {
                return [];
            }
        
        return \App\Models\Hotel\RoomAllocation::where('entity', $entity_match[$owner->email])
                            ->orderBy('date', 'desc')
                            ->with('room_type.property')
                            ->get();
        
    }
}
