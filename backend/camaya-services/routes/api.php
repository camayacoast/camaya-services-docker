<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RealEstate\Promos;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->ge t('/user', function (Request $request) {
//     return $request->user();
// });

// Payment
Route::post('/golf-payments/paymaya', 'GolfMembership\OnlinePayment')->middleware("auth:sanctum");
Route::post('/payment/{provider}', 'PaymentController@initiatePayment')->middleware("auth:sanctum");

Route::post('/login', 'LoginController@authenticate');

Route::post('/register', 'RegisterController@register')->name('register');
Route::post('/save-upload', 'RegisterController@saveUpload');

Route::get('/user', 'LoginController@getAuthenticatedUser')->middleware("auth:sanctum");
// Route::get('/get-user-by-token', 'LoginController@getUserByToken')->middleware("auth:sanctum");

Route::post('/password/reset', 'ResetPasswordController@resetPassword');
Route::post('/password/change', 'ResetPasswordController@changePassword');

// Route::post('/payment/golf-paypal', 'PaymentController@initiatePayment')->middleware("auth:sanctum");

Route::post('/bank-payment', 'PaymentController@bankPayment')->middleware("auth:sanctum");

Route::get('/golf-payments', 'PaymentController@golfPayments')->middleware("auth:sanctum");
Route::post('/golf-payments/mark-as-paid', 'PaymentController@golfPaymentsMarkAsPaid')->middleware("auth:sanctum");
// Route::post('/payment/golf-paypal', 'PaymentController@initiatePayment')->middleware("auth:sanctum");


Route::get('/payments', 'PaymentController@list')->middleware("auth:sanctum");
Route::get('/payment/{id}', 'PaymentController@item')->middleware("auth:sanctum");

// Viber
Route::post('/viberbot/webhook', 'ViberBotController@webhook');

/**
 * Admin Routes
 */

Route::prefix('admin')->group(function () {
  Route::post('/create', 'Admin\Create');

  Route::get('/users', 'Admin\UserList')->middleware("auth:sanctum");
  Route::get('/all-agent-users', 'Admin\AllAgentList')->middleware("auth:sanctum");

  Route::get('/permissions', 'Admin\Permissions')->middleware("auth:sanctum");
  Route::get('/permissions-by-role/{role}', 'Admin\PermissionsByRole')->middleware("auth:sanctum");
  Route::post('/change-role-permissions', 'Admin\ChangeRolePermissions')->middleware("auth:sanctum");
  Route::post('/permission/create', 'Admin\CreatePermission')->middleware("auth:sanctum");

  Route::get('/roles', 'Admin\RoleList')->middleware("auth:sanctum");
  Route::post('/role/create', 'Admin\CreateRole')->middleware("auth:sanctum");

  Route::put('/user/update-user-type', 'Admin\UpdateUserType')->middleware("auth:sanctum");
  Route::put('/user/update-user-role', 'Admin\UpdateUserRole')->middleware("auth:sanctum");
  Route::put('/user/update-user-first-name', 'Admin\UpdateUserFirstName')->middleware("auth:sanctum");
  Route::put('/user/update-user-last-name', 'Admin\UpdateUserLastName')->middleware("auth:sanctum");

  Route::put('/user/change-password', 'Admin\ChangePassword')->middleware("auth:sanctum");

  Route::post('/user/reset-password', 'Admin\ResetPassword')->middleware("auth:sanctum");

});

Route::prefix('booking')->group(function () {
  //Bookings
  Route::post('create', 'Booking\Create')->middleware("auth:sanctum");
  Route::get('list/{type}', 'Booking\BookingList')->middleware("auth:sanctum");
  Route::get('my-bookings', 'Booking\MyBookings')->middleware("auth:sanctum");
  Route::get('view-booking/{refno}', 'Booking\ViewBooking')->middleware("auth:sanctum");

  Route::patch('cancel-booking', 'Booking\CancelBooking')->middleware("auth:sanctum");
  Route::patch('pending-booking', 'Booking\PendingBooking')->middleware("auth:sanctum");
  Route::patch('confirm-booking', 'Booking\ConfirmBooking')->middleware("auth:sanctum");

  Route::post('search-bookings', 'Booking\SearchBookings')->middleware("auth:sanctum");

  Route::post('add-attachment', 'Booking\AddAttachment')->middleware("auth:sanctum");

  Route::get('guests', 'Booking\GuestList')->middleware("auth:sanctum");
  Route::put('guests/update', 'Booking\UpdateGuest')->middleware("auth:sanctum");

  Route::put('guest_vehicles/add', 'Booking\AddVehicle')->middleware("auth:sanctum");
  Route::put('guest_vehicles/update', 'Booking\UpdateVehicle')->middleware("auth:sanctum");
  Route::put('guest_vehicles/delete', 'Booking\DeleteVehicle')->middleware("auth:sanctum");

  Route::put('update/label', 'Booking\UpdateBookingLabel')->middleware("auth:sanctum");
  Route::put('update/remarks', 'Booking\UpdateRemarks')->middleware("auth:sanctum");
  Route::put('update/billing-instructions', 'Booking\UpdateBillingInstructions')->middleware("auth:sanctum");

  Route::get('hotel-guests', 'Booking\HotelGuestList')->middleware("auth:sanctum");

  Route::post('guest/add-guest-pass', 'Booking\AddGuestPass')->middleware("auth:sanctum");

  // TRIPPPING
  Route::get('list/tripping/{type}', 'Booking\TrippingList')->middleware("auth:sanctum");

  // Invoice
  Route::get('invoices/{booking_reference_number}', 'Booking\InvoiceList')->middleware("auth:sanctum");
  Route::post('invoice/new-payment', 'Booking\NewPayment')->middleware("auth:sanctum");

  // Guest Booking
  Route::post('create-as-guest', 'Booking\CreateBookingAsGuest');

  /**
   * Guests
   */
  Route::put('guest/update/status', 'Booking\UpdateGuestStatus')->middleware("auth:sanctum");
  Route::put('guest/delete', 'Booking\DeleteGuest')->middleware("auth:sanctum");

  // Customers
  Route::post('customer/create', 'Booking\CreateCustomer')->middleware("auth:sanctum");
  Route::get('customers', 'Booking\CustomerList')->middleware("auth:sanctum");
  Route::post('customers-v2', 'Booking\CustomerList2')->middleware("auth:sanctum");

  Route::put('customer/link-to-user', 'Booking\LinkCustomerToUser')->middleware("auth:sanctum");

  // Products
  Route::post('product/create', 'Booking\CreateProduct')->middleware("auth:sanctum");
  Route::put('product/update', 'Booking\UpdateProduct')->middleware("auth:sanctum");
  Route::post('product/add-image', 'Booking\AddProductImage')->middleware("auth:sanctum");
  Route::delete('product/image/{id}', 'Booking\RemoveProductImage')->middleware("auth:sanctum");
  Route::get('products', 'Booking\ProductList')->middleware("auth:sanctum");
  // Website
  Route::get('public/products', 'Booking\WebsiteProductList');

  // Packages
  Route::get('packages', 'Booking\PackageList')->middleware("auth:sanctum");
  Route::post('package/create', 'Booking\CreatePackage')->middleware("auth:sanctum");
  Route::put('package/update', 'Booking\UpdatePackage')->middleware("auth:sanctum");
  Route::post('package/add-image', 'Booking\AddPackageImage')->middleware("auth:sanctum");
  Route::delete('package/image/{id}', 'Booking\RemovePackageImage')->middleware("auth:sanctum");
  // Website
  Route::get('public/packages', 'Booking\WebsitePackageList');
  Route::get('public/packages_days', 'Booking\WebsitePackageDays');

  Route::post('public/packages_list', 'Booking\WebsitePackageList');
  Route::post('packages_list', 'Booking\PackageList')->middleware("auth:sanctum");


  // Booking as Logged in user CreateBookingAsLoggedInUser
  Route::post('create-as-logged-in-user', 'Booking\CreateBookingAsLoggedInUser')->middleware("auth:sanctum");

  //Payment
  Route::get('public/payment/{provider}/request/{booking_reference_number}', 'Booking\PaymentRequest');
  Route::get('public/payment/{provider}/partial-request/{booking_reference_number}/{amount}', 'Booking\PartialPaymentRequest');
  Route::get('public/payment/{provider}/success/{booking_reference_number}/{payment_reference_number}', 'Booking\PaymentSuccess');
  Route::get('public/payment/{provider}/cancel/{payment_reference_number}', 'Booking\PaymentCancel');
  Route::get('public/payment/{provider}/failed/{booking_reference_number}', 'Booking\PaymentFailed@index');

  //Voucher Payment
  Route::get('public/voucher-payment/{provider}/request/{transaction_reference_number}', 'Booking\VoucherPaymentRequest');
  Route::get('public/voucher-payment/{provider}/success/{transaction_reference_number}', 'Booking\VoucherPaymentSuccess');
  Route::get('public/voucher-payment/{provider}/cancel/{transaction_reference_number}', 'Booking\VoucherPaymentCancel');
  Route::get('public/voucher-payment/{provider}/failed/{transaction_reference_number}', 'Booking\VoucherPaymentFailed@index');

  Route::get('check/{booking_reference_number}', 'Booking\CheckBookingExists');

  // Image upload
  Route::post('product/image-upload', 'Booking\ImageUpload')->middleware("auth:sanctum");
  Route::post('product/image-upload-remove', 'Booking\ImageUploadRemove')->middleware("auth:sanctum");

  // Resend booking confirmation
  Route::post('resend-booking-confirmation', 'Booking\ResendBookingConfirmation')->middleware("auth:sanctum");

  // Get all the booking tags
  Route::get('all-booking-tags', 'Booking\GetAllBookingTags')->middleware("auth:sanctum");

  // Dashboard data and reports
  Route::get('dashboard', 'Booking\DashboardData')->middleware("auth:sanctum");
  Route::get('concierge-dashboard', 'Concierge\Dashboard')->middleware("auth:sanctum");
  Route::get('arrival-forecast-report', 'Booking\ArrivalForecastReport')->middleware("auth:sanctum");
  Route::get('guest-arrival-status-report', 'Booking\GuestArrivalStatusReport')->middleware("auth:sanctum");
  Route::get('dtt-report', 'Booking\DTTReport')->middleware("auth:sanctum");
  Route::get('dtt-revenue-report', 'Booking\DttRevenueReport')->middleware("auth:sanctum");

  // Booking Settings
  Route::put('update-daily-limit', 'Booking\UpdateDailyLimit')->middleware("auth:sanctum");
  Route::put('update-ferry-passengers-limit', 'Booking\UpdateFerryPassengersLimit')->middleware("auth:sanctum");

  // Stub
  Route::get('stubs', 'Booking\StubList')->middleware("auth:sanctum");
  Route::post('stub/create', 'Booking\CreateStub')->middleware("auth:sanctum");
  Route::put('stub/update/category', 'Booking\UpdateStubCategory')->middleware("auth:sanctum");

  // Land Allocation
  Route::get('land-allocations', 'Booking\LandAllocation')->middleware("auth:sanctum");
  Route::post('land-allocation/create', 'Booking\CreateLandAllocation')->middleware("auth:sanctum");
  Route::put('land-allocation/update-status', 'Booking\UpdateLandAllocationStatus')->middleware("auth:sanctum");
  Route::put('land-allocation/update/allowed-users', 'Booking\UpdateLandAllocationAllowedUsers')->middleware("auth:sanctum");
  Route::put('land-allocation/update/allowed-roles', 'Booking\UpdateLandAllocationAllowedRoles')->middleware("auth:sanctum");
  Route::put('land-allocation/update/allocation', 'Booking\UpdateLandAllocation')->middleware("auth:sanctum");

  // Vouchers
  Route::get('vouchers', 'Booking\VoucherList')->middleware("auth:sanctum");
  Route::post('voucher/create', 'Booking\CreateVoucher')->middleware("auth:sanctum");
  // Image upload
  Route::post('voucher/image-upload', 'Booking\VoucherImageUpload')->middleware("auth:sanctum");
  Route::post('voucher/image-upload-remove', 'Booking\VoucherImageUploadRemove')->middleware("auth:sanctum");
  Route::post('voucher/add-image', 'Booking\AddVoucherImage')->middleware("auth:sanctum");
  // Generted Vouchers
  Route::get('voucher/generated-vouchers', 'Booking\GeneratedVouchersList')->middleware("auth:sanctum");
  Route::post('voucher/generate', 'Booking\GenerateNewVoucher')->middleware("auth:sanctum");
  Route::post('voucher/change-status', 'Booking\ChangeVoucherStatus')->middleware("auth:sanctum");
  Route::post('voucher/change-payment-status', 'Booking\ChangeVoucherPaymentStatus')->middleware("auth:sanctum");

  Route::post('voucher/change-paid-at', 'Booking\VoucherChangePaidAt')->middleware("auth:sanctum");
  Route::post('voucher/change-mode-of-payment', 'Booking\VoucherChangeModeOfPayment')->middleware("auth:sanctum");

  Route::post('voucher/update-voucher-stub', 'Booking\UpdateVoucherStub')->middleware("auth:sanctum");

  Route::post('voucher/buy', 'Booking\BuyVoucher');
  Route::get('website-vouchers', 'Booking\WebsiteVouchersList');

  // Resend Voucher confirmation
  Route::post('resend-voucher-confirmation', 'Booking\ResendVoucherConfirmation')->middleware("auth:sanctum");

  // PDF
  Route::post('/download-boarding-pass-one-pdf', 'Booking\PrintBoardingPassOnePDF')->middleware("auth:sanctum");
  Route::post('/download-booking-confirmation', 'Booking\PrintBookingConfirmation')->middleware("auth:sanctum");

  // Settings
  Route::get('settings', 'Booking\SettingsList')->middleware("auth:sanctum");
  Route::post('setting/create', 'Booking\CreateSetting')->middleware("auth:sanctum");

  // Agent website list
  Route::get('website-agents', 'Booking\WebsiteAgentList')->middleware("auth:sanctum");

  Route::put('passes/update/usable-at', 'Booking\UpdatePassesUsableAt')->middleware("auth:sanctum");
  Route::put('passes/update/expires-at', 'Booking\UpdatePassesExpiresAt')->middleware("auth:sanctum");

  Route::put('invoice/update/discount', 'Booking\UpdateInvoiceDiscount')->middleware("auth:sanctum");
  Route::put('invoice/update/inclusion-discount', 'Booking\UpdateInclusionDiscount')->middleware("auth:sanctum");

  Route::post('note/new', 'Booking\NewNote')->middleware("auth:sanctum");

  Route::post('add-guest', 'Booking\AddGuest')->middleware("auth:sanctum");

  Route::put('customer/update/address', 'Booking\UpdateCustomerAddress')->middleware("auth:sanctum");
  Route::put('customer/update', 'Booking\UpdateCustomer')->middleware("auth:sanctum");

  Route::post('add-inclusions-to-booking', 'Booking\AddInclusionsToBooking')->middleware("auth:sanctum");
  Route::post('remove-inclusion', 'Booking\RemoveInclusion')->middleware("auth:sanctum");

  Route::put('update/additional-emails', 'Booking\UpdateAdditionalEmails')->middleware("auth:sanctum");

  Route::put('update/tags', 'Booking\UpdateBookingTags')->middleware("auth:sanctum");
  Route::put('update/booking-date', 'Booking\UpdateBookingDate')->middleware("auth:sanctum");

  /**
   * Change password
   */
  Route::put('user/change-password', 'Booking\WebsiteUserChangePassword')->middleware("auth:sanctum");

  Route::put('invoice/void-payment', 'Booking\VoidInvoicePayment')->middleware("auth:sanctum");

  // Update primary guest
  Route::put('update/primary-guest', 'Booking\UpdatePrimaryGuest')->middleware("auth:sanctum");

  // Update auto-cancel-date
  Route::put('update/auto-cancel-date', 'Booking\UpdateAutoCancelDate')->middleware("auth:sanctum");
  Route::get('/auto-cancel-bookings', 'Booking\AutoCancelBooking');

  Route::post('/add-ferry-to-booking', 'Booking\AddFerryToBooking')->middleware("auth:sanctum");
  Route::post('/add-ferry-to-guests', 'Booking\AddFerryToGuests')->middleware("auth:sanctum");

  // Booking logs
  Route::get('logs', 'Booking\GetLogs')->middleware("auth:sanctum");

  // Reports
  Route::post('/report/bookings-with-inclusions', 'Booking\BookingsWithInclusionReport')->middleware("auth:sanctum");
  Route::post('/report/arrival-forecast', 'Booking\ArrivalForecastReport')->middleware("auth:sanctum");
  Route::post('/report/guest-arrival-status', 'Booking\GuestArrivalStatusReport')->middleware("auth:sanctum");
  Route::post('/report/dtt', 'Booking\DTTReport')->middleware("auth:sanctum");
  Route::post('/report/dtt-revenue', 'Booking\DttRevenueReport')->middleware("auth:sanctum");
  Route::get('/reports/daily-booking-per-sd/{start_date?}/{end_date?}/{download?}', 'Booking\Reports\DailyBookingPerSD')->middleware("auth:sanctum");
  Route::get('/reports/commercial-sales/{start_date?}/{end_date?}/{download?}', 'Booking\Reports\CommercialSales')->middleware("auth:sanctum");

  Route::post('/report/arrival-forecast-per-segment', 'Booking\Reports\ArrivalForecastPerSegment')->middleware("auth:sanctum");
  Route::post('/report/download-arrival-forecast-per-segment', 'Booking\Reports\DownloadArrivalForecastPerSegment')->middleware("auth:sanctum");

  Route::post('/report/revenue-report', 'Booking\Reports\RevenueReport')->middleware("auth:sanctum");
  Route::get('/reports/revenue-report/{start_date?}/{end_date?}/{download?}', 'Booking\Reports\RevenueReportDownload')->middleware("auth:sanctum");

  // SDMB Reports
  Route::post('/report/sdmb-golf-cart-consumption', 'Booking\Reports\SDMBGolfCartConsumption')->middleware("auth:sanctum");
  Route::post('/report/sdmb-golf-play-consumption', 'Booking\Reports\SDMBGolfPlayConsumption')->middleware("auth:sanctum");

  Route::get('/reports/sdmb-booking-consumption/{start_date?}/{end_date?}/{download?}', 'Booking\Reports\SDMBBookingConsumption')->middleware("auth:sanctum");
  Route::get('/reports/sdmb-sales-room/{start_date?}/{end_date?}/{download?}', 'Booking\Reports\SDMBSalesRoom')->middleware("auth:sanctum");

  Route::get('/agent-list', 'Booking\AgentListForNewBooking')->middleware("auth:sanctum");

  Route::post('/corregidor-bookings-per-date', 'Booking\GetCorregidorBookingsPerDate')->middleware("auth:sanctum");

  // Daily Guest Limit
  Route::post('/generate-daily-guest-per-day', 'Booking\GenerateDailyGuestPerDay')->middleware("auth:sanctum");
  Route::get('/get-daily-guest-limit-per-month-year', 'Booking\GetDailyGuestPerDayMonthYear')->middleware("auth:sanctum");
  Route::post('/update-daily-guest-per-day', 'Booking\UpdateDailyGuestPerDay')->middleware("auth:sanctum");
  Route::post('/update-remarks', 'Booking\UpdateDailyGuestLimitNote')->middleware("auth:sanctum");


});

Route::prefix('hotel')->group(function () {
   // Property
   Route::post('property/create', 'Hotel\CreateProperty')->middleware("auth:sanctum");
   Route::post('property/update', 'Hotel\UpdateProperty')->middleware("auth:sanctum");
   Route::post('property/add-image', 'Hotel\AddPropertyImage')->middleware("auth:sanctum");
   Route::delete('property/image/{id}', 'Hotel\RemovePropertyImage')->middleware("auth:sanctum");
   Route::get('properties', 'Hotel\PropertyList')->middleware("auth:sanctum");

   Route::post('room/create', 'Hotel\CreateRoom')->middleware("auth:sanctum");
   Route::get('rooms', 'Hotel\RoomList')->middleware("auth:sanctum");
   Route::put('room/update-status', 'Hotel\UpdateRoomStatus')->middleware("auth:sanctum");
   Route::post('room/update', 'Hotel\UpdateRoom')->middleware("auth:sanctum");

   Route::get('room-types', 'Hotel\RoomTypeList')->middleware("auth:sanctum");
   Route::get('room-types-only', 'Hotel\RoomTypeOnlyList')->middleware("auth:sanctum");
   Route::put('room-type/update-status', 'Hotel\UpdateRoomTypeStatus')->middleware("auth:sanctum");

  //  Route::get('room-types-with-dates/{arrival}/{departure}', 'Hotel\RoomTypeListWithAvailability')->middleware("auth:sanctum");
   Route::get('room-types-with-dates/{arrival}/{departure}', 'Hotel\RoomAllocationForBooking')->middleware("auth:sanctum");

   Route::get('rooms-types-per-entity', 'Hotel\RoomTypesPerEntityList');

   Route::get('public/room-types', 'Hotel\WebsiteRoomTypeList');
   Route::get('public/room-types-with-dates/{arrival}/{departure}', 'Hotel\WebsiteRoomTypeListWithAvailability');

   Route::get('room-reservations/{start_date}/{end_date}', 'Hotel\RoomReservationList')->middleware("auth:sanctum");

   Route::get('room-rates', 'Hotel\RoomRateList')->middleware("auth:sanctum");
   Route::post('room-rate/create', 'Hotel\CreateRoomRate')->middleware("auth:sanctum");
   Route::post('room-rate/update', 'Hotel\UpdateRoomRate')->middleware("auth:sanctum");
   Route::put('room-rate/update-status', 'Hotel\UpdateRoomRateStatus')->middleware("auth:sanctum");
   Route::put('room-rate/update-allowed-days', 'Hotel\UpdateRoomRateAllowedDays')->middleware("auth:sanctum");
   Route::put('room-rate/update-excluded-days', 'Hotel\UpdateRoomRateExcludedDays')->middleware("auth:sanctum");

   Route::post('room-allocation/create', 'Hotel\CreateRoomAllocation')->middleware("auth:sanctum");

   Route::get('room-allocations-owner/{id}', 'Booking\GetRoomAllocationByOwner')->middleware("auth:sanctum");

   Route::get('room-allocations', 'Hotel\RoomAllocationList')->middleware("auth:sanctum");

   Route::get('room-allocation-for-booking/{arrival}/{departure}', 'Hotel\WebsiteRoomAllocationForBooking');

   Route::get('agent-portal-room-allocation-for-booking/{arrival}/{departure}', 'Booking\AgentPortalRoomAllocationForBooking')->middleware("auth:sanctum");

   Route::put('room-allocation/update-status', 'Hotel\UpdateRoomAllocationStatus')->middleware("auth:sanctum");

   Route::put('room-allocation/update/allowed-roles', 'Hotel\UpdateRoomAllocationAllowedRoles')->middleware("auth:sanctum");

   Route::put('room-allocation/update/allocation', 'Hotel\UpdateRoomAllocation')->middleware("auth:sanctum");

   // Dashboard data
   Route::get('room-reservation/dashboard', 'Hotel\RoomReservationDashboard');

   // Update Actual room status
   Route::put('room/update/room-status', 'Hotel\UpdateHotelRoomStatus')->middleware("auth:sanctum");

   Route::put('room-reservation/update/status', 'Hotel\UpdateRoomReservationStatus')->middleware("auth:sanctum");

   Route::put('room-reservation/switch-room', 'Hotel\SwitchRoom')->middleware("auth:sanctum");

   Route::post('room-reservation/room-blocking', 'Hotel\RoomBlocking')->middleware("auth:sanctum");
   Route::post('room-reservation/cancel-room-blocking', 'Hotel\CancelRoomBlocking')->middleware("auth:sanctum");

   Route::post('room-reservation/get-last-available-reservation-date', 'Hotel\GetLastAvailableReservationDate')->middleware("auth:sanctum");

   Route::get('room-allocation-per-date', 'Hotel\RoomAllocationPerDate')->middleware("auth:sanctum");

   Route::get('room-reservation/available-room-list', 'Hotel\getAvailableRoomList')->middleware("auth:sanctum");

   Route::post('room-reservation/add-room-to-booking', 'Hotel\AddRoomToBooking')->middleware("auth:sanctum");

   Route::post('room-reservation/get-available-rooms-for-date', 'Hotel\AvailableRoomsForDate')->middleware("auth:sanctum");
   Route::post('room-reservation/room-transfer', 'Hotel\RoomTransfer')->middleware("auth:sanctum");

   Route::post('room-type-available-per-dates', 'Hotel\GetAvailableRoomTypePerDates')->middleware("auth:sanctum");

   Route::put('update-check-in-time', 'Hotel\UpdateCheckInTime')->middleware("auth:sanctum");

   // reports
   Route::get('reports/daily-arrival/{date}', 'Hotel\Reports\DailyArrival')->middleware("auth:sanctum");
   Route::get('reports/daily-arrival/{date}/download', 'Hotel\Reports\DailyArrivalDownload')->middleware("auth:sanctum");

   Route::get('reports/daily-departure/{date}', 'Hotel\Reports\DailyDeparture')->middleware("auth:sanctum");
   Route::get('reports/daily-departure/{date}/download', 'Hotel\Reports\DailyDepartureDownload')->middleware("auth:sanctum");

   Route::get('reports/in-house-guest-list/{date}', 'Hotel\Reports\InHouseGuestList')->middleware("auth:sanctum");
   Route::get('reports/in-house-guest-list/{date}/download', 'Hotel\Reports\InHouseGuestListDownload')->middleware("auth:sanctum");

   Route::get('reports/stay-over-guest-list/{date}', 'Hotel\Reports\StayOverGuestList')->middleware("auth:sanctum");
   Route::get('reports/stay-over-guest-list/{date}/download', 'Hotel\Reports\StayOverGuestListDownload')->middleware("auth:sanctum");

   Route::get('reports/dtt-arrival-forecast', 'Hotel\Reports\DTTArrivalForecast')->middleware("auth:sanctum");
   Route::get('reports/dtt-daily-arrival', 'Hotel\Reports\DTTDailyArrival')->middleware("auth:sanctum");
   Route::get('reports/guest-history/{date}', 'Hotel\Reports\GuestHistory')->middleware("auth:sanctum");
   Route::get('reports/guest-history/{date}/download', 'Hotel\Reports\GuestHistoryDownload')->middleware("auth:sanctum");
   Route::get('reports/hotel-occupancy/{start_date?}/{end_date?}/{download?}', 'Hotel\Reports\HotelOccupancy')->middleware("auth:sanctum");

   Route::get('occupancy-dashboard/{month}/{year}', 'Hotel\OccupancyDashboard')->middleware("auth:sanctum");

   Route::get('room-reservation-calendar/{start_date}/{end_date}', 'Hotel\RoomReservationCalendar')->middleware("auth:sanctum");
});

Route::prefix('transportation')->group(function () {
  // Transportation
  Route::post('create', 'Transportation\CreateTransportation')->middleware("auth:sanctum");
  Route::get('transportations', 'Transportation\TransportationList')->middleware("auth:sanctum");

  // Seat
  Route::post('seat/create', 'Transportation\CreateSeat')->middleware("auth:sanctum");

  // Location
  Route::post('location/create', 'Transportation\CreateLocation')->middleware("auth:sanctum");
  Route::get('locations', 'Transportation\LocationList')->middleware("auth:sanctum");

  // Route
  Route::post('route/create', 'Transportation\CreateRoute')->middleware("auth:sanctum");
  Route::get('routes', 'Transportation\RouteList')->middleware("auth:sanctum");

  Route::get('schedules', 'Transportation\ScheduleList')->middleware("auth:sanctum");
  Route::post('generate-schedules', 'Transportation\GenerateSchedules')->middleware("auth:sanctum");
  Route::put('update-schedule', 'Transportation\UpdateSchedule')->middleware("auth:sanctum");

  Route::put('schedule/update/seat-allocation-allowed-roles', 'Transportation\UpdateSeatAllocationAllowedRoles')->middleware("auth:sanctum");
  Route::put('schedule/update/seat-segment-booking-types', 'Transportation\UpdateSeatSegmentBookingTypes')->middleware("auth:sanctum");

  Route::put('schedule/update/seat-segment-allowed-roles', 'Transportation\UpdateSeatSegmentAllowedRoles')->middleware("auth:sanctum");
  Route::put('schedule/update/seat-segment-allowed-users', 'Transportation\UpdateSeatSegmentAllowedUsers')->middleware("auth:sanctum");
  Route::put('schedule/update/seat-segment-status', 'Transportation\UpdateSeatSegmentStatus')->middleware("auth:sanctum");
  Route::put('schedule/update/seat-segment-link', 'Transportation\UpdateSeatSegmentLink')->middleware("auth:sanctum");

  Route::put('schedule/update/status', 'Transportation\UpdateScheduleStatus')->middleware("auth:sanctum");

  Route::post('seat-segment/create', 'Transportation\AddSeatSegment')->middleware("auth:sanctum");
  Route::post('seat-allocation/create', 'Transportation\AddSeatAllocation')->middleware("auth:sanctum");

  Route::put('seat-allocation/update/quantity', 'Transportation\UpdateSeatAllocationQuantity')->middleware("auth:sanctum");
  Route::put('seat-segment/update/allocated', 'Transportation\UpdateSeatSegmentAllocated')->middleware("auth:sanctum");
  Route::put('seat-segment/update/rate', 'Transportation\UpdateSeatSegmentRate')->middleware("auth:sanctum");

  Route::post('schedules-by-date', 'Transportation\GetAvailableCamayaTransportationSchedules')->middleware("auth:sanctum");

  Route::post('schedule/print-manifest', 'Transportation\PrintScheduleManifest')->middleware("auth:sanctum");

  Route::get('schedule/passengers', 'Transportation\PassengerList')->middleware("auth:sanctum");

  Route::put('schedule/update/passenger-status', 'Transportation\UpdatePassengerStatus')->middleware("auth:sanctum");
  Route::post('schedule/passenger-list-by-schedule-id', 'Transportation\PassengerListByScheduleId')->middleware("auth:sanctum");

  Route::post('schedule/available-trips-by-booking-date', 'Transportation\AvailableTripsByBookingDate')->middleware("auth:sanctum");

  Route::put('seat/update/order', 'Transportation\UpdateSeatOrder')->middleware("auth:sanctum");

  Route::put('seat/update/auto-check-in-status', 'Transportation\UpdateSeatAutoCheckInStatus')->middleware("auth:sanctum");
  Route::put('seat/update/status', 'Transportation\UpdateSeatStatus')->middleware("auth:sanctum");


  // Reports
  Route::get('reports/ferry-passengers-manifesto/{start_date?}/{end_date?}', 'Transportation\Reports\FerryPassengersManifesto')->middleware("auth:sanctum");
  Route::post('reports/ferry-passengers-manifesto/download', 'Transportation\Reports\DownloadFerryPassengersManifesto')->middleware("auth:sanctum");
  Route::get('reports/ferry-seats-per-sd/{start_date?}/{end_date?}/{download?}', 'Transportation\Reports\FerrySeatsPerSD')->middleware("auth:sanctum");

  Route::get('reports/ferry-passengers-manifesto-bpo/{start_date?}/{end_date?}', 'Transportation\Reports\FerryPassengersManifestoBPO')->middleware("auth:sanctum");
  Route::post('reports/ferry-passengers-manifesto-bpo/download', 'Transportation\Reports\DownloadFerryPassengersManifestoBPO')->middleware("auth:sanctum");

  Route::get('reports/ferry-passengers-manifesto-concierge/{start_date?}/{end_date?}', 'Transportation\Reports\FerryPassengersManifestoConcierge')->middleware("auth:sanctum");
  Route::post('reports/ferry-passengers-manifesto-concierge/download', 'Transportation\Reports\DownloadFerryPassengersManifestoConcierge')->middleware("auth:sanctum");
});

  //Golf Tee Time Schedule
Route::prefix('golf')->group(function () {

  Route::post('tee-time/create', 'Golf\CreateTeeTimeSchedule')->middleware("auth:sanctum");
  Route::get('tee-time-schedules', 'Golf\TeeTimeScheduleList')->middleware("auth:sanctum");

  Route::get('arrival-summary', 'Golf\ArrivalSummary')->middleware("auth:sanctum");

  Route::put('tee-time/status-change', 'Golf\TeeTimeStatusChange')->middleware("auth:sanctum");;

  Route::post('website-tee-time-schedules', 'Golf\WebsiteTeeTimeSchedules');

  Route::put('tee-time/allocation-update', 'Golf\TeeTimeAllocationUpdate')->middleware("auth:sanctum");

});

Route::prefix('golf-admin-portal')->group(function () {
    Route::get('/payments', 'GolfMembership\PaymentList')->middleware("auth:sanctum");
    Route::post('/update-payment-record', 'GolfMembership\UpdatePaymentRecord')->middleware("auth:sanctum");
    Route::post('/save-payment-transaction-source', 'GolfMembership\SavePaymentTransactionSource')->middleware("auth:sanctum");
});


Route::prefix('auto-gate')->group(function () {
  //// SCAN AND CHECK
  Route::put('scan-and-check', 'AutoGate\ScanAndCheck')->middleware("auth:sanctum");

  Route::post('v1/gate-access', 'AutoGate\GateAccess');

  // Delete pass
  Route::put('passes/delete', 'AutoGate\DeletePass')->middleware("auth:sanctum");

  // accessed by main gate local server
  Route::post('gate-sync', 'AutoGate\GateSync')->middleware("auth:sanctum");
  // accessed by af parking gate local server
  Route::post('gate-sync-af', 'AutoGate\GateSyncAF')->middleware("auth:sanctum");

  // Corregidor passes
  Route::post('corregidor-passes', 'AutoGate\CorregidorPasses')->middleware("auth:sanctum");
  Route::get('corregidor-guests/{date}', 'AutoGate\CorregidorGuests')->middleware("auth:sanctum");

  // Aqua Fun Water Park Access
  Route::post('aqua-fun-water-park-access', 'AutoGate\AquaFunWaterParkAccess')->middleware("auth:sanctum");
  Route::get('aqua-fun-water-park-guests/{date}', 'AutoGate\AquaFunWaterParkGuests')->middleware("auth:sanctum");

  // FTT passes
  Route::post('ftt-pass-entry', 'AutoGate\FTTPassEntry')->middleware("auth:sanctum");
  Route::get('ftt-guests-entry/{date}', 'AutoGate\FTTGuestsEntry')->middleware("auth:sanctum");
  Route::post('ftt-pass-exit', 'AutoGate\FTTPassExit')->middleware("auth:sanctum");
  Route::get('ftt-guests-exit/{date}', 'AutoGate\FTTGuestsExit')->middleware("auth:sanctum");
  Route::post('gate-sync2', 'AutoGate\GateSync2')->middleware("auth:sanctum");
});


/**
 * REAL ESTATE PAYMENTS ROUTES
 */

Route::prefix('real-estate-payments')->group(function () {
  Route::get('list', 'RealEstate\PaymentList')->middleware("auth:sanctum");
  Route::post('paymaya/payment-details', 'RealEstate\PaymentGateway\PayMaya\PaymentDetails')->middleware("auth:sanctum");
  Route::post('paymaya/setup-webhook', 'RealEstate\PaymentGateway\PayMaya\SetupWebhook')->middleware("auth:sanctum");

  Route::post('payment-verification', 'RealEstate\OnlinePayment@paymentVerification')->middleware("auth:sanctum");

  Route::post('filter-lists', 'RealEstate\FilterPaymentLists')->middleware("auth:sanctum");
});

/**
 * REAL ESTATE - SALES ADMIN PORTAL
 */

Route::prefix('sales-admin-portal')->group(function () {

  Route::get('sales-clients-list', 'SalesAdminPortal\SalesClientsList')->middleware("auth:sanctum");

  Route::get('sales-team-list', 'SalesAdminPortal\SalesTeamList')->middleware("auth:sanctum");

  Route::post('new-sales-team', 'SalesAdminPortal\NewSalesTeam')->middleware("auth:sanctum");

  Route::get('sales-agent-list', 'SalesAdminPortal\SalesAgentList')->middleware("auth:sanctum");

  Route::post('update-sales-team', 'SalesAdminPortal\UpdateSalesTeam')->middleware("auth:sanctum");

  // New client record route
  Route::post('new-client-record', 'SalesAdminPortal\NewClientRecord')->middleware("auth:sanctum");

  // Lot inventory list
  Route::get('lot-inventory-list', 'SalesAdminPortal\LotInventoryList')->middleware("auth:sanctum");
  Route::get('lot-inventory-listing/{type}', 'SalesAdminPortal\LotInventoryList@listing')->middleware("auth:sanctum");
  Route::post('view-inventory-list', 'SalesAdminPortal\LotInventoryList@index')->middleware("auth:sanctum");
  Route::post('inventory-custom-filter', 'SalesAdminPortal\LotInventoryList@custom_filter')->middleware("auth:sanctum");
  Route::get('subdivision-list/{type}', 'SalesAdminPortal\LotInventoryList@subdivision_list')->middleware("auth:sanctum");
  Route::get('dashboard-counts/{type}', 'SalesAdminPortal\LotInventoryList@dashboard_counts')->middleware("auth:sanctum");

  Route::post('update-lot-details', 'SalesAdminPortal\UpdateLotDetails')->middleware("auth:sanctum");
  Route::post('delete-lot-details', 'SalesAdminPortal\UpdateLotDetails@delete_lot')->middleware("auth:sanctum");

  Route::post('new-reservation', 'SalesAdminPortal\NewReservation')->middleware("auth:sanctum");

  Route::post('update-reservation', 'SalesAdminPortal\Reservations@updateReservation')->middleware("auth:sanctum");

  Route::post('delete-reservation', 'SalesAdminPortal\Reservations@deleteReservation')->middleware("auth:sanctum");

  Route::get('reservation-list', 'SalesAdminPortal\ReservationList')->middleware("auth:sanctum");

  Route::post('view-reservation-list', 'SalesAdminPortal\ReservationList@index')->middleware("auth:sanctum");

  Route::post('view-reservation', 'SalesAdminPortal\ViewReservation')->middleware("auth:sanctum");

  Route::post('client-reservation', 'SalesAdminPortal\ViewReservation@client_reservation')->middleware("auth:sanctum");

  Route::get('reservation-details/{reservation_number}', 'SalesAdminPortal\ViewReservation@reservationDetails')->middleware("auth:sanctum");

  Route::post('view-client', 'SalesAdminPortal\ViewClient')->middleware("auth:sanctum");

  Route::post('update-reservation-client-number', 'SalesAdminPortal\UpdateReservationClientNumber')->middleware("auth:sanctum");

  Route::post('update-reservation-status', 'SalesAdminPortal\UpdateReservationStatus')->middleware("auth:sanctum");

  Route::post('update-default-penalty-discount', 'SalesAdminPortal\UpdatePenaltyDefaultDiscount')->middleware("auth:sanctum");

  Route::post('lot-inventory-dashboard', 'SalesAdminPortal\LotInventoryDashboard')->middleware("auth:sanctum");

  Route::post('get-dashboard-data', 'SalesAdminPortal\GetDashboardData')->middleware("auth:sanctum");

  Route::post('new-lot-record', 'SalesAdminPortal\NewLotRecord')->middleware("auth:sanctum");

  Route::post('lot-price-update', 'SalesAdminPortal\LotPriceUpdate')->middleware("auth:sanctum");

  Route::post('upload-file-attachment', 'SalesAdminPortal\UploadFileAttachment')->middleware("auth:sanctum");

  Route::post('remove-file-attachment', 'SalesAdminPortal\RemoveFileAttachment')->middleware("auth:sanctum");

  Route::post('update-attachment-status', 'SalesAdminPortal\UpdateAttachmentStatus')->middleware("auth:sanctum");

  Route::post('add-file-attachment', 'SalesAdminPortal\AddAttachment')->middleware("auth:sanctum");

  Route::post('update-attachment-type', 'SalesAdminPortal\UpdateAttachmentStatus@update_attachment_type')->middleware("auth:sanctum");

  Route::post('add-penalty', 'SalesAdminPortal\AddPenalty')->middleware("auth:sanctum");

  Route::post('waive-penalty', 'SalesAdminPortal\AddPenalty@waive_penalty')->middleware("auth:sanctum");

  Route::post('update-amortization-details', 'SalesAdminPortal\Payment@update_amortization')->middleware("auth:sanctum");

  Route::post('penalty-payment', 'SalesAdminPortal\AddPayment@penaltyPayment')->middleware("auth:sanctum");

  Route::post('add-payment', 'SalesAdminPortal\AddPayment')->middleware("auth:sanctum");

  Route::post('update-payment', 'SalesAdminPortal\Payment@updatePayment')->middleware("auth:sanctum");

  Route::post('re-dashboard-update-payment', 'SalesAdminPortal\Payment@reDashboardUpdatePayment')->middleware("auth:sanctum");

  Route::post('account-recomputation', 'SalesAdminPortal\Payment@recompute_account')->middleware("auth:sanctum");
  Route::post('update-payment-detail', 'SalesAdminPortal\Payment@update_payment_detail')->middleware("auth:sanctum");
  Route::post('delete-payment-detail', 'SalesAdminPortal\Payment@delete_payment_detail')->middleware("auth:sanctum");

  Route::get('reset-ra-payments/{reservation_number}/{client_number}', 'SalesAdminPortal\Payment@reset_ra_payments');

  Route::post('view-penalties', 'SalesAdminPortal\ViewPenalties')->middleware("auth:sanctum");

  Route::post('upload-file-reservation-attachment', 'SalesAdminPortal\UploadReservationAttachmentFile')->middleware("auth:sanctum");
  Route::post('remove-file-reservation-attachment', 'SalesAdminPortal\RemoveReservationAttachmentFile')->middleware("auth:sanctum");
  Route::post('approve-attachments', 'SalesAdminPortal\UploadReservationAttachmentFile@approve_attachments')->middleware("auth:sanctum");

  Route::post('upload-file-payment-attachment', 'SalesAdminPortal\PaymentDetailAttachments@upload')->middleware("auth:sanctum");
  Route::post('remove-file-payment-attachment', 'SalesAdminPortal\PaymentDetailAttachments@remove')->middleware("auth:sanctum");
  Route::get('payment-attachment-list/{transaction_id}', 'SalesAdminPortal\PaymentDetailAttachments@payment_attachments')->middleware("auth:sanctum");

  Route::post('download-crf', 'SalesAdminPortal\DownloadCRF')->middleware("auth:sanctum");
  // Route::get('download-crf', 'SalesAdminPortal\DownloadCRF');

  Route::post('download-bis', 'SalesAdminPortal\DownloadBIS')->middleware("auth:sanctum");

  Route::post('update-client-record', 'SalesAdminPortal\UpdateClientRecord')->middleware("auth:sanctum");

  Route::post('export-reservation-data', 'SalesAdminPortal\ExportReservationData')->middleware("auth:sanctum");

  Route::post('export-penalty-reports', 'SalesAdminPortal\ExportReservationData@penaltyReports')->middleware("auth:sanctum");

  Route::post('export-amortization-reports', 'SalesAdminPortal\ExportReservationData@amortizationReports')->middleware("auth:sanctum");

  Route::post('export-cash-ledger-reports', 'SalesAdminPortal\ExportReservationData@cashLedgerReports')->middleware("auth:sanctum");

  Route::post('export-import-template', 'SalesAdminPortal\ExportReservationData@import_template')->middleware("auth:sanctum");

  Route::post('export-unidentified-report', 'SalesAdminPortal\ImportPaymentData@payment_dashboard_report')->middleware("auth:sanctum");

  Route::post('import-reservation-data', 'SalesAdminPortal\ImportPaymentData')->middleware("auth:sanctum");

  Route::post('import-reports', 'SalesAdminPortal\ImportPaymentData@generate_report')->middleware("auth:sanctum");

  Route::post('bulk-upload-payments', 'SalesAdminPortal\ImportPaymentData@bulk_upload_payments')->middleware("auth:sanctum");

  Route::post('export-bis-report', 'SalesAdminPortal\ExportBISReport')->middleware("auth:sanctum");

  Route::post('add-sub-team', 'SalesAdminPortal\AddSubTeam')->middleware("auth:sanctum");

  Route::post('update-team-lead', 'SalesAdminPortal\UpdateTeamLead')->middleware("auth:sanctum");

  Route::post('update-team-members', 'SalesAdminPortal\UpdateTeamMembers')->middleware("auth:sanctum");

  Route::post('update-team-name', 'SalesAdminPortal\UpdateTeamName')->middleware("auth:sanctum");

  Route::post('delete-client-attachment', 'SalesAdminPortal\DeleteClientAttachment')->middleware("auth:sanctum");

  Route::post('export-inventory-status-report', 'SalesAdminPortal\ExportInventoryStatusReport')->middleware("auth:sanctum");
  Route::post('import-inventory-template', 'SalesAdminPortal\ExportInventoryStatusReport@inventory_template')->middleware("auth:sanctum");

  Route::post('update-rf-dp-detils', 'SalesAdminPortal\Payment@update_rf_dp_details')->middleware("auth:sanctum");

  // Collections
  Route::get('account-list', 'SalesAdminPortal\AccountList')->middleware("auth:sanctum");
  Route::post('view-account-list', 'SalesAdminPortal\AccountList@index')->middleware("auth:sanctum");
  Route::post('add-fees', 'Collections\AddFees')->middleware("auth:sanctum");

  Route::post('import-inventory-data/lot', 'SalesAdminPortal\DataImports@import_lot_inventory')->middleware("auth:sanctum");
  Route::post('import-inventory-data/condo', 'SalesAdminPortal\DataImports@import_condo_inventory')->middleware("auth:sanctum");
  Route::get('activity-logs/{reservation_number}', 'SalesAdminPortal\RealestateActivityLogs@show')->middleware("auth:sanctum");
  Route::post('add-activity-log', 'SalesAdminPortal\RealestateActivityLogs@store')->middleware("auth:sanctum");
  Route::get('real-estate-payment-activity-logs/', 'SalesAdminPortal\RealestateActivityLogs@paymentsActivityLogs')->middleware("auth:sanctum");
  Route::get('inventory-activity-logs/{type}', 'SalesAdminPortal\InventoryActivityLogs@show')->middleware("auth:sanctum");
  Route::post('add-inventory-activity-log', 'SalesAdminPortal\InventoryActivityLogs@store')->middleware("auth:sanctum");

  // Realestate Promos CRUD
  Route::get('realestate-promos', [Promos::class, 'index'])->middleware("auth:sanctum");
  Route::get('realestate-promos/{column}/{value}', [Promos::class, 'show'])->middleware("auth:sanctum");
  Route::put('update-realestate-promo', [Promos::class, 'update'])->middleware("auth:sanctum");
  Route::post('add-realestate-promo', [Promos::class, 'store'])->middleware("auth:sanctum");
  Route::delete('delete-realestate-promo/{promo_id}', [Promos::class, 'destroy'])->middleware("auth:sanctum");

  // Collection Dashboard
  Route::get('collection-receivables/{year}/{term}', 'SalesAdminPortal\GetDashboardData@receivables')->middleware("auth:sanctum");
  Route::get('collection-revenues/{month}/{year}', 'SalesAdminPortal\GetDashboardData@revenues')->middleware("auth:sanctum");
});

Route::prefix('af-parking-monitoring')->group(function () {
  Route::get('dashboard/{date?}', 'AFParkingMonitoring\Status')->middleware("auth:sanctum");
  Route::get('mode/{mode?}', 'AFParkingMonitoring\Mode')->middleware("auth:sanctum");
});

/**
 * 1BITS API
 */

Route::prefix('one-bits')->group(function () {
  Route::post('get-available-trips', 'OneBITS\GetAvailableTrips');
  Route::post('book-ferry-trip', 'OneBITS\BookFerryTrip@book');
  Route::post('get-trips-by-date', 'OneBITS\GetTripsByDate');
  Route::post('get-passengers-by-date', 'OneBITS\GetPassengersByDate');
  Route::put('board-passenger', 'OneBITS\BoardPassenger');

  Route::post('cancel-passenger-trip', 'OneBITS\CancelPassengerTrip');

  Route::get('get-passengers-by-date2/{date}', 'OneBITS\GetPassengersByDate2');

  Route::post('payment/response', 'OneBITS\Payment\PaymentResponse');
  Route::post('payment/backend', 'OneBITS\Payment\PaymentBackend');

  Route::post('book-ferry-trip-admin', 'OneBITS\Admin\BookFerryTrip');
  Route::post('confirm-passenger-trip', 'OneBITS\Admin\ConfirmPassengerTrip');

  Route::post('board-passenger-trip', 'OneBITS\Admin\BoardPassengerTrip');
  Route::post('no-show-passenger-trip', 'OneBITS\Admin\NoShowPassengerTrip');

  Route::post('get-bookings', 'OneBITS\GetBookings');
  Route::post('get-passengers-by-reference', 'OneBITS\GetPassengersByGroupReferenceNumber');
  Route::post('confirm-booking', 'OneBITS\Admin\ConfirmBooking');
  Route::post('contact-us', 'OneBITS\ContactUsController');
  Route::get('reports/1bits-sales-report/{start_date?}/{end_date?}/{download?}', 'OneBITS\Admin\Reports\SalesReport');
  Route::get('download-boarding-pass/{group_reference_number}', 'OneBITS\Admin\DownloadBoardingPass');
  Route::post('update-passenger', 'OneBITS\UpdatePassengerDetails');
  Route::post('dashboard-report', 'OneBITS\Admin\DashboardReports');
  Route::post('resend-booking-confirmation', 'OneBITS\Admin\ResendBookingConfirmation');
  Route::post('/download-passenger-manifest', 'OneBITS\Admin\Reports\GeneratePassengerManifest');

  Route::get('reports/1bits-arrival-forecast/{start_date?}/{end_date?}', 'OneBITS\Admin\Reports\ArrivalForeCast');
  Route::get('reports/1bits-arrival-forecast-summary/{start_date?}/{end_date?}', 'OneBITS\Admin\Reports\ArrivalForeCastSummary');

  // ARRIVAL FORECAST EXCEL DOWNLOAD

  Route::get('reports/1bits-arrival-forecast-download/{start_date?}/{end_date?}', 'OneBITS\Admin\Reports\ArrivalForeCastExcelDownload');

  Route::get('reports/1bits-arrival-forecast-summary-download/{start_date?}/{end_date?}', 'OneBITS\Admin\Reports\ArrivalForeCastSummaryDownload');
});
