<table>
    <thead>
        <tr>
            <th colspan="12" height="50">
                {{-- <img src="images/magic-leaf-logo.png" width="140" /> --}}
            </th>
        </tr>
        <tr>
            <th colspan="12" align="center" style="font-size: 16pt; font-weight: bold; border: solid 3px black;">PASSENGER MANIFEST</th>
        </tr>
        <tr>
            <th colspan="12"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="12" style="font-size: 14pt; font-weight: bold;">Trip Information</th>
        </tr>
        <tr>
            <th colspan="12">Origin: {{$schedule->route->origin->name}}</th>            
        </tr>
        <tr>
            <th colspan="12">Destination: {{$schedule->route->destination->name}}</th>
        </tr>
        <tr>
            <th colspan="12"><!--spacer--></th>
        </tr>
        <tr>
            <th colspan="12" style="font-size: 14pt; font-weight: bold;">Schedule Information</th>
        </tr>
        <tr>
            <th colspan="12">Date: {{$schedule->trip_date}}</th>
        </tr>
        <tr>
            <th colspan="12">Departure: {{date('H:i', strtotime($schedule->start_time))}}</th>
        </tr>
        <tr>
            <th colspan="12">Arrival: {{date('H:i', strtotime($schedule->end_time))}}</th>
        </tr>
        <tr>
            <th colspan="12">Vessel: {{$schedule->transportation->name}}</th>
        </tr>        
        <tr>
            <th colspan="12"><!--spacer--></th>
        </tr>
    </thead>    
    <tbody>                
        @if (count($status) === 1 && $status[0] === 'arriving')
            <tr>
                <th colspan="12"><!--spacer--></th>
            </tr>                                                  
            <tr>
                <th colspan="7" align="center" valign="middle" style="font-weight: bold;">
                    Booking Status
                </th>
                <th colspan="6" align="center" valign="middle" style="font-weight: bold;">
                    Number of Bookings
                </th>
            </tr>
            <tr>
                <th colspan="7" align="center" valign="middle">
                    Pending
                </th>
                <th colspan="6" align="center" valign="middle">
                    {{
                        collect($trip_bookings)->sum(function ($tb) {
                            return $tb['status'] == 'pending';
                        })
                    }}
                </th>
            </tr>
            <tr>
                <th colspan="7" align="center" valign="middle">
                    Checked-in
                </th>
                <th colspan="6" align="center" valign="middle">
                    {{
                        collect($trip_bookings)->sum(function ($tb) {
                            return $tb['status'] == 'checked_in';
                        })
                    }}
                </th>
            </tr>            
            <tr>
                <th colspan="7" align="center" valign="middle">
                    Boarded
                </th>
                <th colspan="6" align="center" valign="middle">
                    {{
                        collect($trip_bookings)->sum(function ($tb) {
                            return $tb['status'] == 'boarded';
                        })
                    }}
                </th>
            </tr>
            <tr>
                <th colspan="7" align="center" valign="middle">
                    No Show
                </th>
                <th colspan="6" align="center" valign="middle">
                    {{
                        collect($trip_bookings)->sum(function ($tb) {
                            return $tb['status'] == 'no_show';
                        })
                    }}
                </th>
            </tr>
            <tr>
                <th colspan="7" align="center" valign="middle">
                    Cancelled
                </th>
                <th colspan="6" align="center" valign="middle">
                    {{
                        collect($trip_bookings)->sum(function ($tb) {
                            return $tb['status'] == 'cancelled';
                        })
                    }}
                </th>
            </tr>
            <tr>
                <th colspan="7" align="center" valign="middle" style="font-weight: bold;">
                    TOTAL CONFIRMED BOOKINGS
                </th>
                <th colspan="6" align="center" valign="middle" style="font-weight: bold;">
                    {{
                        collect($trip_bookings)->sum(function ($tb) {
                            return in_array($tb['status'], ['pending', 'boarded', 'cancelled', 'checked_in', 'no_show']);
                        })
                    }}
                </th>
            </tr>
        @else
            @if (count($trip_bookings))
            <tr>
                <th align="center" style="font-weight: bold;">#</th>
                <th align="center" style="font-weight: bold;">Booking ref #</th>
                {{-- <th align="center" style="font-weight: bold;">Guest code</th> --}}
                <th align="center" style="font-weight: bold;">Booking Type</th>
                <th align="center" style="font-weight: bold;">Last Name</th>
                <th align="center" style="font-weight: bold;">First Name</th>
                {{-- <th align="center" style="font-weight: bold;">M.I.</th> --}}
                <th align="center" style="font-weight: bold;">Age</th>
                <th align="center" style="font-weight: bold;">Nationality</th>
                <th align="center" style="font-weight: bold;">Contact Number</th>
                <th align="center" style="font-weight: bold;">Address</th>
                <th align="center" style="font-weight: bold;">Guest Type</th>
                {{-- <th align="center" style="font-weight: bold;">Market Segmentation</th> --}}
                <th align="center" style="font-weight: bold;">Booking Tags</th>
                {{-- <th align="center" style="font-weight: bold;">Guest Tags</th> --}}
                <th align="center" style="font-weight: bold;">Status</th>
            </tr>            
            @foreach($trip_bookings as $key => $trip_booking)
            <tr>
                <td align="center">{{ $key+1 }}</td>
                <td align="center">{{ strtoupper($trip_booking->booking->reference_number) }}</td>
                {{-- <td align="center">{{ strtoupper($trip_booking->passenger->guest_reference_number) }}</td> --}}
                <td align="center">{{ strtoupper($trip_booking->booking->type) }}</td>
                <td align="center">{{ strtoupper($trip_booking->passenger->last_name) }}</td>
                <td align="center">{{ strtoupper($trip_booking->passenger->first_name) }}</td>
                {{-- <td align="center">{{ strtoupper($trip_booking->passenger->middle_name[0] ?? '') }}</td> --}}
                <td align="center">{{ $trip_booking->passenger->age }}</td>
                <td align="center">{{ $trip_booking->passenger->nationality }}</td>
                <td align="center">{{ $trip_booking->booking->customer->contact_number }}</td>
                <td align="center">{{ $trip_booking->booking->customer->address }}</td>
                <td align="center">{{ $trip_booking->passenger->type }}</td>
                {{-- <td align="center">{{ $trip_booking->market_segmentation }}</td> --}}
                <td align="center">{{ implode(', ', collect($trip_booking->booking->tags)->pluck('name')->all()) }}</td>
                {{-- <td align="center">{{ implode(', ', collect($trip_booking->passenger->guest_tags)->pluck('name')->all()) }}</td> --}}
                <td align="center">{{ $trip_booking->status }}</td>
            </tr>
            @endforeach
            @endif
            <tr>
                <th colspan="11"><!--spacer--></th>
            </tr>
            <tr>
                <th colspan="11" align="right" valign="middle" style="font-weight: bold;">
                    Total Bookings:                
                </th>
                <th>
                {{
                    collect($trip_bookings)->sum(function ($tb) {
                        return in_array($tb['status'], ['pending', 'boarded', 'cancelled', 'checked_in', 'no_show']);
                    })
                }}
                </th>
            </tr>
            @if (in_array('pending', $status))
            <tr>
                <th colspan="11" align="right" valign="middle" style="font-weight: bold;">
                Pending:            
                </th>
                <th>
                {{
                    collect($trip_bookings)->sum(function ($tb) {
                        return $tb['status'] == 'pending';
                    })
                }}
                </th>
            </tr>
            @endif
            @if (in_array('checked_in', $status))
            <tr>
                <th colspan="11" align="right" valign="middle" style="font-weight: bold;">
                Checked-in:            
                </th>
                <th>
                {{
                    collect($trip_bookings)->sum(function ($tb) {
                        return $tb['status'] == 'checked_in';
                    })
                }}
                </th>
            </tr>
            @endif 
            @if (in_array('boarded', $status))
            <tr>
                <th colspan="11" align="right" valign="middle" style="font-weight: bold;">
                Boarded:            
                </th>
                <th>
                {{
                    collect($trip_bookings)->sum(function ($tb) {
                        return $tb['status'] == 'boarded';
                    })
                }}
                </th>
            </tr>
            @endif
            @if (in_array('no_show', $status))
            <tr>
                <th colspan="11" align="right" valign="middle" style="font-weight: bold;">
                No show:            
                </th>
                <th>
                {{
                    collect($trip_bookings)->sum(function ($tb) {
                        return $tb['status'] == 'no_show';
                    })
                }}
                </th>
            </tr>
            @endif
            @if (in_array('cancelled', $status))
            <tr>
                <th colspan="11" align="right" valign="middle" style="font-weight: bold;">
                Cancelled:            
                </th>
                <th>
                {{
                    collect($trip_bookings)->sum(function ($tb) {
                        return $tb['status'] == 'cancelled';
                    })
                }}
                </th>
            </tr>
            @endif  
        @endif       
        
    </tbody>
</table>
