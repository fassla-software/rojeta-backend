<?php

$baseUrl = '{{base_url}}';
$auth = [
    'type' => 'bearer',
    'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']],
];
$headers = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
];

function req(string $name, string $method, string $path, ?string $body = null, array $query = [], bool $auth = true): array
{
    global $baseUrl, $headers, $auth;

    $segments = explode('/', trim($path, '/'));
    $url = [
        'raw' => $baseUrl . '/' . $path . ($query ? '?' . http_build_query($query) : ''),
        'host' => [$baseUrl],
        'path' => $segments,
    ];

    if ($query) {
        $url['query'] = array_map(fn ($k, $v) => ['key' => $k, 'value' => $v], array_keys($query), array_values($query));
    }

    $request = [
        'method' => $method,
        'header' => $headers,
        'url' => $url,
    ];

    if ($auth) {
        $request['auth'] = $auth;
    }

    if ($body !== null) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => $body,
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    return ['name' => $name, 'request' => $request, 'response' => []];
}

function folder(string $name, array $items): array
{
    return ['name' => $name, 'item' => $items];
}

$loginScript = <<<'JS'
if (pm.response.code === 200) {
    const json = pm.response.json();
    const token = json.token || json.data?.token || json.access_token;
    if (token) pm.collectionVariables.set('token', token);
}
JS;

$logins = folder('0 - Auth (Login)', [
    array_merge(req('Login - Doctor', 'POST', 'auth/login', json_encode(['phone' => '01222222222', 'password' => 'password123'], JSON_PRETTY_PRINT), [], false), [
        'event' => [['listen' => 'test', 'script' => ['exec' => explode("\n", $loginScript), 'type' => 'text/javascript']]],
    ]),
    array_merge(req('Login - Hospital', 'POST', 'auth/login', json_encode(['phone' => '0220000000', 'password' => 'password123'], JSON_PRETTY_PRINT), [], false), [
        'event' => [['listen' => 'test', 'script' => ['exec' => explode("\n", $loginScript), 'type' => 'text/javascript']]],
    ]),
    array_merge(req('Login - Laboratory', 'POST', 'auth/login', json_encode(['phone' => '0221111111', 'password' => 'password123'], JSON_PRETTY_PRINT), [], false), [
        'event' => [['listen' => 'test', 'script' => ['exec' => explode("\n", $loginScript), 'type' => 'text/javascript']]],
    ]),
    array_merge(req('Login - Radiology', 'POST', 'auth/login', json_encode(['phone' => '0222222222', 'password' => 'password123'], JSON_PRETTY_PRINT), [], false), [
        'event' => [['listen' => 'test', 'script' => ['exec' => explode("\n", $loginScript), 'type' => 'text/javascript']]],
    ]),
    array_merge(req('Login - Nursing', 'POST', 'auth/login', json_encode(['phone' => '0223333333', 'password' => 'password123'], JSON_PRETTY_PRINT), [], false), [
        'event' => [['listen' => 'test', 'script' => ['exec' => explode("\n", $loginScript), 'type' => 'text/javascript']]],
    ]),
]);

