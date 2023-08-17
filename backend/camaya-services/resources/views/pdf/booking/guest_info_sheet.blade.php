<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style>
	.table{
		border-collapse: collapse;
      border: 1px solid black;
	}
	.table thead tr th {
	  	border: 1px solid black;
      border-bottom: 1px solid transparent;
	  	font-size: 22px;
      text-align: left;
      letter-spacing: .03em;
      font-weight: bold;
      margin: 10px 0 5px;
      line-height: .75em;
      /*padding: 25px 25px;*/
	}

   .table tbody tr td {
	  	border: 1px solid black;
	}

   .guest-td {
      text-align: center;
      font-weight: 600;
   }
   .guest-td-data {
      padding:5px 10px; margin:10;
      text-align: center;
   }
   #informationSheet tbody tr td {
      padding: 1px 2px 2px 2px !important;
   }
   .guest-information-font-size{
      font-size: 10px;
   }
   .page-break-before {
        page-break-before: always;
    }
    @page {
        /* size: 21cm 29.7cm; */
        margin: 0mm 0mm 0mm 0mm;
        /* change the margins as you want them to be. */
    }
</style>
</head>
<body>
<table id="informationSheet" class="table table-bordered table-responsive guest-information-font-size">
    <tr>

        <td colspan="6">
            {{-- <img src='{{env('APP_URL')}}/images/Guest_Reg_Form_Header.jpg' align="center" width="100%"/> --}}
            {{-- <img src='{{env('APP_URL')}}/images/guest_info_header.jpg' align="center" width="100%"/> --}}
            @if (isset($booking['room_reservations']))
               @if ($booking['room_reservations'][0]['room_type']['property']['code'] == 'AF')
                  <img src='{{env('APP_URL')}}/images/Guest_Reg_Form_Header2.jpeg' align="center" width="100%"/>
               @else
                  <img src='{{env('APP_URL')}}/images/guest_info_header.jpg' align="center" width="100%"/>
               @endif
            @endif
        </td>
    </tr>
      <tr>
         <td colspan="6" align="right" style="border-top:1px solid transparent;">
            <i>The following information is required to process your arrival</i>
         </td>
      </tr>
      <tr>
         <td class="guest-td">Arrival Date</td>
         <td colspan="2">{{\Carbon\Carbon::parse($booking['start_datetime'])->format('F d, Y')}}</td>
         <td class="guest-td">Departure date</td>
         <td colspan="2">{{\Carbon\Carbon::parse($booking['end_datetime'])->format('F d, Y')}}</td>
      </tr>
      <tr>
         <td class="guest-td">Rack Rate</td>
         <td colspan="3">
            @foreach ($room_reservations_inclusion as $key => $room_reservation_inclusion)
                {{$room_reservation_inclusion['original_price']}}
                @if($key != count($room_reservations_inclusion) - 1)
                ,
                @endif
            @endforeach
        </td>
         <td colspan="2" class="guest-td">Occupants</td>
      </tr>
      <tr>
         <td class="guest-td">Room Rate Per Night</td>
         <td colspan="3">
            @foreach ($room_reservations_inclusion as $key => $room_reservation_inclusion)
                {{$room_reservation_inclusion['price']}}
                @if($key != count($room_reservations_inclusion) - 1)
                ,
                @endif
            @endforeach
         </td>
         <td rowspan="3" class="guest-td"></td>
      </tr>
      <tr>
         <td class="guest-td">Room Type</td>
         <td colspan="3">
            @foreach ($booking['room_reservations'] as $key => $room_reservation)
                {{$room_reservation['room_type']['name']}}
                @if($key != count($booking['room_reservations']) - 1)
                ,
                @endif
            @endforeach
         </td>
      </tr>
      <tr>
         <td class="guest-td">Room No.</td>
         <td colspan="3">
            @foreach ($booking['room_reservations'] as $key => $room_reservation)
                {{$room_reservation['room']['number']}}
                @if($key != count($booking['room_reservations']) - 1)
                ,
                @endif
            @endforeach
         </td>
      </tr>
      <tr>
         <td class="guest-td">Title</td>
         <td class="guest-td">Last Name</td>
         <td class="guest-td">First Name</td>
         <td class="guest-td">Initial</td>
         <td colspan="2" class="guest-td">Date of Birth</td>
      </tr>
      <tr>
         <td class="guest-td-data"></td>
         <td class="guest-td-data">{{strtoupper($booking['customer']['last_name'])}}</td>
         <td class="guest-td-data">{{strtoupper($booking['customer']['first_name'])}}</td>
         <td class="guest-td-data">{{strtoupper($booking['customer']['middle_name'])}}</td>
         <td colspan="2">✓
            <span style="text-align:center !important;"></span>
         </td>
      </tr>
      <tr>
         <td class="guest-td">Company Name</td>
         <td colspan="2">✓ </td>
         <td class="guest-td">Position / Title:</td>
         <td colspan="2">✓</td>
      </tr>
      <tr style="height:20px">
         <td colspan="6" class="guest-td"></td>
      </tr>
      <tr>
         <td class="guest-td">City / Town</td>
         <td colspan="2">{{$booking['customer']['address']}}</td>
         <td class="guest-td">State / Province</td>
         <td colspan="2"></td>
      </tr>
      <tr>
         <td class="guest-td">Country</td>
         <td colspan="5">✓ </td>
      </tr>
      <tr>
         <td class="guest-td">Postal</td>
         <td colspan="5">✓</td>
      </tr>
      <tr>
         <td class="guest-td">Nationality</td>
         <td colspan="5">✓ </td>
      </tr>
      <tr>
         <td class="guest-td">Type of ID / ID No.</td>
         <td colspan="5">✓</td>
      </tr>
      <tr>
         <td class="guest-td">Billing Instructions</td>
         <td colspan="5"></td>
      </tr>
      <tr>
         <td class="guest-td">Payment Method</td>
         <td colspan="5">
            <div>
               ✓<label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Visa</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Master</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Cash</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Voucher</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Other</label>
            </div>
         </td>
      </tr>
      <tr>
         <td class="guest-td">Purpose of Visit</td>
         <td colspan="5">
            <div class="col-md-10">
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Business</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Conference</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Company Meeting</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Holiday / Vacation</label>
               <label class="checkbox-inline" style="padding-right:30px !important;">
                  <input type="checkbox" />Other</label>
            </div>
         </td>
      </tr>
      <tr>
         <td colspan="2">
            <span class="guest-td">Telephone:✓ </span>
         </td>
         <td colspan="4">
            <span class="guest-td">Mobile No.:✓  </span>
            <span class="guest-td-input">{{$booking['customer']['contact_number']}}</span>
         </td>
      </tr>
      <tr>
         <td colspan="2">
            <span class="guest-td">Fax No.:✓ </span>
         </td>
         <td colspan="4">
            <span class="guest-td">Email Address:✓ </span>
            <span class="guest-td-input">{{$booking['customer']['email']}}</span>
         </td>
      </tr>
      <tr>
         <td colspan="6" class="guest-td text-center">
            <label>Occupants Details:✓</label>
         </td>
      </tr>
      <tr>
         <td colspan="2" class="guest-td">Name</td>
         <td colspan="2" class="guest-td">Age</td>
         <td colspan="2" class="guest-td">Date of Birth</td>
      </tr>
      @foreach($booking['guests'] as $keyOccupant => $occupant)
        <tr>
            <td colspan="2" class="guest-td-input">
                <b>{{$keyOccupant+1}}</b>
                {{$occupant['first_name']}} {{$occupant['last_name']}}
            </td>
            <td colspan="2" class="guest-td-input">{{$occupant['age']}}</td>
            <td colspan="2" class="guest-td-input"></td>
        </tr>
      @endforeach
      <tr style="text-align:center;font-size:8px">
         <td colspan="6">
            <p>
               <b>Other Information</b>
               <br/>All rooms are non smoking
               <br/>Check out time is 11am
               <br/>Late checkout is subject to additional charges and room availability
               <br/>Shorten stay will be subject to forfeiture of advance payment.
               <br/>
               <br><br><b>IMPORTANT: We respectfully inform you of the following items:</b>
               <br/>Hotel does not allow bringing in food and drinks. Hotel will not held loable for any injury / accident cause while inside the property.
               <br/>Children must be accompanied and guided by adults at all times.
               <br/>Money, jewels and other valuables brought to Camaya Coast Hotel are at the guest's sole risk. Camaya Coast hotel or the management accept no liability and shall not responsible for any loss or damage thereto and guests remain solely responsible
               for the safekeeping of any such items. Moreover the Hotel shall not be liable for any loss to valuable left in the room.
               <br/>Notwithstanding any method of payment, I agree that I am personally liable for all costs and charges incurred in the event that any such costs and charges are not paid in full and confirm that my responsibility and liability in that regard
               is not waived or raised in any way.
               <br/>
               <br><br><b>Data Privacy and Protection</b>
               <br/>During your stay, information will be collected about you and your preferences in the order to provide you with best possible service. The information will be retained to facilitate your future stays. The information collected will also be used
               for analysis purposes and to communicate news and promotions to you from the hotel.
               <br/>By signing this form I have read and understood the disclosure information above and I agree to my information being used in the material indicated. Furthermore I fully understood all information given herewith.</p>
         </td>
      </tr>
      <tr>
         <td colspan="3">
            <br><b>Guest Signature ✓</b><br><br>
        </td>
         <td colspan="3">
            <br><b>Date ✓</b><br><br>
         </td>
      </tr>
</table>

</body>
</html>
