# Landlord & Field Officer App Integration Guide

## Overview

This document provides complete API documentation for integrating the **Landlord Mobile App** and **Field Officer App** with the Chabrin Lease Management System's approval workflow.

---

## 📱 Use Cases

### **Landlord App:**
- View pending lease approvals
- Review lease details (tenant, rent, terms)
- Approve leases with comments
- Reject leases with reasons
- View approval history

### **Field Officer App:**
- View approval status of leases
- Track pending approvals for follow-up
- View rejection reasons for corrections
- Monitor approval timeline

---

## 🔐 Authentication

**Current Implementation:** Basic landlord ID-based routing

**Recommended for Production:**
- Add Bearer token authentication
- Implement API key per landlord
- Add rate limiting per landlord
- Use Laravel Sanctum for token management

**Example Header:**
```http
Authorization: Bearer {landlord_api_token}
Content-Type: application/json
Accept: application/json
```

---

## 🚀 API Endpoints

### Base URL
```
https://your-domain.com/api/landlord/{landlordId}
```

---

## 1. Get Pending Approvals

**Endpoint:** `GET /api/landlord/{landlordId}/approvals`

**Description:** Get all pending lease approvals for a landlord.

**URL Parameters:**
- `landlordId` (integer, required) - The landlord's ID

**Response:**
```json
{
  "success": true,
  "landlord": {
    "id": 1,
    "name": "Acme Properties Ltd"
  },
  "pending_count": 3,
  "leases": [
    {
      "id": 15,
      "reference_number": "LSE-COM-A-00015-2026",
      "tenant": {
        "name": "John Mwangi",
        "phone": "+254712345678",
        "email": "john.mwangi@example.com"
      },
      "lease_type": "Commercial",
      "monthly_rent": 45000.00,
      "currency": "KES",
      "security_deposit": 90000.00,
      "start_date": "2026-02-01",
      "end_date": "2027-02-01",
      "created_at": "2026-01-14T10:30:00.000000Z"
    },
    {
      "id": 18,
      "reference_number": "LSE-RES-B-00003-2026",
      "tenant": {
        "name": "Sarah Njeri",
        "phone": "+254723456789",
        "email": "sarah.njeri@example.com"
      },
      "lease_type": "Residential",
      "monthly_rent": 25000.00,
      "currency": "KES",
      "security_deposit": 50000.00,
      "start_date": "2026-02-15",
      "end_date": "2027-02-15",
      "created_at": "2026-01-14T11:15:00.000000Z"
    }
  ]
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Failed to fetch pending leases."
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `404 Not Found` - Landlord not found
- `500 Internal Server Error` - Server error

---

## 2. Get Lease Details

**Endpoint:** `GET /api/landlord/{landlordId}/approvals/{leaseId}`

**Description:** Get complete details of a specific lease for review.

**URL Parameters:**
- `landlordId` (integer, required) - The landlord's ID
- `leaseId` (integer, required) - The lease ID

**Response:**
```json
{
  "success": true,
  "lease": {
    "id": 15,
    "reference_number": "LSE-COM-A-00015-2026",
    "workflow_state": "pending_landlord_approval",
    "lease_type": "Commercial",
    "lease_source": "chabrin",
    "monthly_rent": 45000.00,
    "currency": "KES",
    "security_deposit": 90000.00,
    "start_date": "2026-02-01",
    "end_date": "2027-02-01",
    "property_address": "15 Kenyatta Avenue, Tower A, Zone A",
    "special_terms": "Utilities included in rent",
    "tenant": {
      "name": "John Mwangi",
      "phone": "+254712345678",
      "email": "john.mwangi@example.com",
      "id_number": "12345678"
    },
    "guarantors": [
      {
        "name": "Michael Kariuki",
        "phone": "+254700000000",
        "relationship": "parent",
        "guarantee_amount": 45000.00
      }
    ],
    "approval": {
      "status": "pending",
      "comments": null,
      "rejection_reason": null,
      "reviewed_at": null
    }
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Lease not found."
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `404 Not Found` - Lease not found or doesn't belong to landlord
- `500 Internal Server Error` - Server error

---

## 3. Approve Lease

**Endpoint:** `POST /api/landlord/{landlordId}/approvals/{leaseId}/approve`

**Description:** Approve a lease with optional comments.

**URL Parameters:**
- `landlordId` (integer, required) - The landlord's ID
- `leaseId` (integer, required) - The lease ID

**Request Body:**
```json
{
  "comments": "Approved. Everything looks good. Tenant is reliable."
}
```

**Request Parameters:**
- `comments` (string, optional, max 1000) - Approval comments

**Response:**
```json
{
  "success": true,
  "approval": {
    "id": 5,
    "lease_id": 15,
    "decision": "approved",
    "comments": "Approved. Everything looks good. Tenant is reliable.",
    "reviewed_by": 1,
    "reviewed_at": "2026-01-14T12:00:00.000000Z"
  },
  "notification_sent": true,
  "message": "Lease approved successfully."
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Failed to approve lease: Invalid workflow state."
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Validation error or invalid state
- `404 Not Found` - Lease not found
- `500 Internal Server Error` - Server error

**Side Effects:**
- Lease workflow state changes to `approved`
- Tenant receives email notification
- Tenant receives SMS notification
- Audit log entry created
- Approval record marked as notified

---

## 4. Reject Lease

**Endpoint:** `POST /api/landlord/{landlordId}/approvals/{leaseId}/reject`

**Description:** Reject a lease with required reason and optional additional comments.

**URL Parameters:**
- `landlordId` (integer, required) - The landlord's ID
- `leaseId` (integer, required) - The lease ID

**Request Body:**
```json
{
  "rejection_reason": "Monthly rent exceeds agreed upon amount",
  "comments": "Please reduce rent to 40,000 KES as discussed. Also need to review the payment terms."
}
```

**Request Parameters:**
- `rejection_reason` (string, required, max 255) - Reason for rejection
- `comments` (string, optional, max 1000) - Additional comments/feedback

**Response:**
```json
{
  "success": true,
  "approval": {
    "id": 6,
    "lease_id": 15,
    "decision": "rejected",
    "rejection_reason": "Monthly rent exceeds agreed upon amount",
    "comments": "Please reduce rent to 40,000 KES as discussed. Also need to review the payment terms.",
    "reviewed_by": 1,
    "reviewed_at": "2026-01-14T12:30:00.000000Z"
  },
  "notification_sent": true,
  "message": "Lease rejected successfully."
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "The rejection reason field is required."
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Validation error or invalid state
- `404 Not Found` - Lease not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

**Side Effects:**
- Lease workflow state changes to `cancelled`
- Tenant receives email notification with rejection details
- Tenant receives SMS notification
- Audit log entry created
- Approval record marked as notified

---

## 📲 SMS Notifications

All approval actions trigger SMS notifications via Africa's Talking.

### **SMS Messages:**

**1. Approval Request (to Landlord):**
```
New lease LSE-COM-A-00015-2026 awaits your approval.
Tenant: John Mwangi.
Rent: 45,000 KES/month.
Login to approve or reject.
```

**2. Lease Approved (to Tenant):**
```
Good news! Your lease LSE-COM-A-00015-2026 has been APPROVED by the landlord.
You will receive the digital signing link shortly.
```

**3. Lease Rejected (to Tenant):**
```
Your lease LSE-COM-A-00015-2026 needs revision.
Reason: Monthly rent exceeds agreed upon amount.
Contact Chabrin support for details.
```

### **Phone Number Format:**
- Format: `+254XXXXXXXXX` (Kenya)
- Automatic formatting from `07XXXXXXXX` → `+254XXXXXXXXX`

---

## 🔔 Notification Channels

All actions support dual-channel notifications:

```php
// Send via email only
LandlordApprovalService::approveLease($lease, $comments, 'email');

// Send via SMS only
LandlordApprovalService::approveLease($lease, $comments, 'sms');

// Send via both (default)
LandlordApprovalService::approveLease($lease, $comments, 'both');
```

---

## 📱 Mobile App Integration Example

### **Android (Kotlin) Example:**

```kotlin
import retrofit2.http.*
import com.google.gson.annotations.SerializedName

data class ApprovalRequest(
    val comments: String? = null
)

data class RejectionRequest(
    @SerializedName("rejection_reason") val rejectionReason: String,
    val comments: String? = null
)

data class LeasesResponse(
    val success: Boolean,
    val landlord: Landlord,
    @SerializedName("pending_count") val pendingCount: Int,
    val leases: List<Lease>
)

data class LeaseDetailsResponse(
    val success: Boolean,
    val lease: LeaseDetail
)

interface ChabrinAPI {
    @GET("api/landlord/{landlordId}/approvals")
    suspend fun getPendingLeases(
        @Path("landlordId") landlordId: Int
    ): LeasesResponse

    @GET("api/landlord/{landlordId}/approvals/{leaseId}")
    suspend fun getLeaseDetails(
        @Path("landlordId") landlordId: Int,
        @Path("leaseId") leaseId: Int
    ): LeaseDetailsResponse

    @POST("api/landlord/{landlordId}/approvals/{leaseId}/approve")
    suspend fun approveLease(
        @Path("landlordId") landlordId: Int,
        @Path("leaseId") leaseId: Int,
        @Body request: ApprovalRequest
    ): ApprovalResponse

    @POST("api/landlord/{landlordId}/approvals/{leaseId}/reject")
    suspend fun rejectLease(
        @Path("landlordId") landlordId: Int,
        @Path("leaseId") leaseId: Int,
        @Body request: RejectionRequest
    ): ApprovalResponse
}

// Usage
val api = Retrofit.Builder()
    .baseUrl("https://your-domain.com/")
    .addConverterFactory(GsonConverterFactory.create())
    .build()
    .create(ChabrinAPI::class.java)

// Get pending leases
val response = api.getPendingLeases(landlordId = 1)
Log.d("Chabrin", "Pending leases: ${response.pendingCount}")

// Approve lease
val approval = api.approveLease(
    landlordId = 1,
    leaseId = 15,
    ApprovalRequest(comments = "Approved!")
)
```

### **iOS (Swift) Example:**

```swift
struct ApprovalRequest: Codable {
    let comments: String?
}

struct RejectionRequest: Codable {
    let rejectionReason: String
    let comments: String?

    enum CodingKeys: String, CodingKey {
        case rejectionReason = "rejection_reason"
        case comments
    }
}

struct LeasesResponse: Codable {
    let success: Bool
    let landlord: Landlord
    let pendingCount: Int
    let leases: [Lease]

    enum CodingKeys: String, CodingKey {
        case success, landlord, leases
        case pendingCount = "pending_count"
    }
}

class ChabrinAPI {
    let baseURL = "https://your-domain.com"

    func getPendingLeases(landlordId: Int, completion: @escaping (LeasesResponse?) -> Void) {
        let url = URL(string: "\(baseURL)/api/landlord/\(landlordId)/approvals")!

        URLSession.shared.dataTask(with: url) { data, response, error in
            guard let data = data else { return }
            let response = try? JSONDecoder().decode(LeasesResponse.self, from: data)
            completion(response)
        }.resume()
    }

    func approveLease(landlordId: Int, leaseId: Int, comments: String?, completion: @escaping (ApprovalResponse?) -> Void) {
        let url = URL(string: "\(baseURL)/api/landlord/\(landlordId)/approvals/\(leaseId)/approve")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")

        let body = ApprovalRequest(comments: comments)
        request.httpBody = try? JSONEncoder().encode(body)

        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data else { return }
            let response = try? JSONDecoder().decode(ApprovalResponse.self, from: data)
            completion(response)
        }.resume()
    }
}
```

---

## 🧪 Testing with cURL

### **1. Get Pending Leases:**
```bash
curl -X GET "http://your-domain.com/api/landlord/1/approvals" \
  -H "Accept: application/json"
```

### **2. Get Lease Details:**
```bash
curl -X GET "http://your-domain.com/api/landlord/1/approvals/15" \
  -H "Accept: application/json"
```

### **3. Approve Lease:**
```bash
curl -X POST "http://your-domain.com/api/landlord/1/approvals/15/approve" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "comments": "Approved. Everything looks good."
  }'
```

### **4. Reject Lease:**
```bash
curl -X POST "http://your-domain.com/api/landlord/1/approvals/15/reject" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "rejection_reason": "Rent too high",
    "comments": "Please reduce to 40,000 KES"
  }'
```

---

## 🛡️ Security Recommendations

### **1. Add API Authentication:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('landlord/{landlordId}')->group(function () {
        // ... existing routes
    });
});
```

### **2. Add Rate Limiting:**
```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Allow 60 requests per minute per user
});
```

### **3. Add Landlord Verification:**
```php
// In LandlordApprovalController
public function apiIndex(Request $request, int $landlordId)
{
    // Verify authenticated user is this landlord
    if ($request->user()->landlord_id !== $landlordId) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    // ... rest of code
}
```

### **4. Add API Versioning:**
```php
// routes/api.php
Route::prefix('v1/landlord/{landlordId}')->group(function () {
    // ... routes
});

