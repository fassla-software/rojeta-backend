# **Rojeta Doctor — Backend API Documentation**

**Base URL:** https://api.rojeta.com/v1 (replace with actual production URL)  
**Auth:** All endpoints require the Authorization: Bearer \<token\> header unless noted otherwise.  
**Content-Type:** application/json (endpoints requiring file uploads use multipart/form-data).

## **1\. Booking API**

**Context:** Shared across Laboratory, Radiology Center, and Nursing. Distinguished by the userType query parameter.

### **GET /bookings**

**Description:** Fetch the list of bookings for the current provider.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| userType | string | Yes | laboratory, radiologyCenter, or nursingOffice |
| status | string | No | pending, confirmed, completed, or rejected |
| page | int | No | Pagination page number (default: 1\) |
| limit | int | No | Items per page (default: 20\) |

JSON  
{  
  "data": \[  
    {  
      "id": "1",  
      "type": "branchVisit",  
      "patientName": "Mohamed Ali",  
      "patientPhone": "01012345678",  
      "serviceName": "PCR",  
      "locationName": "New cairo",  
      "date": "2026-02-06",  
      "time": "10:00 AM",  
      "status": "pending",  
      "paymentStatus": "paid",  
      "fee": 300.0  
    }  
  \],  
  "meta": {  
    "page": 1,  
    "limit": 20,  
    "total": 3  
  }  
}

### **PATCH /bookings/{bookingId}/status**

**Description:** Update the status of a booking.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| bookingId | string | Yes | The booking's ID (Path Parameter) |
| status | string | Yes | pending, confirmed, completed, or rejected (Request Body) |

JSON  
{  
  "message": "Booking status updated successfully"  
}

## **2\. Doctor API — Dashboard & Operations**

### **GET /doctor/dashboard**

**Description:** Fetch the doctor's dashboard statistics summary.

JSON  
{  
  "data": {  
    "todayAppointments": 12,  
    "pendingRequests": 3,  
    "completedAppointments": 8,  
    "newRequests": 5,  
    "rating": 4.8,  
    "totalEarnings": "3600"  
  }  
}

### **GET /doctor/profile/summary**

**Description:** Fetch the doctor's basic profile info for the home screen header/drawer.

JSON  
{  
  "data": {  
    "name": "Dr. Ahmed Hassan",  
    "specialty": "Cardiologist",  
    "imageUrl": "https://cdn.rojeta.com/doctors/ahmed\_hassan.png"  
  }  
}

### **GET /doctor/appointments**

**Description:** Fetch the doctor's appointment list with filtering support.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| date | string | No | Filter by date (ISO 8601: YYYY-MM-DD) |
| status | string | No | Pending, Confirmed, Completed, Cancelled |
| type | string | No | Clinic Visit, Home Visit, Video Call |
| page | int | No | Pagination page (default: 1\) |
| limit | int | No | Items per page (default: 20\) |

JSON  
{  
  "data": \[  
    {  
      "id": "1",  
      "patientName": "Mohamed Ali",  
      "patientAge": "45 yrs",  
      "patientGender": "Male",  
      "appointmentTime": "10:00 AM",  
      "appointmentDate": "2026-02-06",  
      "type": "Clinic Visit",  
      "status": "Pending",  
      "patientPhone": "01012345678",  
      "isUrgent": false,  
      "location": "New cairo",  
      "notes": "Cardiology Consultation",  
      "feeOriginal": "400 EGP",  
      "feeCurrent": "300 EGP",  
      "discount": "25%",  
      "paymentMethod": "cash",  
      "isOnline": false  
    }  
  \],  
  "meta": {  
    "page": 1,  
    "limit": 20,  
    "total": 4  
  }  
}

### **PATCH /doctor/appointments/{appointmentId}/status**

**Description:** Update the status of a doctor appointment.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| appointmentId | string | Yes | The appointment's ID (Path Parameter) |
| status | string | Yes | Confirmed, Completed, or Cancelled (Request Body) |

