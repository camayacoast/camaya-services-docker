<table>
    <thead>
        <tr>
            <th align="center" colspan="56" style="font-weight: bold; font-size: 16pt;">Hotel Occupancy</th>
        </tr>
        <tr>
            <th colspan="56"></th>
        </tr>
        <tr>
            <th align="center" colspan="8" style="font-weight: bold;">Monday</th>
            <th align="center" colspan="8" style="font-weight: bold;">Tuesday</th>
            <th align="center" colspan="8" style="font-weight: bold;">Wednesday</th>
            <th align="center" colspan="8" style="font-weight: bold;">Thursday</th>
            <th align="center" colspan="8" style="font-weight: bold;">Friday</th>
            <th align="center" colspan="8" style="font-weight: bold;">Saturday</th>
            <th align="center" colspan="8" style="font-weight: bold;">Sunday</th>
        </tr>
    </thead>
    <tbody>
        @php
            $splited_reports = array_chunk($reports, 7);

            $blank = function() {
                return '
                    <td align="center" colspan="8" rowspan="6"></td>
                ';
            };

            $date_number = function($date_number) {
                return '
                    <td align="center" colspan="8" style="font-weight: bold;"> ' . $date_number . ' </td>
                ';
            };

            $title = function() {
                return '
                    <td align="center" style="font-weight: bold;"></td>
                    <td align="center" style="font-weight: bold;">BPO</td>
                    <td align="center" style="font-weight: bold;">RE</td>
                    <td align="center" style="font-weight: bold;">HOA</td>
                    <td align="center" style="font-weight: bold;">FOC</td>
                    <td align="center" style="font-weight: bold;">Walk-in</td>
                    <td align="center" style="font-weight: bold;">Occupancy</td>
                    <td align="center" style="font-weight: bold;">%</td>
                ';
            };

            $deluxe_twin = function($report) {
                return '
                    <td align="center">Deluxe Twin (' . $report["deluxe_twin_room_total"] . ')</td>
                    <td align="center">' . $report["deluxe_twin_bpo"] . '</td>
                    <td align="center">' . $report["deluxe_twin_re"] . '</td>
                    <td align="center">' . $report["deluxe_twin_hoa"] . '</td>
                    <td align="center">' . $report["deluxe_twin_foc"] . '</td>
                    <td align="center">' . $report["deluxe_twin_walk_in"] . '</td>
                    <td align="center">' . $report["deluxe_twin_occupancy"] . '</td>
                    <td align="center">' . $report["deluxe_twin_percent"] . '</td>
                ';
            };

            $family_suite = function($report) {
                return '
                    <td align="center">Family Suite (' . $report["family_suite_room_total"] . ')</td>
                    <td align="center">' . $report["family_suite_bpo"] . '</td>
                    <td align="center">' . $report["family_suite_re"] . '</td>
                    <td align="center">' . $report["family_suite_hoa"] . '</td>
                    <td align="center">' . $report["family_suite_foc"] . '</td>
                    <td align="center">' . $report["family_suite_walk_in"] . '</td>
                    <td align="center">' . $report["family_suite_occupancy"] . '</td>
                    <td align="center">' . $report["family_suite_percent"] . '</td>
                ';
            };

            $deluxe_queen = function($report) {
                return '
                    <td align="center">Deluxe Queen (' . $report["deluxe_queen_room_total"] . ')</td>
                    <td align="center">' . $report["deluxe_queen_bpo"] . '</td>
                    <td align="center">' . $report["deluxe_queen_re"] . '</td>
                    <td align="center">' . $report["deluxe_queen_hoa"] . '</td>
                    <td align="center">' . $report["deluxe_queen_foc"] . '</td>
                    <td align="center">' . $report["deluxe_queen_walk_in"] . '</td>
                    <td align="center">' . $report["deluxe_queen_occupancy"] . '</td>
                    <td align="center">' . $report["deluxe_queen_percent"] . '</td>
                ';
            };

            $executive_suite = function($report) {
                return '
                    <td align="center">Executive Suite (' . $report["executive_suite_room_total"] . ')</td>
                    <td align="center">' . $report["executive_suite_bpo"] . '</td>
                    <td align="center">' . $report["executive_suite_re"] . '</td>
                    <td align="center">' . $report["executive_suite_hoa"] . '</td>
                    <td align="center">' . $report["executive_suite_foc"] . '</td>
                    <td align="center">' . $report["executive_suite_walk_in"] . '</td>
                    <td align="center">' . $report["executive_suite_occupancy"] . '</td>
                    <td align="center">' . $report["executive_suite_percent"] . '</td>
                ';
            };

            $total = function($report) {
                return '
                    <td align="center">Total: ' . $report["total_rooms"] . ' rooms</td>
                    <td align="center">' . $report["total_bpo"] . '</td>
                    <td align="center">' . $report["total_re"] . '</td>
                    <td align="center">' . $report["total_hoa"] . '</td>
                    <td align="center">' . $report["total_foc"] . '</td>
                    <td align="center">' . $report["total_walk_in"] . '</td>
                    <td align="center">' . $report["total_occupancy"] . '</td>
                    <td align="center">' . $report["total_percent"] . '</td>
                ';
            };
        @endphp

        @foreach($splited_reports as $reports)
            <tr>
                @foreach($reports as $report)
                    {!! $date_number($report['date_number']) !!}
                @endforeach
            </tr>

            <tr>
                @foreach($reports as $report)
                    @if (array_key_exists('blank', $report))
                        {!! $blank() !!}
                    @else
                        {!! $title() !!}
                    @endif
                @endforeach
            </tr>

            <tr>
                @foreach($reports as $report)
                    @if (!array_key_exists('blank', $report))
                        {!! $deluxe_twin($report) !!}
                    @endif
                @endforeach
            </tr>

            <tr>
                @foreach($reports as $report)
                    @if (!array_key_exists('blank', $report))
                        {!! $family_suite($report) !!}
                    @endif
                @endforeach
            </tr>

            <tr>
                @foreach($reports as $report)
                    @if (!array_key_exists('blank', $report))
                        {!! $deluxe_queen($report) !!}
                    @endif
                @endforeach
            </tr>

            <tr>
                @foreach($reports as $report)
                    @if (!array_key_exists('blank', $report))
                        {!! $executive_suite($report) !!}
                    @endif
                @endforeach
            </tr>

            <tr>
                @foreach($reports as $report)
                    @if (!array_key_exists('blank', $report))
                        {!! $total($report) !!}
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>