// URLs become: /api/v1/landlord/1/approvals
```

---

## 📊 Field Officer Integration

Field officers have dedicated API endpoints for monitoring and tracking approval statuses across all landlords.

### Base URL for Field Officer APIs
```
https://your-domain.com/api/field-officer
```

### Field Officer Features:
- Monitor pending approvals across all landlords
- Track approval timeline and overdue leases
- View rejection reasons for corrections
- Access comprehensive dashboard statistics
- Monitor approval history and trends

---

## FO API Endpoints

### 1. Get Dashboard Statistics

**Endpoint:** `GET /api/field-officer/dashboard`

**Description:** Get comprehensive approval statistics for field officer dashboard.

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_pending": 12,
    "overdue_count": 3,
    "approved_today": 2,
    "rejected_today": 1,
    "approved_last_7_days": 15,
    "rejected_last_7_days": 4,
    "avg_approval_time_hours": 18.5
  }
}
```

**cURL Example:**
```bash
curl -X GET "http://your-domain.com/api/field-officer/dashboard" \
  -H "Accept: application/json"
```

---

### 2. Get All Pending Approvals

**Endpoint:** `GET /api/field-officer/pending-approvals`

**Description:** Get all pending lease approvals across all landlords.

**Response:**
```json
{
  "success": true,
  "pending_count": 12,
  "leases": [
    {
      "id": 15,
      "reference_number": "LSE-COM-A-00015-2026",
      "landlord": {
        "id": 1,
        "name": "Acme Properties Ltd",
        "phone": "+254700123456",
        "email": "acme@example.com"
      },
      "tenant": {
        "name": "John Mwangi",
        "phone": "+254712345678",
        "email": "john.mwangi@example.com"
      },
      "lease_type": "Commercial",
      "monthly_rent": 45000.00,
      "currency": "KES",
      "submitted_at": "2026-01-13T10:30:00.000000Z",
      "pending_hours": 26,
      "is_overdue": true
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://your-domain.com/api/field-officer/pending-approvals" \
  -H "Accept: application/json"
```