JSON  
{  
  "message": "Appointment status updated successfully"  
}

### **GET /doctor/patients**

**Description:** Fetch the list of patients associated with the doctor.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| search | string | No | Search by   patient name or phone |
| page | int | No | Pagination page |
| limit | int | No | Items per page |

JSON  
{  
  "data": \[  
    {  
      "id": "p1",  
      "name": "Mohamed Ali",  
      "phone": "01012345678",  
      "age": 45,  
      "gender": "Male",  
      "visitsCount": 5,  
      "lastVisitDate": "2026-01-15T00:00:00.000Z",  
      "lastDiagnosis": "Hypertension"  
    }  
  \],  
  "meta": {  
    "page": 1,  
    "limit": 20,  
    "total": 10  
  }  
}

### **GET /doctor/patients/{patientId}**

**Description:** Fetch detailed patient information including medical history.

JSON  
{  
  "data": {  
    "basicInfo": {  
      "id": "p1",  
      "name": "Mohamed Ali",  
      "phone": "01012345678",  
      "age": 45,  
      "gender": "Male",  
      "visitsCount": 5,  
      "lastVisitDate": "2026-01-15T00:00:00.000Z",  
      "lastDiagnosis": "Hypertension"  
    },  
    "allergies": \["Penicillin", "Aspirin"\],  
    "chronicConditions": \["Hypertension", "Diabetes Type 2"\],  
    "visitHistory": \[  
      {  
        "id": "v1",  
        "date": "2026-01-15T00:00:00.000Z",  
        "type": "Clinic Visit",  
        "diagnosis": "Hypertension",  
        "prescription": "Amlodipine 5mg",  
        "notes": "Blood pressure controlled",  
        "followUpDate": "2026-02-15T00:00:00.000Z"  
      }  
    \],  
    "labResults": \[  
      {  
        "id": "lr1",  
        "date": "2026-01-10T00:00:00.000Z",  
        "testsPerformed": "CBC, Lipid Profile",  
        "results": "Cholesterol: 220 mg/dL",  
        "notes": "Slightly elevated cholesterol",  
        "isNormal": false  
      }  
    \]  
  }  
}

### **POST /doctor/consultation**

**Description:** Submit a medical consultation result after an appointment (Uses multipart/form-data).

| Field | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| appointment\_id | int | Yes | The target appointment ID |
| diagnosis | string | No | The doctor's diagnosis |
| medication\_details | string | No | Prescribed medications and dosages |
| laboratory\_tests | string | No | Ordered lab tests |
| radiology\_tests | string | No | Ordered radiology tests |
| follow\_up\_date | string | No | Follow-up date (ISO 8601\) |
| prescription\_pdf | file | No | Uploaded prescription PDF file |

JSON  
{  
  "message": "Consultation submitted successfully",  
  "data": {  
    "consultationId": "c123"  
  }  
}

### **GET /doctor/schedule**

**Description:** Fetch the doctor's weekly schedule and vacations.

JSON  
{  
  "data": {  
    "working\_days": \[  
      {  
        "day\_name": "Monday",  
        "is\_active": true,  
        "time\_slots": \[  
          { "start\_time": "5:00 PM", "end\_time": "6:00 PM" },  
          { "start\_time": "7:00 PM", "end\_time": "8:00 PM" }  
        \]  
      },  
      {  
        "day\_name": "Friday",  
        "is\_active": false,  
        "time\_slots": \[\]  
      }  
    \],  
    "vacations": \[  
      {  
        "id": "1",  
        "start\_date": "2026-02-12",  
        "end\_date": "2026-02-27"  
      }  
    \]  
  }  
}

### **PUT /doctor/schedule**

**Description:** Update the doctor's weekly schedule and vacations. (Request body mirrors the GET response data).

JSON  
{  
  "message": "Schedule updated successfully"  
}