$doctor = folder('1 - Doctor API', [
    req('GET Dashboard', 'GET', 'doctor/dashboard'),
    req('GET Profile Summary', 'GET', 'doctor/profile/summary'),
    req('GET Appointments', 'GET', 'doctor/appointments', null, ['page' => '1', 'limit' => '20']),
    req('PATCH Appointment Status', 'PATCH', 'doctor/appointments/1/status', json_encode(['status' => 'Confirmed'], JSON_PRETTY_PRINT)),
    req('GET Patients', 'GET', 'doctor/patients', null, ['search' => '', 'page' => '1']),
    req('GET Patient Detail', 'GET', 'doctor/patients/1'),
    req('POST Consultation', 'POST', 'doctor/consultation', json_encode([
        'appointment_id' => 1,
        'diagnosis' => 'Hypertension',
        'medication_details' => 'Amlodipine 5mg',
        'laboratory_tests' => 'CBC',
        'radiology_tests' => '',
        'follow_up_date' => '2026-08-15',
    ], JSON_PRETTY_PRINT)),
    req('GET Schedule', 'GET', 'doctor/schedule'),
    req('PUT Schedule', 'PUT', 'doctor/schedule', json_encode([
        'working_days' => [
            ['day_name' => 'Monday', 'is_active' => true, 'time_slots' => [['start_time' => '5:00 PM', 'end_time' => '6:00 PM']]],
            ['day_name' => 'Friday', 'is_active' => false, 'time_slots' => []],
        ],
        'vacations' => [['start_date' => '2026-02-12', 'end_date' => '2026-02-27']],
    ], JSON_PRETTY_PRINT)),
    req('GET Notifications', 'GET', 'doctor/notifications', null, ['isRead' => 'false', 'page' => '1']),
    req('PATCH Notification Read', 'PATCH', 'doctor/notifications/1/read'),
    req('GET Full Profile', 'GET', 'doctor/profile'),
    req('PUT Profile', 'PUT', 'doctor/profile', json_encode([
        'fullName' => 'Dr. Ahmed Hassan',
        'specialty' => 'Cardiologist',
        'yearsOfExperience' => 10,
        'about' => 'Experienced cardiologist',
    ], JSON_PRETTY_PRINT)),
    req('POST Add Clinic', 'POST', 'doctor/clinics', json_encode([
        'name' => 'Clinic 1',
        'phone' => '01222222222',
        'governorate' => 'Cairo',
        'city' => 'New Cairo',
        'fullAddress' => '5th Settlement',
        'consultationTime' => 30,
        'clinicVisitPrice' => 300,
    ], JSON_PRETTY_PRINT)),
    req('PUT Update Clinic', 'PUT', 'doctor/clinics/1', json_encode(['name' => 'Updated Clinic'], JSON_PRETTY_PRINT)),
    req('PUT Services', 'PUT', 'doctor/services', json_encode([
        'services' => [['id' => 'srv_1', 'isEnabled' => true, 'price' => 300, 'currency' => 'EGP']],
    ], JSON_PRETTY_PRINT)),
    req('PUT Payment Details', 'PUT', 'doctor/payment-details', json_encode([
        'paymentType' => 'bank_transfer',
        'accountHolderName' => 'Ahmed Mohamed',
        'bankName' => 'Bank Misr',
        'accountNumber' => '1234567890',
    ], JSON_PRETTY_PRINT)),
    req('GET Analytics', 'GET', 'doctor/analytics', null, ['month' => 'April 2026']),
    req('GET Marketing Packages', 'GET', 'doctor/marketing/packages'),
    req('POST Marketing Subscribe', 'POST', 'doctor/marketing/subscribe', json_encode([
        'packageId' => 'professional',
        'billingCycle' => 'monthly',
    ], JSON_PRETTY_PRINT)),
    req('GET Settings', 'GET', 'doctor/settings'),
    req('PUT Settings', 'PUT', 'doctor/settings', json_encode([
        'emailNotifications' => true,
        'marketingEmails' => false,
    ], JSON_PRETTY_PRINT)),
]);

$booking = folder('2 - Booking API', [
    req('GET Bookings (Laboratory)', 'GET', 'bookings', null, ['userType' => 'laboratory', 'status' => 'pending', 'page' => '1']),
    req('GET Bookings (Radiology)', 'GET', 'bookings', null, ['userType' => 'radiologyCenter']),
    req('GET Bookings (Nursing)', 'GET', 'bookings', null, ['userType' => 'nursingOffice']),
    req('PATCH Booking Status', 'PATCH', 'bookings/1/status', json_encode(['status' => 'confirmed'], JSON_PRETTY_PRINT)),
]);