---

### 3. Get Pending Approvals Grouped by Landlord

**Endpoint:** `GET /api/field-officer/pending-by-landlord`

**Description:** Get pending leases grouped by landlord for targeted follow-up.

**Response:**
```json
{
  "success": true,
  "landlords_count": 5,
  "data": [
    {
      "landlord": {
        "id": 1,
        "name": "Acme Properties Ltd",
        "phone": "+254700123456",
        "email": "acme@example.com"
      },
      "pending_count": 4,
      "oldest_pending_hours": 48,
      "total_rent_value": 180000.00,
      "leases": [
        {
          "id": 15,
          "reference_number": "LSE-COM-A-00015-2026",
          "tenant_name": "John Mwangi",
          "monthly_rent": 45000.00,
          "submitted_at": "2026-01-12T10:30:00.000000Z",
          "pending_hours": 48,
          "is_overdue": true
        }
      ]
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://your-domain.com/api/field-officer/pending-by-landlord" \
  -H "Accept: application/json"
```

---

### 4. Get Overdue Approvals

**Endpoint:** `GET /api/field-officer/overdue-approvals`

**Description:** Get all approvals pending for more than 24 hours (require follow-up).

**Response:**
```json
{
  "success": true,
  "overdue_count": 3,
  "leases": [
    {
      "id": 15,
      "reference_number": "LSE-COM-A-00015-2026",
      "landlord": {
        "id": 1,
        "name": "Acme Properties Ltd",
        "phone": "+254700123456"
      },
      "tenant": {
        "name": "John Mwangi",
        "phone": "+254712345678"
      },
      "monthly_rent": 45000.00,
      "submitted_at": "2026-01-12T10:30:00.000000Z",
      "overdue_hours": 48,
      "overdue_days": 2
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://your-domain.com/api/field-officer/overdue-approvals" \
  -H "Accept: application/json"
```