### **GET /doctor/notifications**

**Description:** Fetch the doctor's notifications list.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| isRead | bool | No | Filter by read status |
| type | string | No | appointment\_request, payment, review, message, system, etc. |
| page | int | No | Pagination page |
| limit | int | No | Items per page |

JSON  
{  
  "data": \[  
    {  
      "id": "1",  
      "title": "New Appointment Request",  
      "body": "Mohamed Ali requested an appointment for tomorrow at 10:00 AM",  
      "time": "5 minutes ago",  
      "isRead": false,  
      "priority": "High",  
      "type": "appointment\_request"  
    }  
  \],  
  "meta": {  
    "page": 1,  
    "limit": 20,  
    "total": 12  
  }  
}

### **PATCH /doctor/notifications/{notificationId}/read**

**Description:** Mark a notification as read.

JSON  
{  
  "message": "Notification marked as read"  
}

## **3\. Doctor API — Profile & Settings**

### **GET /doctor/profile**

**Description:** Fetch the doctor's full profile including clinics, services, and payment details.

JSON  
{  
  "data": {  
    "id": "doc\_123",  
    "fullName": "Dr. Ahmed Hassan",  
    "email": "ahmed.hassan@rujeta.com",  
    "phone": "01111111111",  
    "specialty": "Cardiologist",  
    "subSpecialty": "",  
    "yearsOfExperience": "10",  
    "about": "Experienced cardiologist...",  
    "education": "Cairo University, Faculty of Medicine",  
    "imageUrl": "https://cdn.rojeta.com/doctors/ahmed\_hassan.png",  
    "isPro": false,  
    "clinics": \[  
      {  
        "id": "clinic\_1",  
        "name": "Clinic 1",  
        "phone": "01222222222",  
        "governorate": "Cairo",  
        "city": "New Cairo",  
        "fullAddress": "5th Settlement, Street 90",  
        "photos": \["https://cdn.rojeta.com/clinics/photo1.jpg"\],  
        "consultationTime": 30,  
        "workingHours": {  
          "Monday": { "start": "09:00 AM", "end": "05:00 PM" }  
        },  
        "clinicVisitPrice": 300.0,  
        "followUpPrice": 150.0,  
        "homeVisitPrice": 500.0,  
        "videoCallPrice": 200.0  
      }  
    \],  
    "services": \[  
      {  
        "id": "srv\_1",  
        "name": "Clinic Visit",  
        "isEnabled": true,  
        "price": 300.0,  
        "currency": "EGP"  
      }  
    \],  
    "paymentDetails": {  
      "paymentType": "bank\_transfer",  
      "accountHolderName": "Ahmed Mohamed",  
      "bankName": "Bank Misr",  
      "accountNumber": "\*\*\*\* \*\*\*\* \*\*\*\* 1234"  
    }  
  }  
}

### **PUT /doctor/profile**

**Description:** Update the doctor's primary profile information.

JSON  
{  
  "message": "Profile updated successfully"  
}

### **POST /doctor/clinics**

**Description:** Add a new clinic to the doctor's profile. (Uses multipart/form-data).

| Field | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| name | string | Yes | Clinic name |
| phone | string | No | Clinic phone number |
| governorate | string | No | Region or governorate |
| city | string | No | City |
| fullAddress | string | No | Complete address |
| photos\[\] | file\[\] | No | Clinic photos (multiple) |
| consultationTime | int | No | Duration in minutes |
| workingHours | json | No | Schedule JSON string |
| clinicVisitPrice | double | No | Base visit price |

JSON  
{  
  "message": "Clinic added successfully",  
  "data": { "id": "clinic\_3" }  
}

### **PUT /doctor/clinics/{clinicId}**

**Description:** Update an existing clinic. Identical payload to POST /doctor/clinics.

JSON  
{  
  "message": "Clinic updated successfully"  
}

### **PUT /doctor/services**

