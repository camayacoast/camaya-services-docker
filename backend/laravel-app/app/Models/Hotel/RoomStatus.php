<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_statuses';
    
    protected $fillable = [
        'room_id',
        'status',
        'date',
        'created_by',
    ];

    /**
        * Occupied: A guest is currently registered to the room.
        *
        * Complimentary: The room is occupied, but the guest is assessed no charge for its use.
        * 
        * Stay Over: The guest is not expected to check out today and will remain at least one more night.
        * 
        * On-change: The guest has departed, but the room has not yet been cleaned and readied for re-sale.
        * 
        * Do Not Disturb: The guest has requested not to be disturbed.
        * 
        * Sleep-out: A guest is registered to the room, but the bed has not been used.
        * 
        * Skipper: The guest has left the hotel without making arrangements to settle his or her account.
        * 
        * Sleeper: The guest has settled his or her account and left the hotel, but the front office staff has failed to properly update * the room’s status.
        * 
        * Vacant and ready: The room has been cleaned and inspected and is ready for an arriving guest.
        * 
        * Out-of-order: The room cannot be assigned to a guest. A room may be out-of-order for a variety of reasons including the need for * maintenance, refurbishing, and extensive cleaning.
        * 
        * Double Lock: The guest room door is locked from inside and outside two times so that no one can enter.
        * 
        * Lockout: The room has been locked so that the guest cannot re-enter until a hotel official clears him or her.
        * 
        * DNCO (Did Not Check Out): The guest made arrangements to settle his or her account (and thus is not a skipper), but has left * without informing the front office.
        * 
        * Due out: The room is expected to become vacant after the following day’s checkout time.
        * 
        * Do Not Paid: The guest is going to check out from  the hotel today.
        * 
        * Checkout: The guest has settled his or her account, returned the room keys, and left the hotel.
        * 
        * Late Check-out: The guest has requested and is being allowed to check out later than the hotel’s standard check-out time.
        */

}