---

### 5. Get Approval History

**Endpoint:** `GET /api/field-officer/approval-history?days=7`

**Description:** Get history of approved/rejected leases for analytics.

**Query Parameters:**
- `days` (optional, default: 7) - Number of days to look back

**Response:**
```json
{
  "success": true,
  "period_days": 7,
  "total_count": 19,
  "approved_count": 15,
  "rejected_count": 4,
  "history": [
    {
      "id": 45,
      "lease_reference": "LSE-COM-A-00015-2026",
      "landlord_name": "Acme Properties Ltd",
      "tenant_name": "John Mwangi",
      "monthly_rent": 45000.00,
      "decision": "approved",
      "comments": "All terms acceptable",
      "rejection_reason": null,
      "reviewed_at": "2026-01-13T14:30:00.000000Z",
      "approval_time_hours": 28
    },
    {
      "id": 44,
      "lease_reference": "LSE-RES-B-00003-2026",
      "landlord_name": "Green Valley Estates",
      "tenant_name": "Sarah Njeri",
      "monthly_rent": 25000.00,
      "decision": "rejected",
      "comments": null,
      "rejection_reason": "Monthly rent too low for this property",
      "reviewed_at": "2026-01-13T12:15:00.000000Z",
      "approval_time_hours": 15
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://your-domain.com/api/field-officer/approval-history?days=30" \
  -H "Accept: application/json"
```