**Description:** Update the doctor's service pricing configuration.

JSON  
{  
  "message": "Services updated successfully"  
}

### **PUT /doctor/payment-details**

**Description:** Update the doctor's payment/payout information.

| Field | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| paymentType | string | Yes | bank\_transfer or mobile\_wallet |
| accountHolderName | string | No | Full name on account |
| bankName | string | No | Bank name (if applicable) |
| accountNumber | string | No | Account or wallet number |

JSON  
{  
  "message": "Payment details updated successfully"  
}

### **GET /doctor/analytics**

**Description:** Fetch the doctor's analytics data for a given month.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| month | string | No | Month label, e.g. April 2026 |

JSON  
{  
  "data": {  
    "monthLabel": "April 2026",  
    "totalPatients": 145,  
    "newPatients": 42,  
    "returningPatients": 103,  
    "totalRevenue": 58750.0,  
    "expenses": 12180.0,  
    "netIncome": 46450.0,  
    "avgPerPatient": 405.0,  
    "totalAppointments": 178,  
    "completedAppointments": 156,  
    "cancelledAppointments": 34,  
    "patientGrowthPercent": 18.5,  
    "revenueGrowthPercent": 22.3,  
    "successRate": 87.6  
  }  
}

### **GET /doctor/marketing/packages**

**Description:** Fetch available marketing packages and the doctor's active plan.

JSON  
{  
  "data": {  
    "packages": \[  
      {  
        "id": "professional",  
        "nameKey": "professional",  
        "postsPerMonth": "12",  
        "monthlyPrice": 5000,  
        "yearlyPrice": 50000,  
        "isMostPopular": true,  
        "platforms": \["Facebook", "Instagram", "Twitter"\],  
        "features": \["designQuality", "analytics", "priority", "support"\]  
      }  
    \],  
    "activePlan": {  
      "packageId": "professional",  
      "postsUsed": 8,  
      "totalPosts": 12,  
      "startDate": "2026-06-28T00:00:00.000Z",  
      "endDate": "2026-07-28T00:00:00.000Z"  
    }  
  }  
}

### **POST /doctor/marketing/subscribe**

**Description:** Subscribe to a marketing package.

| Field | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| packageId | string | Yes | basic, professional, premium |
| billingCycle | string | Yes | monthly or yearly |

JSON  
{  
  "message": "Subscription successful",  
  "data": { "subscriptionId": "sub\_456" }  
}

### **GET /doctor/settings**

**Description:** Fetch the doctor's notification settings.

JSON  
{  
  "data": {  
    "emailNotifications": true,  
    "smsNotifications": true,  
    "pushNotifications": true,  
    "appointmentReminders": true,  
    "marketingEmails": false  
  }  
}

### **PUT /doctor/settings**

**Description:** Update the doctor's notification settings.

JSON  
{  
  "message": "Settings updated successfully"  
}

## **4\. Financial API**

**Context:** Shared across Doctor, Lab, Radiology, and Nursing. Role is identified via the auth token.

### **GET /financial/summary**

**Description:** Fetch the financial summary for the authenticated provider.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| startDate | string | No | Filter start date (ISO 8601\) |
| endDate | string | No | Filter end date (ISO 8601\) |

JSON  
{  
  "data": {  
    "totalIncome": 75200,  
    "completedAppointments": 245,  
    "commission": 7520,  
    "netIncome": 67680  
  }  
}

### **GET /financial/transactions**

**Description:** Fetch the list of financial transactions.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| status | string | No | completed or pending |
| startDate | string | No | Filter start date |
| endDate | string | No | Filter end date |
| page | int | No | Pagination page |
| limit | int | No | Items per page |

