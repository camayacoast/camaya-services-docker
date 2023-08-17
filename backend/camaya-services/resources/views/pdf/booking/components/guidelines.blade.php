<style>
    .page-break {
        page-break-after: always;
    }

    .page-break-before {
        page-break-before: always;
    }
    .tb-cancellations th, .tb-cancellations, .tb-cancellations td {
        border: 1px solid black;
        border-collapse: collapse;
        text-align: center;
        padding: 5px;
    }
</style>

        {{-- <div style="text-align: center;"><img src="images/camaya-logo.png" width="140" /></div>
    
        <h3 style="text-transform: uppercase; text-align: center;">BOOKING REMINDERS &amp; SAFETY GUIDELINES</h3>

        <strong>Camaya Coast Management Preliminary Statement</strong>

        <p>As a precautionary measure and as mandated by the government due to COVID-19 outbreak, the Management will strictly impose the following protocols:</p>
        
        <ol>
            <li>Proper wearing of face mask and/or face shield</li>
            <li>Physical Distancing</li>
            <li>Thermal screening to be conducted upon entering the terminal and the main gate of the resort</li>
            <li>Guest or passenger with body temperature above <span style="color: crimson">37.5&deg;C</span> will not be permitted to enter the ferry terminal and/or the resort. </li>
        </ol>

        <hr/> --}}

        <?php
            $isGolfBooking = collect($booking['inclusions'])->filter(function ($inclusion) {
                return \Str::contains(($inclusion->item), ['golf', 'Golf']);
            });
        ?>

        @if (count($isGolfBooking) > 0)
            @include('pdf.booking.components.golf_guidelines')
        @endif

        {{-- <h4 style="text-decoration: underline;">GENERAL INFORMATION</h4> --}}

        @if ($booking['mode_of_transportation'] == 'camaya_transportation')

            <p><strong>Ferry Terminal Address:</strong> Esplanade Seaside Terminal, Seaside Blvd., SM MOA Complex, Pasay&nbsp;City</p>
            
            <h4>SEA TRAVEL REQUIREMENTS</h4>

            <ol>
                {{-- <li>Booking Confirmation</li> --}}
                {{-- <li>Government Issued ID</li> --}}
                {{-- <li>Vaccination Card <i>(Fully-vaccinated only)</i></li>
                <li>
                    <strong>Bataan Get Pass Application (QR) Code </strong>
                    <ol type="i">
                        <li>Please visit <a target="_blank" href="https://bit.ly/camayacoasthealthdeclaration">https://bit.ly/camayacoasthealthdeclaration</a> to download the Camaya Coast’s Health Declaration Form</li>
                        <li>Fill out the Health Declaration Form</li>
                        <li>Present the Health Declaration Form at the Camaya Coast Ferry Counter</li>
                    </ol>
                </li> --}}
                <li><strong>Check-in Counter:</strong> Booking Confirmation &amp; Government issued ID or any equivalent ID.</li>
                <li><strong>Boarding Area:</strong> QR Code &amp; Government issued ID or any equivalent ID. </li>
                {{-- <li>QR Coded Tourist Pass from Bataan VIS.I.T.A (Visitor Information and Travel Assistant) website. Register at <a href="https://visita.beholdbataan.ph" target="_blank">https://visita.beholdbataan.ph</a></li> --}}
            </ol>
            
            {{-- <small style="text-align: center;"><i><strong>**We encourage guests to fill out the Health Declaration Form in advance to ensure seamless entry or check-in upon arrival.</strong> For further guide accomplishing the travel requirements, please view this link: <a href="https://camayacoast.com/how-to-get-there" target="_blank">https://camayacoast.com/how-to-get-there</a></i></small> --}}
            
            <hr/>

            <h4>FERRY CHECK-IN/BOARDING REMINDERS AND GUIDELINES</h4>
            <ol>
                <li>Ferry Schedule is subject to change based on weather and sea conditions.</li>
                <li>All guests must be pre-booked. Same day booking and on the day request for additional guests are not allowed. </li>
                <li>Guest or passenger must arrive at the Ferry Terminal 1 hour and 30 minutes and may check in before the departure time of the ferry. </li>
                <li>Check-in cut-off time is 30 minutes before the departure time of the ferry.</li>
                <li>Boarding gate closes 15 minutes before the departure time of the ferry.</li>
                <li>Pregnant women are not allowed to ride the ferry.</li>
                <li>The ferry booking of the guest or passenger arriving beyond the mentioned cut-off time above will be forfeited automatically.</li>
                <li>No talking and unnecessary phone calls while inside the ferry.</li>
                <li>Guest or passenger is advised to refrain from standing or walking inside the ferry unless it is necessary.</li>
                <li>NO FOOD allowed inside the ferry.</li>
                <li>Changing of seats is STRICLY PROHIBITED.</li>
                <li>Guest or passenger manifesting colds/cough and other symptoms shall not board the ferry.</li>
                {{-- <li>Cancellation made less than 72 hours is strictly not subject for refund or rebooking. Should the Management cancel the trip due to technical problem or inclement weather (Force Majeure) condition, the Guest or Passenger may opt to reschedule his/her trip or refund it in full amount. </li> --}}
            </ol>

        @endif

        @if ($booking['mode_of_transportation'] != 'camaya_transportation')
            
            {{-- <p><strong>Camaya Coast Resort Address:</strong> Camaya Coast, Sitio Wain, Brgy. Biaan, Mariveles, Bataan</p> --}}
            
            <h4>LAND TRAVEL REQUIREMENTS</h4>
            
            <ol>
                <li>Booking Confirmation and QR Code</li>
                <li>Government Issued ID or any equivalent ID</li>
                {{-- <li>QR Coded Tourist Pass from Bataan VIS.I.T.A (Visitor Information and Travel Assistant) website. Register at <a href="https://visita.beholdbataan.ph" target="_blank">https://visita.beholdbataan.ph</a></li> --}}
                {{-- <li>Vaccination Card <i>(Fully-vaccinated only)</i></li>
                <li>
                    <strong>Bataan Get Pass Application (QR) Code </strong>
                    <ol type="i">
                        <li>i.	Download the QR Code from the <strong>Get Pass Application</strong> at Google Play Store (for android user) or Apple Store (iOS user) </li>
                        <li>Register</li>
                        <li>Fill out the Travel Pass Form and indicate your Travel Date</li>
                        <li>Generate and save the QR Code</li>
                        <li>Present the QR Code at the Checkpoint</li>
                    </ol>
                    <p><strong>*1 QR Code : 1 Person</strong></p>
                </li>
                <li>
                    <strong>Health Declaration Form</strong>
                    <ol type="i">
                        <li>Please visit <a target="_blank" href="https://bit.ly/camayacoasthealthdeclaration">https://bit.ly/camayacoasthealthdeclaration</a> to download the Camaya Coast’s Health Declaration Form</li>
                        <li>Fill out the Health Declaration Form</li>
                        <li>Present the Health Declaration Form  at the Camaya Coast Main gate (for land travel) and Camaya Coast Ferry Counter (for sea travel)</li>
                    </ol>
                </li> --}}
            </ol>
            
            {{-- <small style="text-align: center;"><i><strong>**We encourage guests to fill out the Health Declaration Form in advance to ensure seamless entry or check-in upon arrival.</strong> For further guide accomplishing the travel requirements, please view this link: <a href="https://camayacoast.com/how-to-get-there" target="_blank">https://camayacoast.com/how-to-get-there</a></i></small> --}}
        
        @endif

        <hr/>

        {{-- <h4>PAYMENT AND REFUND POLICIES:</h4>
        <ol>
            <li>All bookings must be paid in full upon reservation.</li>
            <li>Payment options:<br/>
                Credit Card/Debit Card via PayPal<br/>
                Direct Bank Deposit <br/>
                Online Bank Transfer<br/>
            </li>
            <li>Refund request must be emailed at <a href="mailto:reservations@camayacoast.com" target="_blank">reservations@camayacoast.com</a></li>
            <li>Approved refund request will be processed within 30 to 60 working days from receipt of request.</li>
            <li>Vouchers or Gift Certificates are non-refundable.</li>
        </ol> --}}

        @if ($booking['type'] == 'ON')
        <h4>HOTEL POLICIES AND GUIDELINES</h4>
        <ol>
            {{-- <li>Smoking inside the Hotel is prohibited, there are designated smoking areas outside.</li> --}}
            <li>Check-in time: 2:00PM. Check-out time: 11:00AM</li>
            <li>Early check-in and check-out is subject to room availability.</li>
            <li>Extra person beyond minimum capacity of 2 persons shall be charged Php 1,000/head.</li>
            <li>For room only bookings; breakfast is excluded.</li>
            <li>Pets are not allowed inside the resort premises.</li>
            <li>All rooms are non-smoking.</li>
            <li>All incidental charges will be on the personal account of the guest.</li>
            <li>Outside FOOD and DRINKS are not allowed inside the resort.</li>
        </ol>
        <hr/>
        @endif

        <h4>PAYMENTS, REBOOKING, REVISIONS AND OTHER RELATED POLICIES</h4>
        <ol>
            @if ($booking['mode_of_transportation'] != 'camaya_transportation')
                <li>The resort is open from 7:00AM onwards.</li>
            
            @endif
                <li>All bookings must be paid in full upon reservation.</li>
                
            @if ($booking['mode_of_transportation'] != 'camaya_transportation')
                <li>Outside FOOD and DRINKS are not allowed inside the resort.</li>
            
            @endif
                <li>Rebooking made at least 5 days of the arrival date is free of charge. Any rebooking beyond the said period shall be charged in full.</li>
                <li>In case there is a rate difference between the original and rebooked dates, the higher rate shall be applied.</li>

            @if ($booking['mode_of_transportation'] == 'camaya_transportation')
                <li>Outside FOOD and DRINKS are not allowed inside the resort.</li>
            
            @endif
                <li>No Show is not subject to rebooking. </li>
                <li>Acts of God or extreme medical emergencies may be allowed upon Management's discretion. Supporting documents may be required.</li>
                <li>Any revisions or changes in bookings must be sent at <a href="mailto:reservations@camayacoast.com" target="_blank">reservations@camayacoast.com</a></li>
                <li>Booking Cancellations <br/>
                    <table class="tb-cancellations">
                        <tbody>
                            <tr>
                                <th>REASON</th>
                                <th>STATUS</th>
                                <th>OPTIONS</th>
                            </tr>
                            <tr>
                                <td>Force Majeure</td>
                                <td>Cancelled</td>
                                <td rowspan="2">
                                    Rebookable within 3 months <br/>
                                    Transferrable within 3 months <br/>
                                    Refund
                                </td>
                            </tr>
                            <tr>
                                <td>Ferry Technical Concern</td>
                                <td>Via land transfer (own vehicle)</td>
                            </tr>
                            <tr>
                                <td>Client Personal Reasons</td>
                                <td>Forfeited</td>
                                <td>
                                    Non-refundable<br/>
                                    Non-rebookable<br/>
                                    Non-transferable
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </li>
            @if ($booking['type'] == 'ON')  
                <li>Rebooking is subject to room availability.</li>
            
            @endif
            <li>For rebooking requests, charges may apply. </li>
            <li>Vouchers or Gift Certificates are non-refundable.</li>
            <li>Pets are not allowed inside the resort premises.</li>
            <li><strong>ACTS OF GOD; FORTUITOUS EVENT</strong>
                <ol type="a">
                    <li>Camaya Coast Management and its affiliates, directors, officers and employees shall not be liable for any delay or failure of performance of its/their obligation/s due to Acts of God or unforeseeable circumstances.</li>
                    <li>Any additional expenses incurred from changes in the itinerary brought about by weather, road and local conditions shall be borne by the passengers or travel agency/tour operator.</li>
                    <li>Any cancellation due to Acts of God or unforeseeable circumstances, changes in the Community Quarantine status as defined by law shall be settled equitably between Camaya Coast Management and guest/tour agency/tour operator taking into consideration the presence or absence of any contributory mitigating or aggravating factors from any of the parties.</li>
                </ol>
            </li>

            <li><strong>RELEASE AND WAIVER OF LIABILITY</strong>
                <p style="margin-top: 0px; margin-bottom: 0px;">Please be aware that the Camaya Coast Management, has taken reasonable 	steps and precautions to assure your safety while staying with us. In using our property, however, you do so at your own risk. Your  voluntary checking-in to 	our hotels and/or entry to our Commercial Area indicates your agreement to 	release and hold the Camaya Coast Management its owners, affiliates,  and  	agents, from any liability. Further, you agree:</p>
                <ol type="a">
                    <li>To irrevocably and unconditionally waive and release and hold harmless from liability  the Camaya Coast Management, its owners, affiliates, and agents from any and all liabilities, claims, actions, damages, costs, or expenses of any nature whatsoever whether in law or equity, known or unknown, occurring during, caused by, relating to, or arising in any way from staying or visiting the resort;</li>
                    <li>To acknowledge and agree that you assume full responsibility for the security and safekeeping of your personal belongings and hereby waive any claims against the Camaya Coast Management for any loss, damage, or theft of such items, including but not limited to money, jewelry, and any other valuables brought within the premises of Camaya Coast.</li>
                    <li>That the stay and visit to our resort may involve voluntary participation in physical activities both indoors and outdoors, and certain exposure to walks, steps, paths, and roads that are uneven. With these, and all related activities, there is a certain element of risk. By checking-in to our hotels or entering our Commercial Area, you hereby acknowledge that participation and use of facilities and grounds are at your own risk, and that you assume all responsibilities for any and all aspects of your participation including your guests, both adults and children;</li>
                    <li>To irrevocably and unconditionally release and hold the Camaya Coast Management harmless from any financial or other liability for any injury, bodily harm, sickness, illness, or loss of life that you or your guests and company may suffer and from any economic harm or loss of property occurring during, caused by, relating to, or arising in any way out of staying at our resort;</li>
                    <li>TO EXPRESSLY WAIVE AND RELEASE ANY AND ALL CLAIMS, NOW 	KNOWN OR HEREAFTER KNOWN, AGAINST THE CAMAYA COAST 	MANAGEMENT AND ITS OFFICERS, DIRECTORS, EMPLOYEES, 	AGENTS, AFFILIATES, MEMBERS, SUCCESSORS, AND ASSIGNS, ON ACCOUNT OF INJURY, DEATH, OR PROPERTY DAMAGE ARISING OUT OF OR ATTRIBUTABLE TO USE OF CAMAYA COAST RESORT. YOU COVENANT NOT TO MAKE OR BRING ANY SUCH CLAIM AGAINST THE CAMAYA COAST MANAGEMENT, AND FOREVER RELEASE AND DISCHARGE THE CAMAYA COAST MANAGEMENT FROM LIABILITY UNDER SUCH CLAIMS;</li>
                    <li>TO DEFEND, INDEMNIFY, AND HOLD HARMLESS THE CAMAYA COAST MANAGEMENT, AND ITS OFFICERS, DIRECTORS, EMPLOYEES, AGENTS, AFFILIATES, MEMBERS, SUCCESSORS, AND ASSIGNS, AGAINST ANY AND ALL LOSSES, DAMAGES, LIABILITIES, DEFICIENCIES, CLAIMS, ACTIONS, JUDGMENTS, SETTLEMENTS, INTEREST, AWARDS, PENALTIES, FINES, COSTS, OR EXPENSES OF WHATEVER KIND, INCLUDING REASONABLE ATTORNEY FEES, FEES AND OTHER RELATED COSTS.</li>
                </ol>
                <p>This Release and Waiver of Liability shall not apply to any claims arising from the negligence of the Camaya Coast Management, its owners or agents.</p>
            </li>
        </ol>

        {{-- <h4>MISCELLANEOUS:</h4>
        <ol>
            <li>The Management shall not be liable for any incident resulting to injury, loss, or damage to the Guest while he is at the resort premises. </li>
            <li>The Guest shall be held liable for any loss or damage to the resort properties and/or facilities caused by his/her negligence and/or non-compliance of the resort's policies. </li>
            <li>For general safety, Guest is expected to conduct himself/herself in a respectable manner and shall not cause annoyance to other Guests while in the premises of the resort. </li>
            <li>The resort shall not be liable for any loss or damage to the Guest’s property, injury or loss of life arising from the use of its facilities or equipment. </li>
            <li>Guest is expected to observe, bind by and comply to all applicable policies and procedures being enforced within the premises of the resort. </li>
        </ol> --}}

        {{-- <hr/> --}}

        {{-- <h4>RESORT REMINDERS AND  GUIDELINES:</h4>
        <ol>
            <li>Guest must wear his/her face mask and/or face shield properly upon entering and while he/she is in the premises of the resort.</li>
            <li>Guest’s vehicle shall be sanitized at the main gate of the resort.</li>
            <li>Thermal screening shall be conducted at the main gate of the resort.</li>
            <li>Guest with body temperature above <span style="color: crimson">37.5&deg;C</span> will not be permitted to enter the ferry terminal and the resort. He/she will be assisted by our Emergency Response Team.</li>
            <li>Guest must present his/her booking confirmation at the main gate. </li>
            <li>Security Guard and/or Resort Personnel will check and verify the booking confirmation </li>
            <li>All ages are allowed and subject to the government restrictions and guidelines. <strong>In the event that any guest’s travel/entry should be delayed or disallowed by any government authority, including those designated at government checkpoint/s, Camaya Coast Resort Management shall not be liable for said delay or prohibition to travel.</strong></li>
            <li>Physical Distancing must be observed.</li>
            <li>No pets allowed.</li>
            <li>Guest is advised to practice “Clean As You Go” or CLAYGO and throw his/her trash properly.</li>
            <li>Guest is advised to wash their hands with soap or use alcohol or sanitizer.</li>
        </ol> --}}
        
        <p style="text-align: center; margin: 0px;"><strong>Thank you and we look forward to seeing you at Camaya Coast!</strong></p>