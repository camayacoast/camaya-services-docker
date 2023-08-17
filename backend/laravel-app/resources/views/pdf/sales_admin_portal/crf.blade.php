<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style>
.table{
border-collapse: collapse;
}
.table thead tr th {
font-size: 22px;
text-align: left;
letter-spacing: .03em;
font-weight: bold;
margin: 10px 0 5px;
line-height: .75em;
/*padding: 25px 25px;*/
}
.table tbody tr td {
/* border: 1px solid black; */
}
@page {
/* size: 21cm 29.7cm; */
margin: 0mm 0mm 0mm 0mm;
/* change the margins as you want them to be. */
}

.box {
    border: solid 1px #333;
    width: 100%;
    padding: 3px;
}
</style>
</head>
<body>

<table class="table">
    <tr>
        <td>
            <img src="{{public_path('images/CRF-Header.png')}}" align="center" width="100%"/>
        </td>
    </tr>
    <tr>
        <td>
            <table class="table" cellpadding="10" style="margin: 50px; width: 100%;">
                    <tr>
                        <td colspan="2">
                            <strong>Date</strong>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            Last Name, First Name, Middle Name, Extension (Jr. Sr., III)
                            <div class="box">
                                {{$last_name}}, {{$first_name}} {{$middle_name ? ', '.$middle_name : ''}} {{isset($information['extension']) ? ', '.$information['extension'] : ''}}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Contact Number
                            <div class="box">
                                {{$information['contact_number'] ?? ''}}
                            </div>
                        </td>
                        <td>
                            Email Address
                            <div class="box">
                                {{$email}}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            &nbsp;
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            I acknowledge that <span style="text-decoration: underline;">{{$agent['agent_details']['first_name']}} {{$agent['agent_details']['last_name']}}</span>, authorized seller of ESLCC, has given me a comprehensive presentation of Camaya Coast Project.
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            I hereby authorize him/her as my seller of choice for the next 60 days. No other seller should be recognized and/or credited by ESLCC, Should I decide to make a reservation deposit for a property for a period of 60 calendar days from the signing of this Client Registration From.
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            Authorized by:
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            &nbsp;
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <div>_________________________________</div>
                            <div>CLIENT (Printed Name &amp; Signature)</div>
                            <div>Date: </div>
                        </td>
                        <td>
                            <div>_________________________________</div>
                            <div>SELLER (Printed Name &amp; Signature)</div>
                            <div>Date: </div>
                        </td>
                    </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>