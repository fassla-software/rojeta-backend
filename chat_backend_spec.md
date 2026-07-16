# Chat & Support API Specification for Backend Team

This document outlines the required endpoints, HTTP methods, headers, request structures, and response schemas to integrate the Patient application's Chat and Support feature.

---

## Base Configuration
- **Base URL**: `https://rojeta.fassla.net/api/v1`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `Authorization: Bearer <JWT_TOKEN>` (Required for all endpoints)

---

## 1. Doctor Conversations Endpoints

### 1.1 List Active Conversations
Retrieve a list of all active conversations between the current patient and doctors.

- **URL**: `/chat/conversations`
- **Method**: `GET`
- **Response Status**: `200 OK`
- **Response Body**:
```json
[
  {
    "id": "conv-uuid-1",
    "name": "Dr. Ahmed Hassan",
    "image_url": "https://i.pravatar.cc/150?u=doc1",
    "specialty": "Cardiologist",
    "last_message": "تم إرسال التقرير الطبي، يرجى مراجعته",
    "last_message_time": "10:30 AM",
    "unread_count": 2,
    "is_online": true,
    "type": "doctor"
  },
  {
    "id": "conv-uuid-2",
    "name": "Dr. Fatima Ali",
    "image_url": "https://i.pravatar.cc/150?u=doc2",
    "specialty": "Dermatologist",
    "last_message": "موعدك القادم يوم الأحد الساعة 2 مساءً",
    "last_message_time": "Yesterday",
    "unread_count": 1,
    "is_online": false,
    "type": "doctor"
  }
]
```

### 1.2 Get Conversation History
Retrieve the history of messages for a specific conversation.

- **URL**: `/chat/messages`
- **Method**: `GET`
- **Query Parameters**:
  - `conversation_id` (string, required): The ID of the conversation.
- **Response Status**: `200 OK`
- **Response Body**:
```json
[
  {
    "id": "msg-uuid-1",
    "sender_id": "doctor-uuid-1",
    "receiver_id": "patient-uuid",
    "content": "مرحباً! كيف يمكنني مساعدتك اليوم؟",
    "timestamp": "10:00 AM",
    "is_sent_by_me": false,
    "status": "read"
  },
  {
    "id": "msg-uuid-2",
    "sender_id": "patient-uuid",
    "receiver_id": "doctor-uuid-1",
    "content": "أهلاً دكتور، أريد استشارة بخصوص نتائج التحاليل",
    "timestamp": "10:05 AM",
    "is_sent_by_me": true,
    "status": "read"
  }
]
```

### 1.3 Send Message to Doctor
Send a message inside an active conversation.

- **URL**: `/chat/send-message`
- **Method**: `POST`
- **Request Body**:
```json
{
  "conversation_id": "conv-uuid-1",
  "content": "سأرسلها الآن"
}
```
- **Response Status**: `201 Created` / `200 OK`
- **Response Body**:
```json
{
  "id": "msg-uuid-3",
  "sender_id": "patient-uuid",
  "receiver_id": "doctor-uuid-1",
  "content": "سأرسلها الآن",
  "timestamp": "10:07 AM",
  "is_sent_by_me": true,
  "status": "sent"
}
```

---

## 2. Dedicated Support Chat Endpoints

Support is designed as a single, global persistent conversation per user. There is no `conversation_id` required for these endpoints as they map directly to the authenticated user.

### 2.1 Get Support Messages
Retrieve all messages sent/received in the user's Support channel.

- **URL**: `/support/messages`
- **Method**: `GET`
- **Response Status**: `200 OK`
- **Response Body**:
```json
[
  {
    "id": "supp-msg-1",
    "sender_id": "support-agent-uuid",
    "receiver_id": "patient-uuid",
    "content": "مرحباً! كيف يمكنني مساعدتك اليوم؟",
    "timestamp": "10:00 AM",
    "is_sent_by_me": false,
    "status": "read"
  },
  {
    "id": "supp-msg-2",
    "sender_id": "patient-uuid",
    "receiver_id": "support-agent-uuid",
    "content": "مرحباً، أريد الاستفسار عن كود الخصم",
    "timestamp": "10:05 AM",
    "is_sent_by_me": true,
    "status": "read"
  }
]
```

### 2.2 Send Message to Support
Post a new message to the Support channel.

- **URL**: `/support/send-message`
- **Method**: `POST`
- **Request Body**:
```json
{
  "content": "سأرسلها الآن"
}
```
- **Response Status**: `201 Created` / `200 OK`
- **Response Body**:
```json
{
  "id": "supp-msg-3",
  "sender_id": "patient-uuid",
  "receiver_id": "support-agent-uuid",
  "content": "سأرسلها الآن",
  "timestamp": "10:07 AM",
  "is_sent_by_me": true,
  "status": "sent"
}
```

---

## 3. Additional Requirements (Backend Gaps)

> The sections below document missing behavior needed for implementation. Existing endpoints in sections 1–2 stay as the patient app contract; this section clarifies backend rules, extra endpoints, and alignment notes.