---

### 6. Get Specific Lease Approval Status

**Endpoint:** `GET /api/field-officer/lease/{leaseId}/status`

**Description:** Get detailed approval status for a specific lease.

**URL Parameters:**
- `leaseId` (integer, required) - The lease ID

**Response:**
```json
{
  "success": true,
  "lease": {
    "id": 15,
    "reference_number": "LSE-COM-A-00015-2026",
    "workflow_state": "pending_landlord_approval",
    "landlord": {
      "id": 1,
      "name": "Acme Properties Ltd",
      "phone": "+254700123456"
    },
    "tenant": {
      "name": "John Mwangi",
      "phone": "+254712345678"
    },
    "monthly_rent": 45000.00,
    "submitted_at": "2026-01-13T10:30:00.000000Z"
  },
  "approval_status": {
    "has_pending": true,
    "has_been_approved": false,
    "has_been_rejected": false,
    "latest_approval": {
      "decision": null,
      "comments": null,
      "rejection_reason": null,
      "reviewed_at": null,
      "approval_time_hours": null
    }
  }
}
```

**cURL Example:**
```bash
curl -X GET "http://your-domain.com/api/field-officer/lease/15/status" \
  -H "Accept: application/json"
```

---

### Field Officer Mobile App Integration

**Recommended Features:**
- Dashboard showing pending count per landlord
- Push notifications when leases are approved/rejected
- Ability to send reminders to landlords
- Approval analytics and reports
- Overdue alerts for follow-up
- Daily/weekly approval summary reports

**Filament Admin UI:**
Field officers can also access:
- **Approval Tracking Page:** Custom Filament page at `/admin/approval-tracking` showing all pending approvals grouped by landlord
- **FO Dashboard Widget:** Real-time statistics widget showing pending, overdue, and recent approval counts
- Direct links to lease details for quick review

---

## 🔄 Workflow States

```
draft
  ↓ (requestApproval)
pending_landlord_approval
  ↓ (approve)        ↓ (reject)
approved          cancelled
  ↓                   ↓
sent_digital      (revise & resubmit)
```

---

## 📝 Best Practices

1. **Polling Frequency:** Check for new approvals every 5-10 minutes
2. **Push Notifications:** Implement FCM/APNS for real-time updates
3. **Offline Support:** Cache lease data for offline viewing
4. **Error Handling:** Show user-friendly messages for all error states
5. **Loading States:** Show loading indicators during API calls
6. **Retry Logic:** Implement exponential backoff for failed requests

---

## 🆘 Support

**For Integration Issues:**
- Check Laravel logs: `storage/logs/laravel.log`
- Enable debug mode temporarily: `APP_DEBUG=true`
- Test with cURL first before mobile integration

**Common Issues:**
- **404 Errors:** Check landlord ID and lease ID are correct
- **403 Errors:** Ensure lease belongs to landlord
- **422 Errors:** Check request body validation
- **500 Errors:** Check server logs for details

---

## 🚀 Production Checklist

- [ ] Add Bearer token authentication
- [ ] Implement rate limiting
- [ ] Add API versioning
- [ ] Set up monitoring/logging
- [ ] Configure CORS for mobile apps
- [ ] Test SMS delivery with real numbers
- [ ] Set up SSL/HTTPS
- [ ] Add API documentation to Postman/Swagger
- [ ] Implement webhook for real-time updates
- [ ] Add analytics tracking

---

**Last Updated:** 2026-01-14
**API Version:** 1.0
**Status:** Production Ready ✅