JSON  
{  
  "data": \[  
    {  
      "patientName": "Marwan Ali",  
      "visitType": "Clinic Visit",  
      "status": "completed",  
      "date": "24 May 2024",  
      "time": "10:30 AM",  
      "serviceFee": 500,  
      "platformCommission": 50,  
      "yourEarning": 450  
    }  
  \],  
  "meta": {  
    "page": 1,  
    "limit": 20,  
    "total": 4  
  }  
}

## **5\. Hospital API**

### **GET /hospital/dashboard**

**Description:** Fetch the hospital's dashboard statistics.

JSON  
{  
  "data": {  
    "doctors": "48",  
    "todaysIncome": "12,500 EGP",  
    "appointments": "124"  
  }  
}

### **GET /hospital/appointments**

**Description:** Fetch the hospital's upcoming appointments.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| date | string | No | Filter by date |
| status | string | No | scheduled or completed |
| page | int | No | Pagination page |
| limit | int | No | Items per page |

JSON  
{  
  "data": \[  
    {  
      "id": "1",  
      "patientName": "Mohamed Ahmed",  
      "patientAge": "30",  
      "patientGender": "Male",  
      "appointmentDate": "2023-11-20",  
      "appointmentTime": "10:00 AM",  
      "type": "Dr. Ahmed Hassan",  
      "status": "scheduled",  
      "isOnline": false  
    }  
  \]  
}

### **GET /hospital/services**

**Description:** Fetch the list of hospital services with their enabled/disabled state.

JSON  
{  
  "data": \[  
    {  
      "id": "1",  
      "titleKey": "serviceLaboratory",  
      "descriptionKey": "serviceLaboratoryDesc",  
      "isEnabled": true,  
      "iconPath": "assets/icons/laboratory.png",  
      "extraInfoKey": null,  
      "extraInfoValue": null  
    }  
  \]  
}

### **PATCH /hospital/services/{serviceId}**

**Description:** Toggle a hospital service on or off.

JSON  
{  
  "message": "Service updated successfully"  
}

### **GET /hospital/specialties**

**Description:** Fetch the list of hospital specialties (clinics) and their staff.

JSON  
{  
  "data": \[  
    {  
      "id": "s1",  
      "name": "Orthopedic Clinic",  
      "description": "Bones and joints",  
      "hasEmergency": false,  
      "doctorsCount": 12,  
      "nursesCount": 8,  
      "staffList": \[  
        {  
          "id": "d1",  
          "name": "Dr. Jane Smith",  
          "role": "Orthopedic Surgeon",  
          "price": "150EGP",  
          "availability": "Mon-Fri",  
          "imagePath": "https://cdn.rojeta.com/staff/jane.png"  
        }  
      \]  
    }  
  \]  
}

### **POST /hospital/specialties**

**Description:** Add a new specialty to the hospital.

JSON  
{  
  "message": "Specialty added successfully",  
  "data": { "id": "s6" }  
}

### **POST /hospital/specialties/{specialtyId}/staff**

**Description:** Add a staff member to a specialty.

JSON  
{  
  "message": "Staff member added successfully",  
  "data": { "id": "d5" }  
}

### **GET /hospital/icu-rooms**

**Description:** Fetch ICU room and bed inventory.

JSON  
{  
  "data": \[  
    {  
      "id": "icu1",  
      "roomNumber": "ICU-101",  
      "bedId": "BED-A",  
      "floorWing": "2nd Floor \- West",  
      "equipmentList": \["Ventilator", "Heart Monitor", "IV Pump"\],  
      "status": "available",  
      "assignedPatient": null  
    }  
  \]  
}

### **GET /hospital/incubators**

**Description:** Fetch incubator inventory.

JSON  
{  
  "data": \[  
    {  
      "id": "inc1",  
      "unitId": "NICU-001",  
      "model": "Dräger Isolette",  
      "wingSection": "NICU \- East Wing",  
      "monitoringType": \["Heart Rate", "SpO2", "Temperature"\],  
      "status": "ready",  
      "temperature": 36.5,  
      "humidity": 65  
    }  
  \]  
}