### 3.1 Auth Alignment
- Project uses **Laravel Sanctum** (`auth:sanctum`), not a JWT package.
- `Authorization: Bearer <TOKEN>` where token is the Sanctum plain-text token from login.
- All chat/support endpoints require an authenticated **patient** (`role:patient`) unless otherwise noted for doctor/agent APIs.

### 3.2 Standard Response Envelope
Backend should wrap payloads with the existing `ApiResponse` format (even if samples above show raw arrays):

**Success (data):**
```json
{
  "data": { }
}
```

**Success (paginated list):**
```json
{
  "data": [ ],
  "meta": {
    "page": 1,
    "limit": 30,
    "total": 120
  }
}
```

**Success (message + data):**
```json
{
  "message": "Message sent successfully",
  "data": { }
}
```

**Error:**
```json
{
  "error": "VALIDATION_ERROR",
  "message": "Invalid request body",
  "details": {
    "content": ["The content field is required."]
  }
}
```

### 3.3 Timestamps
- Store and return timestamps as **ISO 8601** (e.g. `2026-07-16T10:30:00Z`).
- Display strings like `"10:30 AM"` / `"Yesterday"` are **frontend formatting only**, not API contract for new fields.
- Prefer also exposing machine-readable fields alongside display fields if the app still needs them:
  - `last_message_at` (ISO 8601)
  - `created_at` (ISO 8601)

### 3.4 Conversation Creation Rules
A doctor conversation is **not** created by listing alone. Define when it is created:

| Trigger | Behavior |
|---------|----------|
| Paid chat (`doctor_profiles.chat_price`) | After successful payment for chat consultation with that doctor, create (or reopen) one conversation between patient and doctor. |
| After booking (optional) | Optionally auto-create a conversation linked to a `booking_id` when a booking is confirmed. |
| Start chat explicitly | `POST /chat/conversations` with `doctor_id` (+ optional `booking_id`) creates conversation if none exists, or returns the existing one. |

**Rules:**
- One active conversation per `(patient_id, doctor_id)` pair (reuse existing rather than duplicating).
- Conversation must belong to the authenticated patient.
- If chat requires payment and payment is missing/unpaid → `402` or `403` with clear error code (e.g. `CHAT_PAYMENT_REQUIRED`).

#### 3.4.1 Start / Open Conversation
- **URL**: `/chat/conversations`
- **Method**: `POST`
- **Request Body**:
```json
{
  "doctor_id": "doctor-uuid-1",
  "booking_id": "booking-uuid-optional"
}
```
- **Response Status**: `201 Created` (new) or `200 OK` (existing)
- **Response Body** (`data`):
```json
{
  "id": "conv-uuid-1",
  "doctor_id": "doctor-uuid-1",
  "booking_id": null,
  "name": "Dr. Ahmed Hassan",
  "image_url": "https://...",
  "specialty": "Cardiologist",
  "unread_count": 0,
  "is_online": false,
  "type": "doctor",
  "created_at": "2026-07-16T10:00:00Z"
}
```

### 3.5 Mark as Read
Without these, `unread_count` cannot stay correct.

#### 3.5.1 Mark Doctor Conversation as Read
- **URL**: `/chat/mark-read`
- **Method**: `POST`
- **Request Body**:
```json
{
  "conversation_id": "conv-uuid-1"
}
```
- **Response Status**: `200 OK`
- **Response Body**:
```json
{
  "message": "Conversation marked as read",
  "data": {
    "conversation_id": "conv-uuid-1",
    "unread_count": 0
  }
}
```
- Marks all messages in that conversation where the current user is the receiver as `read`.

#### 3.5.2 Mark Support as Read
- **URL**: `/support/mark-read`
- **Method**: `POST`
- **Request Body**: none (maps to authenticated user support channel)
- **Response Status**: `200 OK`
- **Response Body**:
```json
{
  "message": "Support chat marked as read",
  "data": {
    "unread_count": 0
  }
}
```

### 3.6 Pagination
Apply to message history endpoints (and optionally conversations list).

**Query parameters (GET):**
- `page` (integer, default `1`)
- `limit` (integer, default `30`, max `100`)

**Example:** `GET /chat/messages?conversation_id=conv-uuid-1&page=1&limit=30`

**Paginated response shape:**
```json
{
  "data": [ ],
  "meta": {
    "page": 1,
    "limit": 30,
    "total": 85
  }
}
```

Messages should be returned **oldest → newest** within a page (or document clearly if reverse-chronological; frontend must know).

### 3.7 Validation Rules
| Field | Rules |
|-------|--------|
| `content` | required, string, min 1, max 5000 characters (after trim) |
| `conversation_id` | required (doctor chat), UUID, must exist, must belong to current patient |
| `doctor_id` | required on create conversation, UUID, user must be role `doctor` |
| `booking_id` | optional, UUID, must belong to current patient if provided |
| Attachments (if enabled) | max 5 files, max 5MB each, mime: `image/jpeg`, `image/png`, `image/webp`, `application/pdf` |

