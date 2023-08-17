import React, {Suspense, lazy} from 'react'

import Home from 'screens/Main/Home' // Home page

const Users = lazy(() => import('screens/Main/Users'))
const Register = lazy(() => import('screens/Main/Register'))
const Access = lazy(() => import('screens/Main/Access'))

const HOA = lazy(() => import('screens/Main/HOA'))

const BookingHome = lazy(() => import('screens/Booking/Home'))
const BookingDashboard = lazy(() => import('screens/Booking/Dashboard'))
const BookingSettings = lazy(() => import('screens/Booking/Settings'))
const BookingGuests = lazy(() => import('screens/Booking/Guests'))
const BookingInventory = lazy(() => import('screens/Booking/Inventory'))
const BookingTripping = lazy(() => import('screens/Booking/Tripping'))
const BookingReports = lazy(() => import('screens/Booking/Reports'))
// const ChangePassword = lazy(() => import('screens/Web/ChangePassword'));

const HotelDashboard = lazy(() => import('screens/Hotel/Dashboard'))
const HotelHome = lazy(() => import('screens/Hotel/Home'))
const RoomReservationCalendar = lazy(() => import('screens/Hotel/RoomReservationCalendar'))
const HotelInventory = lazy(() => import('screens/Hotel/Inventory'))
const HotelGuests = lazy(() => import('screens/Hotel/Guests'))
const HotelReports = lazy(() => import('screens/Hotel/Reports'))

const TransportationHome = lazy(() => import('screens/Transportation/Home'))
const TransportationInventory = lazy(() => import('screens/Transportation/Inventory'))
const TransportationPassenger = lazy(() => import('screens/Transportation/Passenger'))
const TransportationReports = lazy(() => import('screens/Transportation/Reports'))

const ConciergeHome = lazy(() => import('screens/Concierge/Home'))
const ConciergeReports = lazy(() => import('screens/Concierge/Reports'))

const AutoGateHome = lazy(() => import('screens/AutoGate/Home'))

const AFParkingMonitoringHome = lazy(() => import('screens/AutoGate/AFParkingMonitoring'))

const HousekeepingHome = lazy(() => import('screens/Housekeeping/Home'))

const GolfHome = lazy(() => import('screens/Golf/Home'))
const GolfTeeTime = lazy(() => import('screens/Golf/TeeTime'))

const RealEstatePayments = lazy(() => import('screens/RealEstatePayments/Home'))

const SalesPortalAdmin = lazy(() => import('screens/SalesPortalAdmin/Home'))
const SalesClients = lazy(() => import('screens/SalesPortalAdmin/SalesClients'))
const ReservationAgreements = lazy(() => import('screens/SalesPortalAdmin/ReservationAgreements'))
const SalesTeam = lazy(() => import('screens/SalesPortalAdmin/SalesTeam'))
const Promos = lazy(() => import('screens/SalesPortalAdmin/Promos'))
const LotInventory = lazy(() => import('screens/SalesPortalAdmin/LotInventory'))
const CondoInventory = lazy(() => import('screens/SalesPortalAdmin/CondoInventory'))
const NewReservation = lazy(() => import('screens/SalesPortalAdmin/NewReservation'))
const ViewReservation = lazy(() => import('screens/SalesPortalAdmin/ViewReservation'))
const EditReservation = lazy(() => import('screens/SalesPortalAdmin/EditReservation'))
const ViewClient = lazy(() => import('screens/SalesPortalAdmin/ViewClient'))

const CollectionsViewAccount = lazy(() => import('screens/Collections/ViewAccount'))
const CollectionsTransactedAccounts = lazy(() => import('screens/Collections/TransactedAccounts'))
const Collections = lazy(() => import('screens/Collections/Home'))

const GolfAdminPortal = lazy(() => import('screens/GolfAdminPortal/Home'))

const ViewBookingScreen = lazy(() => import('screens/Booking/ViewBooking'))


// import Fallback from 'common/Fallback'; // Fallback
import Loading from 'common/Loading'
import PageNotFound from 'common/PageNotFound' // Page not found - (404)

// Lazy imports

