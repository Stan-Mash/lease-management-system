# Chabrin Lease Management System - User Guide

<p align="center">
  <img src="public/chabrin-logo.png" alt="Chabrin Agencies Logo" width="200">
</p>

<p align="center">
  <strong>Version 2.0</strong> &nbsp;|&nbsp; Chabrin Agencies &nbsp;|&nbsp; February 2026
</p>

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Dashboard Overview](#3-dashboard-overview)
4. [Client Management](#4-client-management)
5. [Property Management](#5-property-management)
6. [Unit Management](#6-unit-management)
7. [Tenant Management](#7-tenant-management)
8. [Lease Agreements](#8-lease-agreements)
9. [Lease Approval Workflow](#9-lease-approval-workflow)
10. [Digital Signing & OTP Verification](#10-digital-signing--otp-verification)
11. [Document Vault](#11-document-vault)
12. [Lease Templates](#12-lease-templates)
13. [Lawyer Tracking](#13-lawyer-tracking)
14. [Zone Management](#14-zone-management)
15. [Dashboards & Analytics](#15-dashboards--analytics)
16. [User & Role Management](#16-user--role-management)
17. [System Monitoring (Pulse)](#17-system-monitoring-pulse)
18. [PDF Generation & QR Verification](#18-pdf-generation--qr-verification)
19. [Notifications & Email](#19-notifications--email)
20. [Keyboard Shortcuts & Tips](#20-keyboard-shortcuts--tips)
21. [Troubleshooting](#21-troubleshooting)

---

## 1. Introduction

The **Chabrin Lease Management System** is a comprehensive, web-based platform designed for Chabrin Agencies to manage the complete lifecycle of property leases -- from drafting through approval, digital signing, document archival, and renewal.

### Key Capabilities

- **End-to-end lease lifecycle** -- Draft, approve, sign, activate, and renew leases
- **Digital signing** -- Tenants sign leases online via OTP-verified signature pads
- **Document vault** -- Centralized storage for scanned and digital lease documents
- **Zone-based hierarchy** -- Organize properties and staff across geographic zones
- **Role-based access control** -- Eight distinct roles with granular permissions
- **Real-time dashboards** -- Portfolio analytics, revenue charts, and performance metrics
- **PDF generation** -- Professional lease agreements with QR codes for verification
- **Audit trails** -- Full traceability of every action on documents and leases

### Technology Stack

| Component          | Technology                        |
|--------------------|-----------------------------------|
| Backend Framework  | Laravel 12 (PHP 8.4)             |
| Admin Panel        | Filament v4                       |
| Database           | PostgreSQL 16                     |
| Frontend           | Tailwind CSS, Alpine.js, Livewire |
| Real-time Updates  | Livewire polling                  |
| PDF Generation     | DomPDF / Blade templates          |
| SMS Integration    | Africa's Talking                  |
| Digital Signatures | Signature Pad JS                  |

---

## 2. Getting Started

### 2.1 Logging In

1. Navigate to your system URL (e.g., `https://leases.chabrinagencies.co.ke/admin/login`)
2. Enter your **email address** and **password**
3. Click **Sign in**

> **Default Super Admin Accounts:**
>
> | Name     | Email                                    | Password        |
> |----------|------------------------------------------|-----------------|
> | Stanely  | stanely.macharia@chabrinagencies.co.ke   | Chabrin@2026!   |
> | Kimathi  | kimathiw@chabrinagencies.co.ke           | password         |
> | Mark     | mark.nyaga@chabrinagencies.co.ke         | Chabrin@2026!   |

```
Screenshot placeholder: Login page
┌─────────────────────────────────────────────┐
│                                             │
│            🏢 Chabrin Agencies              │
│         Lease Management System             │
│                                             │
│   ┌─────────────────────────────────┐       │
│   │ Email                           │       │
│   └─────────────────────────────────┘       │
│   ┌─────────────────────────────────┐       │
│   │ Password                        │       │
│   └─────────────────────────────────┘       │
│                                             │
│         [ Sign in ]                         │
│                                             │
└─────────────────────────────────────────────┘
```

### 2.2 Navigation Structure

After logging in, you will see the **admin panel** powered by Filament. The sidebar contains:

| Navigation Group      | Items                                                  |
|-----------------------|--------------------------------------------------------|
| **Lease Portfolio**   | Portfolio Overview, Lease Agreements, Document Vault   |
| **People**            | Clients, Tenants                                       |
| **Assets**            | Properties, Units                                      |
| **Operations**        | Lawyers, Lease Templates                               |
| **Administration**    | Users, Roles, Permission Dashboard                     |
| **Dashboards**        | Company Dashboard, Zone Dashboard, Field Officer Dashboard |
| **Monitoring**        | System Pulse, Document Audit, Print Log, Approval Tracking |

```
Screenshot placeholder: Main navigation sidebar
┌──────────────────┬──────────────────────────────────────────────┐
│                  │                                              │
│  📁 Lease        │    Main Content Area                         │
│   Portfolio      │                                              │
│   ├ Portfolio    │    ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│   ├ Lease Agr.   │    │ Active   │ │ Pending  │ │ Draft    │   │
│   └ Doc Vault    │    │ Leases   │ │ Approval │ │ Leases   │   │
│                  │    │   142    │ │    23    │ │    8     │   │
│  👥 People       │    └──────────┘ └──────────┘ └──────────┘   │
│   ├ Clients      │                                              │
│   └ Tenants      │                                              │
│                  │                                              │
│  🏢 Assets       │                                              │
│   ├ Properties   │                                              │
│   └ Units        │                                              │
│                  │                                              │
│  ⚙ Admin        │                                              │
│   ├ Users        │                                              │
│   └ Roles        │                                              │
│                  │                                              │
└──────────────────┴──────────────────────────────────────────────┘
```

### 2.3 Global Search

Use the **search bar** at the top of the page to instantly find leases across the entire system. The global search indexes:

- Lease reference numbers
- Unit codes
- Tenant names, national IDs, and phone numbers
- Property names and reference numbers
- Client names

Simply start typing and matching results appear with tenant name, property, unit, and status.

---

## 3. Dashboard Overview

### 3.1 Lease Portfolio Overview

The **Portfolio Overview** (`/admin/lease-portfolio`) is the default landing page. It provides a bird's-eye view of your entire lease portfolio.

**Statistics Cards:**

| Card               | Description                                          |
|---------------------|------------------------------------------------------|
| Total Leases        | Total number of leases in the system                 |
| Active Leases       | Currently active and running leases                  |
| Pending Approval    | Leases awaiting landlord/client approval             |
| Draft Leases        | Leases still being prepared                          |
| Expiring Soon       | Active leases expiring within 30 days                |
| Expired Leases      | Leases that have passed their end date               |

**Additional Sections:**

- **Document Statistics** -- Total documents, pending review, approved, linked/unlinked counts
- **Recent Leases** -- Last 5 updated leases with tenant, property, unit info
- **Recent Documents** -- Last 5 uploaded documents
- **Monthly Trends** -- 6-month charts for lease creation and document uploads
- **Quick Actions** -- Buttons to create a lease, upload documents, or view review queue

```
Screenshot placeholder: Lease Portfolio dashboard
┌─────────────────────────────────────────────────────────────────┐
│  Lease Portfolio                                                │
│                                                                 │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐  │
│  │ Total   │ │ Active  │ │ Pending │ │ Draft   │ │Expiring │  │
│  │  298    │ │  142    │ │   23    │ │    8    │ │   12    │  │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘  │
│                                                                 │
│  ┌────────────────────────────┐ ┌────────────────────────────┐  │
│  │  Recent Leases             │ │  Document Statistics        │  │
│  │  ─────────────────         │ │  ─────────────────          │  │
│  │  LSE-ABC123 | John Doe    │ │  Total: 456                 │  │
│  │  LSE-DEF456 | Jane Smith  │ │  Pending Review: 12         │  │
│  │  LSE-GHI789 | Mark Oloo   │ │  Approved: 389              │  │
│  └────────────────────────────┘ └────────────────────────────┘  │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Monthly Trends (6 months)                               │    │
│  │  ▓▓▓▓▓  ▓▓▓▓▓▓  ▓▓▓▓▓▓▓  ▓▓▓▓▓▓▓▓  ▓▓▓▓▓  ▓▓▓▓▓▓▓   │    │
│  │  Sep     Oct      Nov       Dec        Jan     Feb       │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Client Management

Clients (formerly "Landlords") are the property owners who engage Chabrin Agencies for lease management.

### 4.1 Viewing Clients

Navigate to **Clients** in the sidebar. The list view shows all registered clients with search and filter capabilities.

### 4.2 Creating a Client

1. Click **New Client**
2. Fill in the required information:

| Field Group       | Fields                                                              |
|-------------------|---------------------------------------------------------------------|
| **Identity**      | Names, Second Name, Last Name, Title, Gender                       |
| **Contact**       | Mobile Number, Email Address, Address (1, 2, 3)                    |
| **Tax & ID**      | VAT Number, PIN Number, National ID, Passport Number               |
| **Banking**       | Bank ID, Account Name, Account Number                              |
| **Registration**  | Registered Date, Reference Number, UID                             |
| **Lease Info**    | Property ID, Unit ID, Lease Start Date, Lease Years, Rent Amount   |
| **Documents**     | Photo upload, Document attachments                                  |

3. Click **Create** to save

### 4.3 Viewing Client Details

Click on any client row to view their full profile with all associated information.

---

## 5. Property Management

Properties represent the physical buildings or land parcels managed by Chabrin Agencies.

### 5.1 Viewing Properties

Navigate to **Properties** in the sidebar. The list view provides sortable, searchable columns.

### 5.2 Creating a Property

1. Click **New Property**
2. Fill in the details:

| Field                 | Description                                           |
|-----------------------|-------------------------------------------------------|
| Property Name         | Display name for the property                         |
| Reference Number      | Unique property reference code                        |
| Client ID             | The owning client                                     |
| Zone / Zone Area      | Geographic zone assignment                            |
| LR Number             | Land Registry number                                  |
| Description           | Property description and details                      |
| Lat/Long              | GPS coordinates                                       |
| Usage Type            | Residential, Commercial, etc.                         |
| Acquisition Date      | When the property was acquired                        |
| Field Officer          | Assigned field officer                                |
| Zone Supervisor       | Assigned zone supervisor                              |
| Zone Manager          | Assigned zone manager                                 |
| Bank Account          | Linked bank account for rent collection               |

3. Click **Create** to save

---

## 6. Unit Management

Units are individual rentable spaces within properties (e.g., apartment rooms, office suites, shop spaces).

### 6.1 Creating a Unit

1. Navigate to **Units** > **New Unit**
2. Fill in:

| Field                      | Description                                      |
|----------------------------|--------------------------------------------------|
| Unit Number                | Unique identifier within the property            |
| Unit Code                  | System-wide unique code                          |
| Unit Name                  | Display name                                     |
| Property ID                | Parent property                                  |
| Client ID                  | Owning client                                    |
| Zone                       | Geographic zone                                  |
| Unit Type / Category       | Classification (studio, 1BR, office, etc.)       |
| Usage Type                 | Residential or Commercial                        |
| Rent Amount                | Monthly rent in KES                              |
| VAT-able                   | Whether rent is subject to VAT                   |
| Occupancy Status           | Vacant, Occupied, Maintenance                    |
| Initial Water Meter        | Starting water meter reading                     |

3. Click **Create**

---

## 7. Tenant Management

Tenants are the individuals or entities who occupy rented units.

### 7.1 Creating a Tenant

1. Navigate to **Tenants** > **New Tenant**
2. Fill in tenant information (same column structure as clients):

| Field Group       | Fields                                                              |
|-------------------|---------------------------------------------------------------------|
| **Identity**      | Names, Second Name, Last Name, Title, Gender, National ID, Passport |
| **Contact**       | Mobile Number, Email Address, Address                               |
| **Tax**           | VAT Number, PIN Number                                              |
| **Banking**       | Bank ID, Account Name, Account Number                               |
| **Lease Info**    | Property, Unit, Lease Start Date, Rent Amount, Escalation Rate      |
| **Preferences**   | Preferred Messages Language                                         |

3. Click **Create**

---

## 8. Lease Agreements

Lease Agreements are the core of the system. They track the full lifecycle of a rental contract.

### 8.1 Lease List View

Navigate to **Lease Agreements** in the sidebar. The badge on the navigation item shows the count of active leases.

**Table Columns:**

| Column           | Description                                               |
|------------------|-----------------------------------------------------------|
| Date Created     | When the lease was created                                |
| Ref No.          | Auto-generated reference (e.g., `LSE-A3BX7KM2NP`)       |
| Lease Ref        | Manual lease reference number                             |
| Unit Code        | The assigned unit code                                    |
| Tenant           | Tenant's full name                                        |
| Property         | Property name                                             |
| Zone             | Assigned zone (badge)                                     |
| Field Officer    | Assigned field officer                                    |
| Type             | Residential (Micro), Residential (Macro), Commercial      |
| Status           | Draft, Pending Approval, Approved, Active, etc. (badge)   |
| Rent             | Monthly rent in KES                                       |

**Available Filters:**

- Status (multi-select: Draft, Pending, Approved, Active, Terminated, etc.)
- Lease Type (Commercial, Residential Micro/Macro)
- Zone
- Field Officer
- Property
- Client
- Zone Manager
- Date Created range
- Start Date range
- End Date range
- Expiring within 90 days (toggle)

```
Screenshot placeholder: Lease Agreements list
┌──────────────────────────────────────────────────────────────────────┐
│  Lease Agreements                              [ + Create ]          │
│                                                                      │
│  🔍 Search...                          Filters ▼                     │
│                                                                      │
│  ┌──────────┬─────────────┬───────────┬──────────┬─────────┬──────┐  │
│  │ Ref No.  │ Tenant      │ Property  │ Zone     │ Status  │ Rent │  │
│  ├──────────┼─────────────┼───────────┼──────────┼─────────┼──────┤  │
│  │ LSE-A3BX │ John Doe    │ Sunset Pk │ Zone A   │ Active  │ 45K  │  │
│  │ LSE-K7QM │ Jane Smith  │ Hill View │ Zone B   │ Draft   │ 32K  │  │
│  │ LSE-P2NR │ Mark Oloo   │ River Est │ Zone A   │ Pending │ 28K  │  │
│  │ LSE-W9TY │ Sarah Wanja │ Oak Ridge │ Zone C   │ Active  │ 55K  │  │
│  └──────────┴─────────────┴───────────┴──────────┴─────────┴──────┘  │
│                                                                      │
│  Showing 1-10 of 298                        « 1 2 3 ... 30 »        │
└──────────────────────────────────────────────────────────────────────┘
```

### 8.2 Creating a Lease

1. Click **Create** from the Lease Agreements list
2. Complete the form:

**Lease Details:**

| Field               | Description                                                    |
|---------------------|----------------------------------------------------------------|
| Reference Number    | Auto-generated (`LSE-XXXXXXXXXX`), read-only                  |
| Workflow State      | Initial state: Draft, Active, or Terminated                   |
| Source              | "Chabrin Generated" or "Landlord Provided"                    |
| Lease Type          | Residential Major, Residential Micro, or Commercial           |
| Template            | Optional custom template (filtered by lease type)             |
| Signing Method      | **Digital** (email/SMS link) or **Physical** (field officer)  |

**Property & Tenant:**

| Field         | Description                                                     |
|---------------|-----------------------------------------------------------------|
| Tenant        | Searchable dropdown of registered tenants                       |
| Unit          | Searchable dropdown -- auto-fills rent, property, and client    |

**Financials:**

| Field          | Description                                    |
|----------------|------------------------------------------------|
| Monthly Rent   | Amount in KES (auto-filled from unit)          |
| Deposit Amount | Security deposit in KES                        |
| Start Date     | Lease commencement date                        |
| End Date       | Lease expiry date (optional)                   |

**Guarantor Section (optional):**

Toggle **Requires Guarantor** to add one or more guarantors with:
- Full Name, National ID, Phone, Email
- Relationship (Parent, Spouse, Sibling, Employer, Friend, Other)
- Guarantee Amount (defaults to deposit)
- Signed status and notes

3. Click **Create** to save the lease in Draft state

```
Screenshot placeholder: Create Lease form
┌─────────────────────────────────────────────────────────────┐
│  Create Lease Agreement                                      │
│                                                              │
│  Reference Number: [LSE-A3BX7KM2NP]  (auto-generated)      │
│                                                              │
│  Workflow State:  [ Draft         ▼]                        │
│  Source:          [ Chabrin Generated ▼]                     │
│  Lease Type:      [ Residential Major ▼]                    │
│  Template:        [ Default Template  ▼]                    │
│  Signing Method:  [ Digital Signing   ▼]                    │
│                                                              │
│  ── Property & Tenant ──                                     │
│  Tenant:  [ 🔍 Search tenants...     ▼]                    │
│  Unit:    [ 🔍 Search units...       ▼]                    │
│                                                              │
│  ── Financials ──                                            │
│  Monthly Rent:   [ Ksh 45,000    ]                          │
│  Deposit:        [ Ksh 90,000    ]                          │
│  Start Date:     [ 01/03/2026    ]                          │
│  End Date:       [ 28/02/2028    ]                          │
│                                                              │
│  ☐ Requires Guarantor                                        │
│                                                              │
│                          [ Cancel ] [ Create ]               │
└─────────────────────────────────────────────────────────────┘
```

### 8.3 Viewing a Lease

Click any lease row (or the eye icon) to open the **View** page. This shows all lease details and provides action buttons in the header.

**Header Actions (contextual based on lease state):**

| Action              | Visible When                    | Description                              |
|---------------------|---------------------------------|------------------------------------------|
| Edit                | Draft or Received               | Modify lease details                     |
| Request Approval    | Draft, no pending approval      | Send to client/landlord for review       |
| Approve Lease       | Pending Landlord Approval       | Approve with optional comments           |
| Reject Lease        | Pending Landlord Approval       | Reject with reason and comments          |
| Resolve Dispute     | Disputed                        | Resolve a disputed lease                 |
| Cancel Disputed     | Disputed                        | Cancel a disputed lease                  |
| Send Digital Link   | Approved + Digital signing      | Email/SMS the signing link to tenant     |
| Print Lease         | Approved + Physical signing     | Mark as printed for field delivery       |
| Preview PDF         | Always                          | Open PDF preview in new tab              |
| Download PDF        | Always                          | Download the lease as PDF                |
| Send via Email      | Tenant has email                | Email lease document to tenant           |
| Upload Documents    | User can manage leases          | Upload scanned physical documents        |

```
Screenshot placeholder: View Lease page with action buttons
┌─────────────────────────────────────────────────────────────────┐
│  LSE-A3BX7KM2NP                                                │
│                                                                  │
│  [Edit] [Request Approval] [Preview PDF] [Download PDF]         │
│  [Send via Email] [Upload Documents]                             │
│                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────┐             │
│  │ Lease Details         │  │ Tenant Information   │             │
│  │ ─────────────         │  │ ─────────────────    │             │
│  │ Type: Commercial      │  │ Name: John Doe       │             │
│  │ Status: Draft         │  │ Phone: +254712345678 │             │
│  │ Source: Chabrin       │  │ Email: john@email.com│             │
│  │ Rent: KES 45,000     │  │ ID: 12345678         │             │
│  │ Start: 01/03/2026    │  │                      │             │
│  │ End: 28/02/2028      │  │                      │             │
│  └──────────────────────┘  └──────────────────────┘             │
│                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────┐             │
│  │ Property & Unit       │  │ Guarantors           │             │
│  │ ──────────────        │  │ ──────────           │             │
│  │ Property: Sunset Park │  │ No guarantors        │             │
│  │ Unit: 5A              │  │                      │             │
│  │ Zone: Zone A          │  │                      │             │
│  └──────────────────────┘  └──────────────────────┘             │
└─────────────────────────────────────────────────────────────────┘
```

### 8.4 Lease Workflow States

Leases progress through the following states:

```
  ┌──────────┐     ┌───────────────────┐     ┌──────────┐
  │  DRAFT   │────>│ PENDING LANDLORD  │────>│ APPROVED │
  │          │     │    APPROVAL       │     │          │
  └──────────┘     └───────────────────┘     └──────────┘
       │                    │                      │
       │              (Rejected)              ┌────┴────┐
       │                    │                 │         │
       │                    v           Digital    Physical
       │              ┌──────────┐     Signing    Signing
       │              │  DRAFT   │        │         │
       │              │ (revised)│        v         v
       │              └──────────┘   ┌─────────┐ ┌──────────┐
       │                             │  SENT   │ │ PRINTED  │
       │                             │ DIGITAL │ │          │
       │                             └────┬────┘ └──────────┘
       │                                  │           │
       │                                  v           │
       │                           ┌────────────┐    │
       │                           │ PENDING OTP│    │
       │                           └─────┬──────┘    │
       │                                 │           │
       │                                 v           │
       │                          ┌─────────────┐   │
       │                          │   TENANT    │   │
       │                          │   SIGNED    │   │
       │                          └──────┬──────┘   │
       │                                 │          │
       │                                 v          v
       │                          ┌─────────────────┐
       └─────────────────────────>│     ACTIVE      │
                                  └────────┬────────┘
                                           │
                              ┌────────────┼────────────┐
                              v            v            v
                        ┌──────────┐ ┌──────────┐ ┌──────────┐
                        │TERMINATED│ │ EXPIRED  │ │CANCELLED │
                        └──────────┘ └──────────┘ └──────────┘
                              │
                              v
                        ┌──────────┐
                        │ DISPUTED │
                        └──────────┘
```

---

## 9. Lease Approval Workflow

### 9.1 Requesting Approval

1. Open a lease in **Draft** state
2. Click **Request Approval**
3. Confirm the action -- an email notification is sent to the client/landlord

### 9.2 Client/Landlord Approval Portal

Clients can approve or reject leases through:

- **Web Portal** at `/landlord/{landlordId}/approvals`
- **API Endpoints** for programmatic access

The portal shows:
- List of pending, approved, and rejected leases
- Statistics cards (pending count, approved count, rejected count)
- Detailed lease view with property, tenant, rent, and dates

### 9.3 Approving a Lease (Admin)

From the lease view page:

1. Click **Approve Lease**
2. Optionally add approval comments
3. Click **Approve** -- the lease moves to "Approved" state and the tenant is notified

### 9.4 Rejecting a Lease

1. Click **Reject Lease**
2. Enter a **rejection reason** (required) and optional comments
3. Click **Reject** -- the lease returns to "Draft" state for revision

### 9.5 Approval Tracking Dashboard

Navigate to **Approval Tracking** (`/admin/approval-tracking`) to monitor:
- Pending and overdue approvals
- Landlord approval activity
- Total KES value of pending leases
- Approval timeline

```
Screenshot placeholder: Approval Tracking dashboard
┌─────────────────────────────────────────────────────────────┐
│  Approval Tracking                                           │
│                                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │
│  │ Pending  │ │ Approved │ │ Rejected │ │ Overdue  │       │
│  │   23     │ │   156    │ │    8     │ │    3     │       │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │
│                                                              │
│  Landlord Activity                                           │
│  ────────────────                                            │
│  ABC Holdings      -- 5 pending, 12 approved                │
│  XYZ Properties    -- 2 pending, 8 approved                 │
│  Sunset Investors  -- 1 pending, 15 approved                │
└─────────────────────────────────────────────────────────────┘
```

---

## 10. Digital Signing & OTP Verification

### 10.1 Sending a Digital Signing Link

Once a lease is **Approved** and the signing method is **Digital**:

1. Click **Send Digital Link** from the lease view
2. The system sends an email/SMS to the tenant with a unique signing URL

### 10.2 Tenant Signing Portal

The tenant receives a link to `/tenant/sign/{lease}`. The signing process is a **3-step wizard**:

**Step 1: Verify Identity**
- Tenant enters their phone number
- System sends a **4-digit OTP** via SMS
- Tenant enters the OTP within the 10-minute validity window

**Step 2: Review Lease**
- The full lease document is displayed for review
- Tenant can scroll through all terms and conditions
- A PDF preview is available with print functionality

**Step 3: Sign**
- Tenant draws their signature on a **digital signature pad**
- Must check consent checkboxes:
  - "I have read and understood the lease agreement"
  - "I agree to the terms and conditions"
- Click **Submit Signature** to complete

```
Screenshot placeholder: Tenant digital signing portal
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│  ── Step 1 ──  ── Step 2 ──  ── Step 3 ──                  │
│   Verify ID      Review         Sign                        │
│  ━━━━━━━━━━    ─────────     ─────────                      │
│                                                              │
│  Verify Your Identity                                        │
│                                                              │
│  Enter the OTP sent to your phone:                           │
│                                                              │
│  ┌────┐ ┌────┐ ┌────┐ ┌────┐                               │
│  │  4 │ │  7 │ │  2 │ │  9 │                               │
│  └────┘ └────┘ └────┘ └────┘                               │
│                                                              │
│  Code expires in: 08:42                                      │
│                                                              │
│              [ Verify OTP ]                                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

```
Screenshot placeholder: Digital signature pad
┌─────────────────────────────────────────────────────────────┐
│  Step 3: Sign the Lease                                      │
│                                                              │
│  Draw your signature below:                                  │
│  ┌─────────────────────────────────────────────────┐        │
│  │                                                   │        │
│  │           ~~~~ Signature Here ~~~~                │        │
│  │                                                   │        │
│  └─────────────────────────────────────────────────┘        │
│                                  [ Clear ] [ Undo ]          │
│                                                              │
│  ☑ I have read and understood the lease agreement            │
│  ☑ I agree to the terms and conditions                       │
│                                                              │
│              [ Submit Signature ]                             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 10.3 After Signing

Once signed, the tenant sees a **confirmation page** with:
- Lease reference number
- Tenant name, property, unit details
- Rent amount and lease dates
- Signature date and GPS location
- Current lease status
- "What's Next" instructions

---

## 11. Document Vault

The **Document Vault** (`/admin/documents`) is a centralized repository for all lease-related documents.

### 11.1 Document List

The vault table includes:

| Column          | Description                                |
|-----------------|--------------------------------------------|
| Title           | Document title                             |
| Zone            | Assigned zone                              |
| Property        | Associated property                        |
| Type            | Lease agreement, amendment, ID copy, etc.  |
| Status          | Pending Review, Approved, Rejected         |
| Quality         | Excellent, Good, Fair, Poor                |
| Size            | Human-readable file size                   |
| Uploaded By     | Name of uploader                           |
| Uploaded        | Date and relative time                     |
| Linked Lease    | Associated lease reference (if linked)     |

**Filters:**
- Status, Quality, Zone, Source, Document Type
- Unlinked Documents (toggle)
- Quality Issues (toggle)
- My Uploads (toggle)
- Date range

### 11.2 Uploading Documents

**Single Upload:**
1. Navigate to **Document Vault** > **Upload**
2. Select Zone and Property
3. Choose Document Type and Year
4. Enter a title and description
5. Upload the file (PDF, DOC, DOCX, JPG, PNG, TIFF -- max 25MB)
6. Rate the document quality
7. Click **Create**

**Bulk Upload:**
Navigate to the **Upload Center** for batch uploading multiple documents at once with drag-and-drop support.

**From Lease View:**
Use the **Upload Documents** button on any lease to directly attach documents.

### 11.3 Document Review Queue

Navigate to **Document Vault** > **Review** to see all documents pending review.

Reviewers can:
- **Approve** documents individually or in bulk
- **Reject** documents with reasons
- **Link** approved documents to leases
- **Preview** documents inline (PDF/images displayed in a slide-over panel)

### 11.4 Document Features

- **Inline Preview** -- View PDFs and images directly in the browser via a slide-over modal
- **Version History** -- Track all versions of a document with file hashes
- **Audit Trail** -- Full log of who viewed, edited, approved, or downloaded each document
- **Integrity Verification** -- SHA hash verification for document authenticity
- **Auto-compression** -- Uploaded images are automatically compressed to save storage
- **Bulk Link to Lease** -- Select multiple approved documents and link them to a lease at once

```
Screenshot placeholder: Document Vault list with preview
┌──────────────────────────────────────────────────────────────────┐
│  Document Vault                    12 pending review             │
│                                                                  │
│  [ All ] [ My Uploads ] [ Upload Center ] [ Review Queue ]       │
│                                                                  │
│  ┌──────────────┬────────┬──────────┬──────────┬──────────────┐  │
│  │ Title        │ Type   │ Status   │ Quality  │ Uploaded     │  │
│  ├──────────────┼────────┼──────────┼──────────┼──────────────┤  │
│  │ Lease - Doe  │ Lease  │ Approved │ Good     │ Feb 15, 2026 │  │
│  │ ID Copy J.S  │ ID     │ Pending  │ Fair     │ Feb 14, 2026 │  │
│  │ Amendment #2 │ Amend. │ Approved │ Excellent│ Feb 12, 2026 │  │
│  └──────────────┴────────┴──────────┴──────────┴──────────────┘  │
│                                                                  │
│                        [ Preview ] [ Download ] [ Link ]         │
└──────────────────────────────────────────────────────────────────┘
```

---

## 12. Lease Templates

Lease templates define the layout and content of generated lease agreements.

### 12.1 Template Types

| Template Type        | Description                                    |
|----------------------|------------------------------------------------|
| Residential Major    | Full residential lease with detailed terms      |
| Residential Micro    | Simplified residential lease                    |
| Commercial           | Commercial/business lease agreement             |

### 12.2 Managing Templates

Navigate to **Lease Templates** in the sidebar.

- **List Templates** -- View all active and inactive templates
- **Create Template** -- Create a new lease template with Blade content
- **Edit Template** -- Modify template content and styles
- **Preview** -- Live preview of the template as HTML or PDF

### 12.3 Template Variables

Templates use Blade syntax with variables like:
- `{{ $lease->tenant->names }}` -- Tenant name
- `{{ $lease->property->property_name }}` -- Property name
- `{{ $lease->monthly_rent }}` -- Rent amount
- `{{ $lease->start_date }}` -- Lease start date
- `{{ $lease->unit->unit_number }}` -- Unit number

### 12.4 Template Preview

Use the **Preview** page (`/admin/lease-templates/{id}`) to see how a template renders with:
- Raw Blade content view
- Rendered HTML preview
- PDF export preview

---

## 13. Lawyer Tracking

The system tracks external lawyers involved in lease preparation.

### 13.1 Managing Lawyers

Navigate to **Lawyers** to:
- Add new lawyers with contact information
- Track which leases are with which lawyer
- Record when leases are sent to and received from lawyers

### 13.2 Lawyer Workflow

1. **Send to Lawyer** -- From the lease view, assign a lease to a lawyer for review
2. **Track Progress** -- Monitor the lease's status with the lawyer
3. **Receive from Lawyer** -- Record when the reviewed lease is returned

---

## 14. Zone Management

Zones divide Chabrin's property portfolio into geographic areas for management purposes.

### 14.1 Zone Structure

Each zone has:
- A **Zone Manager** who oversees all properties in the zone
- **Field Officers** who handle on-the-ground lease activities
- Assigned **Properties** and their **Units**

### 14.2 Zone-Based Access Control

- **Super Admins / System Admins** -- See all zones
- **Zone Managers** -- See only their assigned zone's data
- **Field Officers** -- See only leases assigned to them within their zone

### 14.3 Zone Dashboard

Navigate to **Zone Dashboard** (or it auto-redirects for zone managers) to see:
- Zone-specific lease statistics
- Field officer performance within the zone
- Lease status breakdown by zone
- Revenue charts filtered by zone

```
Screenshot placeholder: Zone Dashboard
┌─────────────────────────────────────────────────────────────┐
│  Zone A Performance                                          │
│                                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                    │
│  │ Active   │ │ Pending  │ │ Revenue  │                    │
│  │  67      │ │   12     │ │ KES 3.2M │                    │
│  └──────────┘ └──────────┘ └──────────┘                    │
│                                                              │
│  Field Officer Performance                                   │
│  ─────────────────────────                                   │
│  James Mwangi   -- 23 active leases, 2 pending              │
│  Peter Ochieng  -- 18 active leases, 5 pending              │
│  Mary Akinyi    -- 26 active leases, 5 pending              │
│                                                              │
│  ┌──────────────────────────────────────────────┐           │
│  │  Lease Status Chart                           │           │
│  │  ■ Active: 67  ■ Pending: 12  ■ Draft: 4    │           │
│  └──────────────────────────────────────────────┘           │
└─────────────────────────────────────────────────────────────┘
```

---

## 15. Dashboards & Analytics

### 15.1 Company Dashboard

**Access:** Super Admin, System Admin

**Widgets:**
- **Date Range Filter** -- Filter all widgets by custom date range
- **Lease Statistics** -- Total, active, pending, terminated counts
- **Zone Performance** -- Comparison across all zones
- **Lease Status Chart** -- Visual breakdown of leases by status
- **Revenue Chart** -- Monthly revenue trends

### 15.2 Zone Dashboard

**Access:** Super Admin, System Admin, Zone Manager

- Same widgets as Company Dashboard but filtered to a specific zone
- Field officer performance metrics
- Zone-specific lease and revenue data

### 15.3 Field Officer Dashboard

**Access:** All staff (scoped to own data for field officers)

- Assigned lease statistics
- Lease status breakdown for assigned leases
- Personal performance metrics

### 15.4 Document Audit Dashboard

**Access:** `/admin/document-audit-dashboard`

- Document activity timeline
- Category breakdowns (by type, zone, status)
- Top uploaders
- Upload trends over time

### 15.5 Permission Dashboard

**Access:** `/admin/permission-dashboard`

- **Role-Permission Matrix** -- Visual grid showing which roles have which permissions
- User counts per role
- Recent role changes audit trail
- Permission delegation tracking

### 15.6 Print Log Report

**Access:** `/admin/print-log-report`

- Track all lease printing activity
- Analytics on print frequency and distribution

---

## 16. User & Role Management

### 16.1 Managing Users

Navigate to **Users** to:
- View all system users
- Create new users with name, email, username, and password
- Assign roles to users
- Block/unblock user accounts

### 16.2 Roles & Permissions

The system uses **Spatie Permission** with 8 predefined roles:

| Role                       | Key Permissions                                                    |
|----------------------------|--------------------------------------------------------------------|
| **Super Admin**            | Full access to everything                                          |
| **System Admin**           | All operations except user deletion; manages settings & templates  |
| **Property Manager**       | Full lease lifecycle, landlord lease editing, template management   |
| **Asst. Property Manager** | Same as PM but cannot manage system settings                       |
| **Zone Manager**            | Zone-scoped lease management, field officer oversight              |
| **Senior Field Officer**    | Create/update leases, delivery recording, digital signing          |
| **Field Officer**           | View assigned leases, check-in/out, record delivery/signatures    |
| **Audit**                   | Read-only access to all data, reports, and audit logs             |

### 16.3 Role Management

Navigate to **Roles** to:
- View all roles and their permission sets
- Create custom roles
- Edit role permissions
- View role details with assigned users

### 16.4 Permission Categories

| Category             | Permissions                                                      |
|----------------------|------------------------------------------------------------------|
| **Leases**           | View, create, update, delete, print, transition state            |
| **Landlord Leases**  | Edit landlord leases, upload landlord documents                  |
| **Approvals**        | Approve, reject, request approval                                |
| **Digital Signing**  | Send digital signing, verify OTP                                 |
| **Field Operations** | Checkout, check-in, record delivery, physical signatures         |
| **Lawyers**          | Manage lawyers, send to/receive from lawyer                     |
| **Distribution**     | Distribute copies                                                |
| **Entity Management**| View/manage tenants, landlords, properties, units               |
| **Zones**            | View/manage zones, view zone reports                            |
| **Users**            | View/manage users, assign roles                                 |
| **Reports**          | View reports, audit logs, export reports                        |
| **System**           | Manage settings, manage templates                               |
| **Dashboards**       | View dashboard, company dashboard, zone dashboard, FO dashboard |

---

## 17. System Monitoring (Pulse)

### 17.1 System Pulse Page

Navigate to **System Pulse** (`/admin/system-pulse`) for real-time system health monitoring:

- **Health Checks** -- Server status, database connectivity
- **Queue Breakdown** -- Pending, processing, failed jobs
- **Database Statistics** -- Connection pool, query performance
- **Live Polling** -- Auto-refreshes every 30 seconds

### 17.2 Laravel Pulse Dashboard

Navigate to `/pulse` for the full Laravel Pulse dashboard:

- **Server Stats** -- CPU, memory, disk usage
- **SMS Balance** -- Africa's Talking SMS credit balance
- **CHIPS Database** -- Financial system connection health
- **Usage Metrics** -- Active users, request volume
- **Queue Monitor** -- Job processing status
- **Slow Queries** -- Database performance tracking
- **Exceptions** -- Error tracking and frequency
- **Slow Requests** -- API/page load performance

### 17.3 Custom Monitoring Cards

| Card                | Description                                               |
|---------------------|-----------------------------------------------------------|
| SMS Balance         | Real-time Africa's Talking SMS balance with threshold alerts |
| CHIPS Database      | Connection health to the CHIPS financial system with uptime |

---

## 18. PDF Generation & QR Verification

### 18.1 Generating Lease PDFs

From any lease view:
- Click **Preview PDF** to view in browser
- Click **Download PDF** to save locally
- Use **Send via Email** to email the PDF to the tenant

### 18.2 PDF Templates

Three professional PDF templates are available:

1. **Residential Major** -- Full-featured residential lease (A4 format)
2. **Residential Micro** -- Simplified residential lease
3. **Commercial** -- Business/commercial lease agreement

Each PDF includes:
- Company letterhead and branding
- Lease serial number
- QR code for verification
- Party details (landlord/tenant)
- Premises description
- Terms and conditions
- Signature blocks

### 18.3 QR Code Verification

Every generated lease PDF contains a **QR code** that links to a verification page.

**How it works:**
1. Anyone can scan the QR code on a printed lease
2. The code links to `/verify/lease?ref=LSE-XXXXXXXXXX`
3. The verification page shows:
   - Document authenticity confirmation (or failure)
   - Lease serial number
   - Cryptographic hash verification
   - Security notice

```
Screenshot placeholder: QR verification page
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│  ✓ Document Verified                                        │
│                                                              │
│  This lease agreement is authentic and was generated by      │
│  the Chabrin Lease Management System.                        │
│                                                              │
│  Serial Number: CHA-2026-000142                              │
│  Reference: LSE-A3BX7KM2NP                                  │
│  Hash: a3b7c9e2...                                           │
│                                                              │
│  ⚠ Security Notice: This verification only confirms that    │
│  the document was generated by this system. Always verify    │
│  signatures independently.                                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 19. Notifications & Email

### 19.1 Email Notifications

The system sends email notifications for:

| Event                     | Recipient         | Description                               |
|---------------------------|-------------------|-------------------------------------------|
| Approval Requested        | Client/Landlord   | Lease sent for approval                   |
| Lease Approved            | Tenant            | Lease has been approved                   |
| Lease Rejected            | Creator           | Lease rejected with reason                |
| Digital Signing Link      | Tenant            | Link to sign the lease online             |
| Lease Document Email      | Tenant            | Lease PDF sent via email                  |
| Rent Escalation (Landlord)| Client            | Notification of upcoming rent increase    |
| Rent Escalation (Tenant)  | Tenant            | Notification of rent increase             |

### 19.2 SMS Notifications

Via **Africa's Talking** integration:
- OTP codes for tenant identity verification
- Lease signing reminders
- Custom SMS notifications

### 19.3 In-App Notifications

Filament's notification system provides toast-style notifications for:
- Successful actions (create, update, approve)
- Error messages
- Warning messages (e.g., missing data)
- Information messages

---

## 20. Keyboard Shortcuts & Tips

### 20.1 Navigation Tips

- Use the **sidebar** to quickly navigate between sections
- The sidebar **collapses** for more screen space -- hover to expand
- Use **global search** (top bar) for instant lease lookup
- **Column toggles** let you show/hide table columns per your preference

### 20.2 Table Features

- **Sort** -- Click any column header to sort
- **Search** -- Use the search bar above tables
- **Filter** -- Click "Filters" to open the filter panel (supports 3-column layout)
- **Pagination** -- Choose 10, 25, or 50 records per page
- **Copy** -- Click the copy icon next to reference numbers to copy to clipboard
- **Toggle Columns** -- Click the columns icon to show/hide columns

### 20.3 Productivity Tips

1. **Quick Lease Creation** -- Selecting a unit auto-fills rent, property, and client
2. **Bulk Document Actions** -- Select multiple documents and approve or link to a lease in one action
3. **Date Range Filtering** -- Use the date range widget on dashboards to focus on specific periods
4. **Expiring Leases Toggle** -- Use the "Expiring within 90 days" filter to proactively manage renewals
5. **Global Search** -- Search across lease references, tenant names, phone numbers, and property names simultaneously

---

## 21. Troubleshooting

### 21.1 Common Issues

| Issue                           | Solution                                                      |
|---------------------------------|---------------------------------------------------------------|
| Cannot log in                   | Verify email and password; check if account is blocked        |
| Cannot see leases               | Check your role -- field officers only see assigned leases    |
| Cannot create leases            | Auditors and field officers cannot create leases              |
| Approval button not visible     | Lease must be in correct state (Draft or Pending Approval)    |
| PDF preview is blank            | Ensure lease has all required fields filled in                |
| OTP not received                | Check tenant's phone number; verify SMS balance in Pulse      |
| Document upload fails           | Check file size (max 25MB) and accepted types                |
| Zone dashboard shows no data    | Ensure your user account has a zone assigned                  |

### 21.2 Role-Based Access Issues

If you cannot access a feature, verify your role has the required permission:

- **Cannot access Company Dashboard?** -- Requires Super Admin or System Admin
- **Cannot manage templates?** -- Requires Property Manager or higher
- **Cannot approve documents?** -- Requires Super Admin, System Admin, or IT Officer role
- **Cannot delete leases?** -- Requires Super Admin, System Admin, or Property Manager

### 21.3 Getting Help

For technical support:
- Contact your system administrator
- Check the **System Pulse** dashboard for system health status
- Review the **Document Audit Dashboard** for recent activity logs

---

<p align="center">
  <strong>Chabrin Agencies</strong><br>
  Lease Management System v2.0<br>
  <em>Built with Laravel, Filament, and PostgreSQL</em>
</p>