### **GET /hospital/lab-tests**

**Description:** Fetch the hospital's internal lab test catalog.

JSON  
{  
  "data": \[  
    {  
      "id": "lt1",  
      "name": "CBC (Complete Blood Count)",  
      "price": 150.0,  
      "category": "Hematology",  
      "homeCollectionAvailable": true,  
      "preparationInstructions": "Fasting for 8 hours",  
      "estimatedResultTime": "24 hours"  
    }  
  \]  
}

### **GET /hospital/radiology-services**

**Description:** Fetch the hospital's radiology service catalog.

JSON  
{  
  "data": \[  
    {  
      "id": "rs1",  
      "name": "MRI Brain",  
      "price": 2500.0,  
      "category": "MRI",  
      "contrastAgentRequired": true,  
      "preparationInstructions": "Remove all metallic items",  
      "estimatedDuration": "45 minutes"  
    }  
  \]  
}

### **GET /hospital/settings**

**Description:** Fetch the hospital's application settings.

JSON  
{  
  "data": {  
    "pushNotificationsEnabled": true,  
    "languageCode": "en",  
    "newBookingsEnabled": true,  
    "appointmentRemindersEnabled": true,  
    "emergencyRequestsEnabled": true,  
    "paymentNotificationsEnabled": false  
  }  
}

### **PUT /hospital/settings**

**Description:** Update the hospital's application settings.

JSON  
{  
  "message": "Settings updated successfully"  
}

## **6\. Laboratory & Radiology API**

### **GET /lab/dashboard**

**Description:** Fetch the lab/radiology center dashboard data.

JSON  
{  
  "data": {  
    "todayBookings": 24,  
    "todayBookingsProgress": "18/24",  
    "pendingRequests": 5,  
    "homeVisits": 3,  
    "upcomingBooking": {  
      "patientName": "Ahmed Hassan",  
      "testCategory": "Hematology",  
      "appointmentTime": "10:30 AM",  
      "appointmentDate": "2026-07-14"  
    },  
    "specialOffer": {  
      "title": "Summer Checkup Package",  
      "description": "Full body checkup at discounted rates",  
      "discount": "30%"  
    }  
  }  
}

### **GET /lab/services**

**Description:** Fetch the list of lab/radiology services.

| Parameter | Type | Required | Description |
| :---- | :---- | :---- | :---- |
| category | string | No | Filter by category |
| search | string | No | Search by service name |

JSON  
{  
  "data": \[  
    {  
      "id": "ls1",  
      "name": "CBC (Complete Blood Count)",  
      "price": 120.0,  
      "isHomeCollectionAvailable": true,  
      "turnaroundTime": "24 hours",  
      "category": "Hematology"  
    }  
  \]  
}

### **POST /lab/services**

**Description:** Add a new lab/radiology service.

JSON  
{  
  "message": "Service added successfully",  
  "data": { "id": "ls5" }  
}

### **GET /lab/branches**

**Description:** Fetch the lab's branch locations.

JSON  
{  
  "data": \[  
    {  
      "id": "br1",  
      "name": "New Cairo Branch",  
      "address": "5th Settlement, Street 90",  
      "phone": "01234567890",  
      "workingHours": "Sat-Thu: 8 AM \- 10 PM",  
      "isHomeCollectionAvailable": true,  
      "imageUrl": "https://cdn.rojeta.com/branches/newcairo.jpg"  
    }  
  \]  
}

### **POST /lab/branches**

**Description:** Add a new branch location.

JSON  
{  
  "message": "Branch added successfully",  
  "data": { "id": "br3" }  
}

### **GET /lab/home-visits/config**

**Description:** Fetch the home visit service configuration.

