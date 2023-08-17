<style>
.page-break {
    page-break-after: always;
}

.page-break-before {
    page-break-before: always;
}

.table-noborder {
    /* margin: 0 !important; */
    /* padding: 0 !important; */
}

.table-noborder td {
    border: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

.table{
    border-collapse: separate;
    border-spacing: 8px;
    font-family: Arial, sans-serif;
    font-size: 10px;
}

.table-min {
    border-collapse: separate;
    border-spacing: 0px;
    font-family: Arial, sans-serif;
    font-size: 10px;
}

.table-min td {
    border-left: solid 1px #111;
    border-top: solid 1px #111;
    padding: 3px;
}

.table-min td.label {
    width: 200px;
    min-width: 0%;
}

.table-min td:last-child {
    border-right: solid 1px #111;
}

.table-min tr:last-child > td {
    border-bottom: solid 1px #111;
}

.table thead tr th {
/* font-size: 22px;
text-align: left;
letter-spacing: .03em;
font-weight: bold;
margin: 10px 0 5px;
line-height: .75em; */
/*padding: 25px 25px;*/
}
.table tbody tr td {
/* border: 1px solid black; */
}

.blue-bg {
    background: rgb(1,126,197);
    color: white;
}

.italic {
    font-style: italic;
}

.w-100 {
    width: 100%;
}

.m-0 {
    margin: 0;
}

.b-none {
    border: none;
}

.v-top {
    vertical-align: top;
}

.inner-label {
    font-size: 7px;
    /* margin-bottom: 8px; */
}

.inner-label span + span {
    margin-left: 18%;
}

.household-income li, .gender li, .radio li {
    list-style: circle;
    line-height:14px;
    font-size: 24px;
}

.gender {
    margin: 0;
}

.gender li {
    float: left;
}

.gender li:first-child {
    margin-right: 50px;
}

.household-income li span, .gender li span, .radio li span  {
    font-size: 7px;
    line-height:14px;
    vertical-align:bottom;
    margin-left: -5px;
    /* content:".";
    font-size:100px;
    vertical-align:middle;
    line-height:20px; */
}
</style>

<img src="{{'images/BIS-Header.jpg'}}" align="center" width="100%"/>

<div style="padding: 20px 10px; position: relative; width: 100%;">
    <table class="table w-100 m-0" style="width: 100%;">
        <tr>
            <td class="blue-bg" style="height: 16px; text-align: center;">1</td>
            <td style="padding: 0;" rowspan="2" class="b-none v-top">
                <table class="table-min m-0 w-100" style="padding: 0px;">
                    <tr>
                        <td class="blue-bg" style="width: 140px;">Source</td>
                        <td class="label" style="width: 50px;">Phase</td>
                        <td colspan="3">{{$data->subdivision}}</td>
                        <td class="label" style="width: 90px;">Approximate Area</td>
                        <td>{{$data->area}}</td>
                    </tr>
                    <tr>
                        <td>{{$data->source}}</td>
                        <td class="label" style="width: 50px;">Block</td>
                        <td>{{$data->block}}</td>
                        <td class="label" style="width: 50px;">Lot</td>
                        <td>{{$data->lot}}</td>
                        <td class="label" style="width: 90px;">Total Selling Price</td>
                        <td>{{number_format($data->total_selling_price, 2)}}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
        </tr>

        <tr>
            <td class="blue-bg" style="height: 16px; text-align: center;">2</td>
            <td class="blue-bg b-none" style="text-align: center;">
                PERSONAL INFORMATION
            </td>
        </tr>
        
        <tr>
            <td colspan="2">
                <table class="w-100">
                    <tr>
                        <td class="v-top" style="width: 120px;"><span class="italic">Please indicate full name to appear in the contract</span></td>
                        <td style="padding: 0; margin: 0;">
                            <table class="table-min w-100" style="width: 100%;">
                                <tr>
                                    <td colspan="4">
                                        <table class="table-noborder w-100 m-0" style="border-collapse: separate; border-spacing: 0; 0; font-size: 7px;">
                                            <tr>
                                                <td class="v-top">Last Name<br/>
                                                    <span style="font-size: 10px;">{{$data->client->last_name}}</span>
                                                </td>
                                                <td class="v-top">First Name<br/>
                                                    <span style="font-size: 10px;">{{$data->client->first_name}}</span></td>
                                                <td class="v-top">Middle Name<br/>
                                                    <span style="font-size: 10px;">{{$data->client->middle_name}}</span></td>
                                                <td class="v-top">Extn (Jr., Sr., III)<br/>
                                                    <span style="font-size: 10px;">{{$data->client->information->extension ?? ''}}</span></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="vertical-align: top">
                                        <ul class="gender" style="margin: 0;">
                                            <li style="{{ $data->client->information->gender == 'male' ? 'list-style: disc !important;' : ''}}"><span>Male</span></li>
                                            <li style="{{ $data->client->information->gender == 'female' ? 'list-style: disc !important;' : ''}}"><span>Female</span></li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="inner-label">
                                            Birth Date
                                        </div>
                                        {{date('M d, Y', strtotime($data->client->information->birth_date))}}
                                    </td>
                                    <td style="border-right: none; width: 110px;">
                                        <div class="inner-label">
                                            Place of Birth
                                        </div>
                                        {{$data->client->information->birth_place}}
                                    </td>
                                    <td style="border-left: none;"></td>
                                    <td>
                                        <div class="inner-label">
                                            Citizenship
                                        </div>
                                        {{$data->client->information->citizenship}}
                                    </td>
                                    <td class="v-top" rowspan="4" style="border-bottom: solid 1px #111;">
                                        <div style="float: left;">
                                            <p style="text-align: center; font-size: 7px; margin: 0;"><strong>MONTHLY HOUSEHOLD INCOME</strong></p>
                                            <ul class="household-income" style="border: none;">
                                                <li style="{{ $data->client->information->monthly_household_income == 'below_50000' ? 'list-style: disc !important;' : ''}}"><span>Below P50,000</span></li>
                                                <li style="{{ $data->client->information->monthly_household_income == '50001_80000' ? 'list-style: disc !important;' : ''}}"><span>P50,001 &ndash; P80,000<span></li>
                                                <li style="{{ $data->client->information->monthly_household_income == '80001_120000' ? 'list-style: disc !important;' : ''}}"><span>P80,001 &ndash; P120,000<span></li>
                                                <li style="{{ $data->client->information->monthly_household_income == '120001_150000' ? 'list-style: disc !important;' : ''}}"><span>P120,001 &ndash; P150,000<span></li>
                                                <li style="{{ $data->client->information->monthly_household_income == '150001_180000' ? 'list-style: disc !important;' : ''}}"><span>P150,001 &ndash; P180,000<span></li>
                                                <li style="{{ $data->client->information->monthly_household_income == 'above_180000' ? 'list-style: disc !important;' : ''}}"><span>Above P180,000</span></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="inner-label">
                                            Government Issued Identification
                                        </div>
                                            {{$data->client->information->government_issued_id}}
                                    </td>
                                    <td colspan="2" style="border-right: none;">
                                        <div class="inner-label">
                                            Place and Date of Issuance
                                        </div>
                                            {{$data->client->information->government_issued_id_issuance_place}} {{date('M d, Y', strtotime($data->client->information->government_issued_id_issuance_date))}}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="inner-label">
                                            Tax Identification No.
                                        </div>
                                            {{$data->client->information->tax_identification_number}}
                                    </td>
                                    <td colspan="2" style="border-right: none;">
                                        <div class="inner-label">
                                            Occupation
                                        </div>
                                        {{$data->client->information->occupation}}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="border-right: none;">
                                        <div class="inner-label">
                                            Company Name and Address
                                        </div>
                                        {{$data->client->information->company_name}} {{$data->client->information->company_address}}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center; padding: 5px;">
                            <strong>SPOUSE INFORMATION</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="v-top"><span class="italic">Please indicate full name to appear in the contract</span></td>
                        <td>
                            <table class="table-min w-100" style="border-collapse: collapse;">
                                <tr>
                                    <td colspan="2">
                                        <table class="table-noborder w-100 m-0" style="border-collapse: separate; border-spacing: 0; 0; font-size: 7px;">
                                            <tr>
                                                <td class="v-top">Last Name<br/>
                                                    <span style="font-size: 10px;">{{$data->client->spouse->spouse_last_name ?? ''}}</span>
                                                </td>
                                                <td class="v-top">First Name<br/>
                                                    <span style="font-size: 10px;">{{$data->client->spouse->spouse_first_name ?? ''}}</span></td>
                                                <td class="v-top">Middle Name<br/>
                                                    <span style="font-size: 10px;">{{$data->client->spouse->spouse_middle_name ?? ''}}</span></td>
                                                <td class="v-top">Extn (Jr., Sr., III)<br/>
                                                    <span style="font-size: 10px;">{{$data->client->spouse->spouse_extension ?? ''}}</span></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="vertical-align: top">
                                        <ul class="gender">
                                            <li style="{{ isset($data->client->spouse->spouse_gender) && $data->client->spouse->spouse_gender == 'male' ? 'list-style: disc !important;' : ''}}"><span>Male</span></li>
                                            <li style="{{ isset($data->client->spouse->spouse_gender) && $data->client->spouse->spouse_gender == 'female' ? 'list-style: disc !important;' : ''}}"><span>Female</span></li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="inner-label">
                                            Birth Date
                                        </div>
                                        {{isset($data->client->spouse->spouse_birth_date) ? date('M d, Y', strtotime($data->client->spouse->spouse_birth_date)) : ''}}
                                    </td>
                                    <td style="border-right: none;">
                                        <div class="inner-label">
                                            Place of Birth
                                        </div>
                                        {{$data->client->spouse->spouse_birth_place ?? ''}}
                                    </td>
                                    <td>
                                        <div class="inner-label">
                                            Citizenship
                                        </div>
                                        {{$data->client->spouse->spouse_citizenship ?? ''}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="inner-label">
                                            Government Issued Identification
                                        </div>
                                        {{$data->client->spouse->spouse_goverment_issued_id ?? ''}}
                                    </td>
                                    <td style="border-right: 0;">
                                        <div class="inner-label">
                                            Place and Date of Issuance
                                        </div>
                                        {{$data->client->spouse->spouse_government_issued_id_issuance_place ?? ''}} {{ isset($data->client->spouse->spouse_government_issued_id_issuance_date) ? date('M d, Y', strtotime($data->client->spouse->spouse_government_issued_id_issuance_date)) : ''}}
                                    </td>
                                    <td>
                                        <div class="inner-label">
                                            Tax Identification No.
                                        </div>
                                        {{$data->client->spouse->spouse_tax_identification_number ?? ''}}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="inner-label">
                                            Company Name and Address
                                        </div>
                                        {{$data->client->spouse->spouse_company_name ?? ''}} {{$data->client->spouse->spouse_company_address ?? ''}}
                                    </td>
                                    <td>
                                        <div class="inner-label">
                                            Occupation
                                        </div>
                                        {{$data->client->spouse->spouse_occupation ?? ''}}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="blue-bg" style="padding: 5px; width: 50px; text-align: center;">3</td>
            <td class="blue-bg b-none" style="text-align: center;">
                CONTACT INFORMATION
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <table class="w-100">
                    <tr>
                        <td class="v-top" style="width: 120px;"><span class="italic">&nbsp;</span></td>
                        <td>
                            <table class="table-min w-100" style="border-collapse: collapse;">
                                <tr>
                                    <td>
                                        <div class="inner-label">
                                            Home Phone
                                        </div>
                                        {{$data->client->information->home_phone}}
                                    </td>
                                    <td>
                                        <div class="inner-label">
                                            Business Phone
                                        </div>
                                        {{$data->client->information->business_phone}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="inner-label">
                                            Email Address
                                        </div>
                                        {{$data->client->email}}
                                    </td>
                                    <td>
                                        <div class="inner-label">
                                            Mobile Number
                                        </div>
                                        {{$data->client->information->mobile_number}}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>


        <tr>
            <td class="blue-bg" style="padding: 5px; width: 50px; text-align: center;">4</td>
            <td class="blue-bg b-none" style="text-align: center;">
                COMPLETE ADDRESS
            </td>
        </tr>


        <tr>
            <td colspan="2">
                <table class="w-100" cellspacing="0">
                    <tr>
                        <td class="v-top" style="width: 125px;"><span class="italic">&nbsp;</span></td>
                        <td class="v-top" style="padding: 0;">
                            <small>Select your preferred mailing address</small>
                            <table class="table-min w-100" style="border-collapse: collapse;">
                                <tr>
                                    <td style="width: 100px;">
                                        <ul class="radio m-0">
                                            <li style="{{ $data->client->information->preferred_mailing_address == 'current_home_address' ? 'list-style: disc !important;' : ''}}"><span>Current</span></li>
                                        </ul>
                                    </td>
                                    <td>
                                        {{ implode(", ", array_filter([$data->client->information->current_home_address,$data->client->information->current_home_address_house_number,$data->client->information->current_home_address_street,$data->client->information->current_home_address_baranggay,$data->client->information->current_home_address_city,$data->client->information->current_home_address_province,$data->client->information->current_home_address_country ?? 'Philippines',$data->client->information->current_home_address_zip_code])) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 100px;">
                                        <ul class="radio m-0">
                                            <li style="{{ $data->client->information->preferred_mailing_address == 'permanent_home_address' ? 'list-style: disc !important;' : ''}}"><span>Permanent</span></li>
                                        </ul>
                                    </td>
                                    <td>
                                        {{ implode(", ", array_filter([$data->client->information->permanent_home_address,$data->client->information->permanent_home_address_house_number,$data->client->information->permanent_home_address_street,$data->client->information->permanent_home_address_baranggay,$data->client->information->permanent_home_address_city,$data->client->information->permanent_home_address_province,'Philippines',$data->client->information->permanent_home_address_zip_code])) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>


        <tr>
            <td class="blue-bg" style="padding: 5px; text-align: center;">5</td>
            <td class="blue-bg b-none" style="text-align: center;">
                OFFICIAL RECEIPT ISSUANCE POLICIES
            </td>
        </tr>

        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td class="b-none">
                <table class="table-min w-100">
                    <tr>
                        <td style="font-size: 7px;">
                            <p><strong>Buyer</strong> personally appearing in the Company’s Head Office to obtain the Official Receipt for any payment made must present:</p>
                            <p>1 Valid Government ID; and<br/>
                            Reservation Agreement.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 7px;">
                            <p><strong>Buyer’s Representative</strong> may collect the said Official Receipt by presenting the following:</p>
                            <p>
                                <strong>"Valid"</strong> Special Power of Attorney duly notarized and signed by the Buyer;<br/>
                                2 valid Government IDs;<br/>
                                Reservation Agreement.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 7px;">
                            <p>
                                <strong>Assigned Property Consultant.</strong><br/>
                                The Buyer may opt to assign his/her property consultant to pick-up the receipts in his/her behalf. In the event that the property consultant ceases working for the Company, upon notice to the Buyer, he/she may assign the property consultant’s successor to obtain the official receipts in his/her behalf. 
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td style="font-size: 7px; padding-left: 14px;">
                For security purpose, the Company reserves the right not to release any official receipt in the absence of any of the above-enumerated requirements
            </td>
        </tr>


        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td class="blue-bg b-none" style="text-align: center;">
                REFER-A-FRIEND PROMO
            </td>
        </tr>

        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td class="b-none">
                <table class="w-100">
                    <tr>
                        <td style="font-size: 10px; padding: 8px; width: 140px;" class="blue-bg b-none">
                            Existing Client
                        </td>
                        <td style="border: solid 1px #111;">{{$data->referrer->first_name ?? ''}} {{$data->referrer->last_name ?? ''}}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; padding: 8px; width: 140px;" class="blue-bg b-none">
                            Property Purchased
                        </td>
                        <td style="border: solid 1px #111;">
                            @if (isset($data->referrer_property_details))
                                {{$data->referrer_property_details->subdivision ?? ''}} Block {{$data->referrer_property_details->block ?? ''}} Lot {{$data->referrer_property_details->lot ?? ''}}
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>


        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td class="blue-bg b-none" style="text-align: center;">
                SIGNATURE
            </td>
        </tr>

        <tr>
            <td style="padding: 5px; text-align: center;">&nbsp;</td>
            <td class="b-none">
                <table class="w-100">
                    <tr>
                        <td style="font-size: 8px; padding: 8px; width: 210px;">
                            Printed Name of Buyer
                        </td>
                        <td style="border-bottom: solid 1px #111;">
                            {{$data->client->first_name}} {{$data->client->middle_name}} {{$data->client->last_name}} {{$data->client->information->extension ?? ''}}
                            @if ($data->co_buyers)
                                @foreach ($data->co_buyers as $co_buyer) 
                                    / {{$co_buyer['details']['first_name']}} {{$co_buyer['details']['last_name']}}
                                @endforeach
                            @endif
                        </td>
                        <td style="font-size: 8px; padding: 8px; width: 100px; text-align: right;">
                            Signature
                        </td>
                        <td style="border-bottom: solid 1px #111;"></td>
                    </tr>
                    <tr>
                        <td style="font-size: 8px; padding: 8px; width: 210px;">
                            Printed Name of Spouse
                        </td>
                        <td style="border-bottom: solid 1px #111;">
                            {{$data->client->spouse->spouse_first_name ?? ''}} {{$data->client->spouse->spouse_middle_name ?? ''}} {{$data->client->spouse->spouse_last_name ?? ''}} {{$data->client->spouse->spouse_extension ?? ''}}
                        </td>
                        <td style="font-size: 8px; padding: 8px; width: 100px; text-align: right;">
                            Signature
                        </td>
                        <td style="border-bottom: solid 1px #111;"></td>
                    </tr>
                    <tr>
                        <td style="font-size: 8px; padding: 8px; width: 210px;">
                            Printed Name of Property Consultant
                        </td>
                        <td style="border-bottom: solid 1px #111;">
                            {{$data->agent->first_name ?? ''}} {{$data->agent->middle_name ?? ''}} {{$data->agent->last_name ?? ''}}
                        </td>
                        <td style="font-size: 8px; padding: 8px; width: 100px; text-align: right;">
                            Signature
                        </td>
                        <td style="border-bottom: solid 1px #111;"></td>
                    </tr>
                    <tr>
                        <td style="font-size: 8px; padding: 8px; width: 210px;">
                            Printed Name of Sales Manager
                        </td>
                        <td style="border-bottom: solid 1px #111;">
                            {{$data->sales_manager->first_name ?? ''}} {{$data->sales_manager->last_name ?? ''}}
                        </td>
                        <td style="font-size: 8px; padding: 8px; width: 100px; text-align: right;">
                            Signature
                        </td>
                        <td style="border-bottom: solid 1px #111;"></td>
                    </tr>
                    <tr>
                        <td style="font-size: 8px; padding: 8px; width: 210px;">
                            Printed Name of Sales Director
                        </td>
                        <td style="border-bottom: solid 1px #111;">
                            {{$data->sales_director->first_name ?? ''}} {{$data->sales_director->last_name ?? ''}}
                        </td>
                        <td style="font-size: 8px; padding: 8px; width: 100px; text-align: right;">
                            Signature
                        </td>
                        <td style="border-bottom: solid 1px #111;"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
        
<!-- <div class="page-break"></div>  -->



        