$financial = folder('3 - Financial API', [
    req('GET Summary', 'GET', 'financial/summary', null, ['startDate' => '2026-01-01', 'endDate' => '2026-12-31']),
    req('GET Transactions', 'GET', 'financial/transactions', null, ['status' => 'completed', 'page' => '1']),
]);

$hospital = folder('4 - Hospital API', [
    req('GET Dashboard', 'GET', 'hospital/dashboard'),
    req('GET Appointments', 'GET', 'hospital/appointments', null, ['date' => '2026-07-14', 'status' => 'scheduled']),
    req('GET Services', 'GET', 'hospital/services'),
    req('PATCH Toggle Service', 'PATCH', 'hospital/services/1', json_encode(['isEnabled' => true], JSON_PRETTY_PRINT)),
    req('GET Specialties', 'GET', 'hospital/specialties'),
    req('POST Add Specialty', 'POST', 'hospital/specialties', json_encode([
        'name' => 'Orthopedic Clinic',
        'description' => 'Bones and joints',
        'hasEmergency' => false,
    ], JSON_PRETTY_PRINT)),
    req('POST Add Staff', 'POST', 'hospital/specialties/1/staff', json_encode([
        'name' => 'Dr. Jane Smith',
        'role' => 'Orthopedic Surgeon',
        'price' => '150EGP',
        'availability' => 'Mon-Fri',
    ], JSON_PRETTY_PRINT)),
    req('GET ICU Rooms', 'GET', 'hospital/icu-rooms'),
    req('GET Incubators', 'GET', 'hospital/incubators'),
    req('GET Lab Tests', 'GET', 'hospital/lab-tests'),
    req('GET Radiology Services', 'GET', 'hospital/radiology-services'),
    req('GET Settings', 'GET', 'hospital/settings'),
    req('PUT Settings', 'PUT', 'hospital/settings', json_encode([
        'pushNotificationsEnabled' => true,
        'languageCode' => 'en',
    ], JSON_PRETTY_PRINT)),
]);

$lab = folder('5 - Laboratory & Radiology API', [
    req('GET Dashboard', 'GET', 'lab/dashboard'),
    req('GET Services', 'GET', 'lab/services', null, ['category' => 'Hematology', 'search' => 'CBC']),
    req('POST Add Service', 'POST', 'lab/services', json_encode([
        'name' => 'CBC (Complete Blood Count)',
        'price' => 120,
        'isHomeCollectionAvailable' => true,
        'turnaroundTime' => '24 hours',
        'category' => 'Hematology',
    ], JSON_PRETTY_PRINT)),
    req('GET Branches', 'GET', 'lab/branches'),
    req('POST Add Branch', 'POST', 'lab/branches', json_encode([
        'name' => 'New Cairo Branch',
        'address' => '5th Settlement',
        'phone' => '01234567890',
        'workingHours' => 'Sat-Thu: 8 AM - 10 PM',
        'isHomeCollectionAvailable' => true,
    ], JSON_PRETTY_PRINT)),
    req('GET Home Visit Config', 'GET', 'lab/home-visits/config'),
    req('PUT Home Visit Config', 'PUT', 'lab/home-visits/config', json_encode([
        'isServiceVisible' => true,
        'collectionFee' => 50,
        'minimumBookingAmount' => 200,
        'serviceAreas' => [['name' => 'New Cairo', 'radiusKm' => 15, 'techsAvailable' => 4]],
        'weekdaySlots' => [['time' => '08:00', 'amPm' => 'AM', 'status' => 'active']],
        'weekendSlots' => [['time' => '09:00', 'amPm' => 'AM', 'status' => 'active']],
    ], JSON_PRETTY_PRINT)),
    req('GET Profile', 'GET', 'lab/profile'),
    req('GET Info', 'GET', 'lab/info'),
    req('PUT Info', 'PUT', 'lab/info', json_encode([
        'labName' => 'City Lab',
        'licenseNumber' => 'LAB-2023-994821',
        'primaryContactEmail' => 'info@citylab.com',
        'phoneNumber' => '01234567890',
    ], JSON_PRETTY_PRINT)),
    req('GET Working Hours', 'GET', 'lab/working-hours'),
    req('PUT Working Hours', 'PUT', 'lab/working-hours', json_encode([
        'standardWeek' => [
            ['dayName' => 'Saturday', 'isClosed' => false, 'openingTime' => '08:00 AM', 'closingTime' => '10:00 PM'],
            ['dayName' => 'Friday', 'isClosed' => true, 'openingTime' => null, 'closingTime' => null],
        ],
        'upcomingOverrides' => [
            ['dateStr' => '2026-07-23', 'eventName' => 'Eid Holiday', 'isClosed' => true, 'openingTime' => null, 'closingTime' => null],
        ],
    ], JSON_PRETTY_PRINT)),
    req('GET Settings', 'GET', 'lab/settings'),
    req('PUT Settings', 'PUT', 'lab/settings', json_encode([
        'emailNotifications' => true,
        'language' => 'English',
    ], JSON_PRETTY_PRINT)),
]);