JSON  
{  
  "data": {  
    "isServiceVisible": true,  
    "serviceAreas": \[  
      {  
        "id": "sa1",  
        "name": "New Cairo",  
        "radiusKm": 15,  
        "techsAvailable": 4  
      }  
    \],  
    "collectionFee": 50.0,  
    "minimumBookingAmount": 200.0,  
    "weekdaySlots": \[  
      {  
        "id": "ts1",  
        "time": "08:00",  
        "amPm": "AM",  
        "status": "active"  
      }  
    \],  
    "weekendSlots": \[  
      {  
        "id": "ts5",  
        "time": "09:00",  
        "amPm": "AM",  
        "status": "active"  
      }  
    \]  
  }  
}

### **PUT /lab/home-visits/config**

**Description:** Update the home visit service configuration.

JSON  
{  
  "message": "Home visit configuration updated successfully"  
}

### **GET /lab/profile**

**Description:** Fetch the lab/radiology center profile summary.

JSON  
{  
  "data": {  
    "labName": "City Lab",  
    "licenseNo": "LAB-2023-994821",  
    "branchesCount": 12,  
    "scansCount": 45,  
    "iconUrl": "https://cdn.rojeta.com/labs/citylab.png"  
  }  
}

### **GET /lab/info**

**Description:** Fetch detailed laboratory information.

JSON  
{  
  "data": {  
    "labName": "City Lab",  
    "licenseNumber": "LAB-2023-994821",  
    "primaryContactEmail": "info@citylab.com",  
    "phoneNumber": "01234567890",  
    "laboratoryDescription": "Full-service diagnostic laboratory",  
    "isVerified": true,  
    "verificationYear": "2023"  
  }  
}

### **PUT /lab/info**

**Description:** Update the lab's detailed information.

JSON  
{  
  "message": "Lab information updated successfully"  
}

### **GET /lab/working-hours**

**Description:** Fetch the lab's working hours and special date overrides.

JSON  
{  
  "data": {  
    "standardWeek": \[  
      {  
        "dayName": "Saturday",  
        "isClosed": false,  
        "openingTime": "08:00 AM",  
        "closingTime": "10:00 PM"  
      }  
    \],  
    "upcomingOverrides": \[  
      {  
        "id": "so1",  
        "dateStr": "2026-07-20",  
        "eventName": "Eid Al-Adha",  
        "isClosed": true,  
        "openingTime": null,  
        "closingTime": null  
      }  
    \]  
  }  
}

### **PUT /lab/working-hours**

**Description:** Update the lab's working hours and special overrides.

JSON  
{  
  "message": "Working hours updated successfully"  
}

### **GET /lab/settings**

**Description:** Fetch the lab's general and notification settings.

JSON  
{  
  "data": {  
    "emailNotifications": true,  
    "bookingUpdates": true,  
    "pushNotifications": true,  
    "appointmentReminders": true,  
    "marketingEmails": false,  
    "language": "English"  
  }  
}

### **PUT /lab/settings**

**Description:** Update the lab's general/notification settings.

JSON  
{  
  "message": "Settings updated successfully"  
}

## **7\. Nursing API**

### **GET /nursing/dashboard**

**Description:** Fetch the nursing office dashboard data.

JSON  
{  
  "data": {  
    "todaysBookings": 8,  
    "bookingsTrend": "+12% from yesterday",  
    "activeNurses": 5,  
    "activeNursesDesc": "3 on duty, 2 available",  
    "totalEarnings": 12500.0,  
    "earningsDesc": "This week",  
    "upcomingBookings": \[  
      {  
        "dateMonth": "JUL",  
        "dateDay": "14",  
        "patientName": "Ahmed Hassan",  
        "serviceType": "Wound Care",  
        "time": "10:00 AM"  
      }  
    \]  
  }  
}

### **GET /nursing/services**

**Description:** Fetch the list of nursing services offered.

JSON  
{  
  "data": \[  
    {  
      "title": "Home Nursing Care",  
      "subtitle": "Professional in-home nursing assistance",  
      "durationTag": "12 Hours",  
      "bulletPoints": \[  
        "Medication management",  
        "Vital signs monitoring",  
        "Wound care"  
      \]  
    }  
  \]  
}