Empty / whitespace-only `content` → `VALIDATION_ERROR` (400).

Optional rate limit suggestion: max ~30 messages / minute / user.

### 3.8 Error Responses
| HTTP | `error` code | When |
|------|----------------|------|
| `401` | `UNAUTHORIZED` | Missing/invalid token |
| `403` | `FORBIDDEN` | Not a patient / not participant in conversation |
| `404` | `NOT_FOUND` | Conversation or doctor not found |
| `400` | `VALIDATION_ERROR` | Invalid body/query |
| `402` or `403` | `CHAT_PAYMENT_REQUIRED` | Chat not paid / not entitled |
| `429` | `RATE_LIMITED` | Too many messages |
| `500` | `INTERNAL_ERROR` | Unexpected server error |

### 3.9 Attachments (Optional Phase)
Text-only is enough for v1. If attachments are needed:

- Send via `multipart/form-data` on send-message endpoints:
  - `content` (optional if file present)
  - `attachments[]` (files)
- Message object extra fields:
```json
{
  "attachments": [
    {
      "id": "att-uuid-1",
      "url": "https://...",
      "mime_type": "image/jpeg",
      "file_name": "report.jpg",
      "size": 204800
    }
  ]
}
```

### 3.10 Realtime & Presence
- **v1 (recommended):** client **polling** every 5–15s on open chat screens for new messages / unread.
- **v2 (optional):** WebSockets (Laravel Reverb / Pusher) broadcasting `MessageSent`, `MessageRead`, `UserOnline`.
- `is_online` requires a presence mechanism (heartbeat or websocket presence). If not implemented yet, return `false` consistently and document as stub.
- Message `status` lifecycle: `sent` → `delivered` → `read` (minimum: `sent` and `read`).

### 3.11 Doctor & Support Agent Side (Required for Replies)
Patient endpoints alone are not enough; someone must reply.

#### Doctor (auth: doctor)
- `GET /doctor/chat/conversations` — list patient conversations
- `GET /doctor/chat/messages?conversation_id=` — history
- `POST /doctor/chat/send-message` — send reply (`conversation_id`, `content`)
- `POST /doctor/chat/mark-read` — mark patient messages read

#### Support Agent / Admin
- Support channel is one persistent thread per user (as in section 2).
- Agent APIs (admin panel / support role):
  - `GET /admin/support/threads` — list users with support activity + unread
  - `GET /admin/support/messages?user_id=` — that user's support history
  - `POST /admin/support/send-message` — `{ "user_id", "content" }`
  - `POST /admin/support/mark-read` — `{ "user_id" }`
- `receiver_id` / `sender_id` for support agents may be a system support user UUID or agent user id; document the chosen model in implementation.

### 3.12 Relation to Existing `profile/help-support`
- Existing stub: `POST /profile/help-support` (ticket-style, no real chat DB).
- **Decision:** Support chat in this doc is the live messaging channel.
- `profile/help-support` should either:
  1. Be deprecated in favor of `/support/*`, or
  2. Create a support ticket record **and** optionally seed the first support message into the support chat.
- Do not leave both as conflicting “support” entry points without documenting which the app uses.

### 3.13 Suggested Data Model (Backend)
**conversations**
- `id` (uuid)
- `type` (`doctor` | `support`)
- `patient_id` (uuid → users)
- `doctor_id` (nullable uuid → users, for doctor chats)
- `booking_id` (nullable uuid → bookings)
- `last_message_at`
- `created_at` / `updated_at`

**messages**
- `id` (uuid)
- `conversation_id` (uuid)
- `sender_id` (uuid → users)
- `receiver_id` (uuid → users)
- `content` (text, nullable if attachment-only)
- `status` (`sent` | `delivered` | `read`)
- `read_at` (nullable)
- `created_at` / `updated_at`

**message_attachments** (optional)
- `id`, `message_id`, `url`, `mime_type`, `file_name`, `size`

**Notes:**
- Support: one `conversations` row per patient with `type = support` and `doctor_id = null`.
- Index: `(patient_id, doctor_id)` unique where type is doctor; `(patient_id)` unique where type is support.

### 3.14 Status Code Consistency
For send endpoints, use a single success code:
- Prefer **`201 Created`** when a new message row is created.
- Use **`200 OK`** for mark-read and for returning an already-existing conversation on start.

### 3.15 Implementation Checklist
- [ ] Sanctum + `role:patient` (and doctor/admin as needed)
- [ ] Conversation create/open rules + `chat_price` / payment entitlement
- [ ] List conversations, get messages, send message (sections 1–2)
- [ ] Mark as read (doctor + support)
- [ ] Pagination on message lists
- [ ] Validation + error codes
- [ ] ISO timestamps in storage/API
- [ ] Doctor reply APIs
- [ ] Support agent reply APIs
- [ ] Resolve overlap with `POST profile/help-support`
- [ ] Polling (v1) or websockets (v2)
- [ ] Optional attachments phase