const routes = [
    // Login
    // {
    //     path: '/login',
    //     exact: true,
    //     auth: false,
    //     component: (props) => <Login {...props}/>
    // },

    // Home page
    {
        path: '/',
        title: 'Home',
        name: 'home',
        exact: true,
        auth: false,
        component: (props) => <Home {...props}/>
    },

    // Users page
    {
        path: '/users',
        title: 'Users',
        name: 'users',
        exact: true,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><Users {...props}/></Suspense>
    },

    // HOA page
    {
        path: '/hoa',
        title: 'HOA',
        name: 'hoa',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HOA {...props}/></Suspense>
    },

    // Reports
    {
        path: '/access',
        title: 'Access',
        name: 'access',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><Access {...props}/></Suspense>
    },

    // Register page
    {
        path: '/register',
        title: 'Register',
        name: 'register',
        exact: true,
        auth: false,
        component: (props) => <Suspense fallback={<Loading/>}><Register {...props}/></Suspense>
    },

    /**
     * BOOKING ROUTES
     */
    // View Reservations
    {
        path: '/booking/view-booking/:reference_number',
        title: 'View Booking | Sales Admin Portal',
        name: 'view-booking',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><ViewBookingScreen {...props}/></Suspense>
    }, 

     // Tripping page
     {
        path: '/booking/tripping',
        title: 'Tripping',
        name: 'tripping',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingTripping {...props}/></Suspense>
    },

     // Guests page
     {
        path: '/booking/guests',
        title: 'Booking Guests',
        name: 'booking-guests',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingGuests {...props}/></Suspense>
    },

    // Inventory page
    {
        path: '/booking/inventory',
        title: 'Booking Inventory',
        name: 'booking-inventory',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingInventory {...props}/></Suspense>
    },

    // Settings page
    {
        path: '/booking/settings',
        title: 'Booking Settings',
        name: 'booking-settings',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingSettings {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/booking/dashboard',
        title: 'Booking Dashboard',
        name: 'booking-dashboard',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingDashboard {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/booking/reports',
        title: 'Booking Reports',
        name: 'booking-reports',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingReports {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/booking',
        title: 'Booking',
        name: 'booking',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><BookingHome {...props}/></Suspense>
    },

    // Hotel Guests page
    {
        path: '/hotel/reports',
        title: 'Hotel Reports',
        name: 'hotel-reports',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HotelReports {...props}/></Suspense>
    },

    // Hotel Guests page
    {
        path: '/hotel/guests',
        title: 'Hotel Guests',
        name: 'hotel-guests',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HotelGuests {...props}/></Suspense>
    },

    // Hotel Inventory page
    {
        path: '/hotel/inventory',
        title: 'Hotel Inventory',
        name: 'hotel-inventory',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HotelInventory {...props}/></Suspense>
    },

    // Hotel Dashboard page
    {
        path: '/hotel/dashboard',
        title: 'Hotel Dashboard',
        name: 'hotel-dashboard',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HotelDashboard {...props}/></Suspense>
    },


    // Hotel Dashboard page
    {
        path: '/hotel/calendar',
        title: 'Hotel',
        name: 'hotel',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HotelHome {...props}/></Suspense>
    },

    // Hotel Dashboard page
    {
        path: '/hotel/room-reservation-calendar',
        title: 'Room Reservation Calendar',
        name: 'room-reservation-calendar',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><RoomReservationCalendar {...props}/></Suspense>
    },

    // Transportation Reports page
    {
        path: '/transportation/reports',
        title: 'Transportation Reports',
        name: 'transportation-reports',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><TransportationReports {...props}/></Suspense>
    },

    // Transportation Inventory page
    {
        path: '/transportation/inventory',
        title: 'Transportation Inventory',
        name: 'transportation-inventory',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><TransportationInventory {...props}/></Suspense>
    },

    // Transportation passenger page
    {
        path: '/transportation/passengers',
        title: 'Passengers',
        name: 'passengers',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><TransportationPassenger {...props}/></Suspense>
    },


    // Transportation page
    {
        path: '/transportation',
        title: 'Transportation',
        name: 'transportation',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><TransportationHome {...props}/></Suspense>
    },

    // Concierge Report
    {
        path: '/concierge/reports',
        title: 'Concierge Reports',
        name: 'concierge-reports',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><ConciergeReports {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/concierge',
        title: 'Concierge',
        name: 'concierge',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><ConciergeHome {...props}/></Suspense>
    }, 
    
    // Dashboard page
    {
        path: '/housekeeping',
        title: 'Housekeeping',
        name: 'housekeeping',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><HousekeepingHome {...props}/></Suspense>
    }, 

    /**
     * Auto-Gate Pages
     */

    // Dashboard page
    {
        path: '/auto-gate/af-parking-monitoring',
        title: 'AF Parking Monitoring',
        name: 'af-parking-monitoring',
        exact: true,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><AFParkingMonitoringHome {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/auto-gate',
        title: 'Auto-Gate',
        name: 'auto-gate',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><AutoGateHome {...props}/></Suspense>
    }, 
    

    /**
     * Golf Pages
     */

     // Dashboard page
    {
        path: '/golf/tee-time',
        title: 'Golf Tee Time',
        name: 'golf-tee-time',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><GolfTeeTime {...props}/></Suspense>
    }, 

    // Dashboard page
    {
        path: '/golf',
        title: 'Golf',
        name: 'golf',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><GolfHome {...props}/></Suspense>
    }, 

    // Dashboard page
    {
        path: '/real-estate-payments',
        title: 'Real Estate Payments',
        name: 'real-estate-payments',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><RealEstatePayments {...props}/></Suspense>
    },

    // Lot Inventory
    {
        path: '/sales-admin-portal/lot-inventory',
        title: 'Lot Inventory | Sales Admin Portal',
        name: 'lot-inventory',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><LotInventory {...props}/></Suspense>
    }, 

    // Condominium Inventory
    {
        path: '/sales-admin-portal/condo-inventory',
        title: 'Condominium Inventory | Sales Admin Portal',
        name: 'condo-inventory',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><CondoInventory {...props}/></Suspense>
    }, 

    // Sales Team
    {
        path: '/sales-admin-portal/sales-team',
        title: 'Sales Team | Sales Admin Portal',
        name: 'sales-team',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><SalesTeam {...props}/></Suspense>
    }, 

    // Sales Reservations Agreement
    {
        path: '/sales-admin-portal/reservation-documents',
        title: 'Reservation Documents | Sales Admin Portal',
        name: 'reservation-documents',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><ReservationAgreements {...props}/></Suspense>
    }, 

    // View Reservations
    {
        path: '/sales-admin-portal/view-reservation/:reservation_number',
        title: 'View Reservation | Sales Admin Portal',
        name: 'view-reservation',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><ViewReservation {...props}/></Suspense>
    }, 

    // Edit Reservations
    {
        path: '/sales-admin-portal/edit-reservation/:reservation_number',
        title: 'Edit Reservation | Sales Admin Portal',
        name: 'edit-reservation',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><EditReservation {...props}/></Suspense>
    },

    // View Client Via Number
    {
        path: '/sales-admin-portal/view-client/:client_number',
        title: 'View Client | Sales Admin Portal',
        name: 'view-client',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><ViewClient {...props}/></Suspense>
    }, 


    // Sales Reservations Agreement
    {
        path: '/sales-admin-portal/new-reservation',
        title: 'New Reservation | Sales Admin Portal',
        name: 'new-reservation',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><NewReservation {...props}/></Suspense>
    }, 

    // Sales Client page
    {
        path: '/sales-admin-portal/sales-clients',
        title: 'Sales Clients | Sales Admin Portal',
        name: 'sales-clients',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><SalesClients {...props}/></Suspense>
    }, 

    {
        path: '/sales-admin-portal/promos',
        title: 'Promos | Sales Admin Portal',
        name: 'promos',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><Promos {...props}/></Suspense>
    }, 

    // Dashboard page
    {
        path: '/sales-admin-portal',
        title: 'Sales Admin Portal',
        name: 'sales-admin-portal',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><SalesPortalAdmin {...props}/></Suspense>
    },

    // View Reservations
    {
        path: '/collections/view-account/:reservation_number',
        title: 'View Account | Collections',
        name: 'view-reservation',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><CollectionsViewAccount {...props}/></Suspense>
    }, 

    // Dashboard page
    {
        path: '/collections/transacted-accounts',
        title: 'Transacted Accounts',
        name: 'transacted-accountss',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><CollectionsTransactedAccounts {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/collections',
        title: 'Collections',
        name: 'collections',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><Collections {...props}/></Suspense>
    },

    // Dashboard page
    {
        path: '/golf-admin-portal',
        title: 'Golf Admin Portal',
        name: 'golf-admin-portal',
        exact: false,
        auth: true,
        component: (props) => <Suspense fallback={<Loading/>}><GolfAdminPortal {...props}/></Suspense>
    }, 
    
    // 404
    {
        path: '',
        exact: true,
        auth: false,
        title: 'Lost in space',
        component: (props) => <PageNotFound {...props}/>
    },
];

export default routes;