$nursing = folder('6 - Nursing API', [
    req('GET Dashboard', 'GET', 'nursing/dashboard'),
    req('GET Services', 'GET', 'nursing/services'),
    req('POST Add Service', 'POST', 'nursing/services', json_encode([
        'title' => 'Home Nursing Care',
        'subtitle' => 'Professional in-home nursing',
        'durationTag' => '12 Hours',
        'bulletPoints' => ['Medication management', 'Wound care'],
    ], JSON_PRETTY_PRINT)),
    req('GET Staff', 'GET', 'nursing/staff'),
    req('POST Add Staff', 'POST', 'nursing/staff', json_encode([
        'name' => 'Nurse Fatma',
        'gender' => 'Female',
        'status' => 'available',
        'skills' => ['Wound Care', 'IV Therapy'],
        'yearsOfExperience' => 5,
    ], JSON_PRETTY_PRINT)),
    req('GET Profile', 'GET', 'nursing/profile'),
    req('GET Office Info', 'GET', 'nursing/office-info'),
    req('PUT Office Info', 'PUT', 'nursing/office-info', json_encode([
        'officeName' => 'City Nursing Office',
        'licenseNumber' => 'NRS-2024-001234',
        'email' => 'info@citynursing.com',
        'phone' => '01234567890',
    ], JSON_PRETTY_PRINT)),
    req('GET Working Hours', 'GET', 'nursing/working-hours'),
    req('PUT Working Hours', 'PUT', 'nursing/working-hours', json_encode([
        'days' => [['day' => 'Saturday', 'isOpen' => true, 'openTime' => '08:00 AM', 'closeTime' => '10:00 PM']],
    ], JSON_PRETTY_PRINT)),
    req('GET Settings', 'GET', 'nursing/settings'),
    req('PUT Settings', 'PUT', 'nursing/settings', json_encode([
        'pushNotifications' => true,
        'marketingEmails' => false,
    ], JSON_PRETTY_PRINT)),
]);

$collection = [
    'info' => [
        'name' => 'Rojeta Provider API',
        'description' => 'All provider-facing endpoints (Doctor, Booking, Financial, Hospital, Lab/Radiology, Nursing). Base URL: http://127.0.0.1:8001/api/v1. Run a Login request first to set {{token}}.',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://127.0.0.1:8001/api/v1', 'type' => 'string'],
        ['key' => 'token', 'value' => '', 'type' => 'string'],
    ],
    'item' => [$logins, $doctor, $booking, $financial, $hospital, $lab, $nursing],
];

file_put_contents(
    __DIR__ . '/../Rojeta_Provider_API_Postman_Collection.json',
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "Generated Rojeta_Provider_API_Postman_Collection.json\n";