### **POST /nursing/services**

**Description:** Add a new nursing service.

JSON  
{  
  "message": "Service added successfully"  
}

### **GET /nursing/staff**

**Description:** Fetch the nursing staff list and availability status.

JSON  
{  
  "data": {  
    "stats": {  
      "totalStaff": 12,  
      "available": 5,  
      "onDuty": 4,  
      "onLeave": 3  
    },  
    "staff": \[  
      {  
        "id": "ns1",  
        "name": "Nurse Fatma",  
        "imageUrl": "https://cdn.rojeta.com/staff/fatma.png",  
        "gender": "Female",  
        "status": "available",  
        "skills": \["Wound Care", "IV Therapy", "Vital Signs"\],  
        "yearsOfExperience": 5  
      }  
    \]  
  }  
}

### **POST /nursing/staff**

**Description:** Add a new nurse to the staff list.

JSON  
{  
  "message": "Staff member added successfully",  
  "data": { "id": "ns5" }  
}

### **GET /nursing/profile**

**Description:** Fetch the nursing office high-level profile summary.

JSON  
{  
  "data": {  
    "id": "no\_001",  
    "name": "City Nursing Office",  
    "licenseNumber": "NRS-2024-001234",  
    "avatarUrl": "https://cdn.rojeta.com/nursing/cityoffice.png",  
    "servicesCount": 6,  
    "nursesCount": 12  
  }  
}

### **GET /nursing/office-info**

**Description:** Fetch detailed nursing office information.

JSON  
{  
  "data": {  
    "officeName": "City Nursing Office",  
    "licenseNumber": "NRS-2024-001234",  
    "email": "info@citynursing.com",  
    "phone": "01234567890",  
    "description": "Professional home nursing services",  
    "verificationStatus": "verified"  
  }  
}

### **PUT /nursing/office-info**

**Description:** Update detailed nursing office information.

JSON  
{  
  "message": "Office info updated successfully"  
}

### **GET /nursing/working-hours**

**Description:** Fetch the nursing office working hours.

JSON  
{  
  "data": {  
    "days": \[  
      {  
        "day": "Saturday",  
        "isOpen": true,  
        "openTime": "08:00 AM",  
        "closeTime": "10:00 PM"  
      }  
    \]  
  }  
}

### **PUT /nursing/working-hours**

**Description:** Update the nursing office working hours.

JSON  
{  
  "message": "Working hours updated successfully"  
}

### **GET /nursing/settings**

**Description:** Fetch the nursing office application settings.

JSON  
{  
  "data": {  
    "emailNotifications": true,  
    "bookingUpdates": true,  
    "pushNotifications": true,  
    "appointmentReminders": true,  
    "marketingEmails": false,  
    "language": "English"  
  }  
}

### **PUT /nursing/settings**

**Description:** Update the nursing office application settings.

JSON  
{  
  "message": "Settings updated successfully"  
}

## **8\. Common Error Responses**

The API adheres to standard HTTP status codes and responds with the following unified error structure:

### **400 Bad Request**

JSON  
{  
  "error": "VALIDATION\_ERROR",  
  "message": "Invalid request body",  
  "details": \[  
    { "field": "status", "message": "Invalid status value" }  
  \]  
}

### **401 Unauthorized**

JSON  
{  
  "error": "UNAUTHORIZED",  
  "message": "Invalid or expired token"  
}

### **403 Forbidden**

JSON  
{  
  "error": "FORBIDDEN",  
  "message": "You do not have permission to access this resource"  
}

### **404 Not Found**

JSON  
{  
  "error": "NOT\_FOUND",  
  "message": "Resource not found"  
}

### **500 Internal Server Error**

JSON  
{  
  "error": "INTERNAL\_ERROR",  
  "message": "An unexpected error occurred"  
}  
