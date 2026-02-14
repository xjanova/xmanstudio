# Bug Reporting System Documentation

## Overview

The Bug Reporting System allows users to submit bug reports, misclassification reports, and feature requests from mobile apps and other products. Reports are stored in the database and can be automatically posted to GitHub Issues for tracking.

## Architecture

### Components

1. **Database Tables**
   - `bug_reports` - Main reports table
   - `bug_report_comments` - Comments and discussion threads
   - `bug_report_attachments` - Files, screenshots, logs

2. **Models**
   - `BugReport` - Main model with GitHub integration methods
   - `BugReportComment` - Comment model
   - `BugReportAttachment` - Attachment model

3. **Services**
   - `GitHubIssueService` - Handles GitHub API integration

4. **Controllers**
   - `Api\V1\BugReportController` - REST API endpoints

---

## API Endpoints

Base URL: `/api/v1/bug-reports`

### 1. Submit a Bug Report

**POST** `/api/v1/bug-reports`

Submit a single bug report from a mobile app or client.

**Request Body:**

```json
{
  "product_name": "smschecker",
  "product_version": "1.0.5",
  "report_type": "misclassification",
  "title": "SCB payment SMS misclassified as credit",
  "description": "SMS with 'ชำระเงิน' and 'รับเงิน' is being classified as CREDIT instead of DEBIT",
  "metadata": {
    "transaction_id": 12345,
    "bank": "SCB",
    "amount": "5000.00",
    "detected_type": "CREDIT",
    "correct_type": "DEBIT",
    "issue_type": "WRONG_TRANSACTION_TYPE",
    "original_message": "ชำระเงิน 5,000 บาท ธนาคารรับเงินแล้ว",
    "sender_address": "SCB",
    "timestamp": "2026-02-14T10:30:00Z"
  },
  "device_id": "abc123def456",
  "user_email": "user@example.com",
  "priority": "high",
  "severity": "major",
  "os_version": "Android 14",
  "app_version": "1.0.5",
  "stack_trace": null,
  "additional_info": {
    "device_model": "Samsung Galaxy S24",
    "locale": "th_TH"
  }
}
```

**Response (201):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "new",
    "github_issue_url": null
  },
  "message": "Bug report submitted successfully"
}
```

---

### 2. Submit Batch Reports

**POST** `/api/v1/bug-reports/batch`

Submit multiple bug reports at once (up to 50 per request).

**Request Body:**

```json
{
  "reports": [
    {
      "product_name": "smschecker",
      "report_type": "misclassification",
      "title": "SCB misclassification #1",
      "description": "...",
      "metadata": {...}
    },
    {
      "product_name": "smschecker",
      "report_type": "misclassification",
      "title": "KBANK misclassification #1",
      "description": "...",
      "metadata": {...}
    }
  ]
}
```

**Response (201):**

```json
{
  "success": true,
  "data": {
    "created_count": 2,
    "failed_count": 0,
    "created_ids": [1, 2],
    "failed_reports": []
  },
  "message": "2 reports created, 0 failed"
}
```

---

### 3. Get Bug Report by ID

**GET** `/api/v1/bug-reports/{id}`

Retrieve a specific bug report with comments and attachments.

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "product_name": "smschecker",
    "report_type": "misclassification",
    "title": "SCB payment SMS misclassified as credit",
    "description": "...",
    "status": "new",
    "github_issue_url": "https://github.com/xjanova/xmanstudio/issues/42",
    "github_issue_number": 42,
    "created_at": "2026-02-14T10:30:00.000000Z",
    "comments": [],
    "attachments": []
  }
}
```

---

### 4. List Bug Reports

**GET** `/api/v1/bug-reports`

Get a paginated list of bug reports with filters.

**Query Parameters:**

- `product_name` - Filter by product (e.g., `smschecker`)
- `report_type` - Filter by type (`bug`, `misclassification`, `feature_request`, etc.)
- `status` - Filter by status (`new`, `analyzing`, `confirmed`, `fixed`, etc.)
- `is_analyzed` - Filter by analysis status (`true`/`false`)
- `is_fixed` - Filter by fix status (`true`/`false`)
- `device_id` - Filter by device ID
- `per_page` - Results per page (default: 20, max: 100)

**Example:**

```
GET /api/v1/bug-reports?product_name=smschecker&report_type=misclassification&status=new&per_page=50
```

**Response (200):**

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 50,
    "total": 142
  }
}
```

---

### 5. Get Statistics

**GET** `/api/v1/bug-reports/stats`

Get aggregated statistics about bug reports.

**Query Parameters:**

- `product_name` - Optional: Filter statistics by product

**Response (200):**

```json
{
  "success": true,
  "data": {
    "total": 142,
    "by_status": {
      "new": 45,
      "analyzing": 12,
      "confirmed": 23,
      "fixed": 58,
      "closed": 4
    },
    "by_type": {
      "bug": 67,
      "misclassification": 52,
      "feature_request": 18,
      "crash": 5
    },
    "by_priority": {
      "low": 35,
      "medium": 78,
      "high": 24,
      "critical": 5
    },
    "unanalyzed": 47,
    "unfixed": 84,
    "posted_to_github": 98
  }
}
```

---

### 6. Post Reports to GitHub (Admin Only)

**POST** `/api/v1/bug-reports/post-to-github`

Posts unposted reports to GitHub Issues. Requires authentication.

**Headers:**

```
Authorization: Bearer {access_token}
```

**Query Parameters:**

- `limit` - Max number of reports to post (default: 10, max: 50)

**Response (200):**

```json
{
  "success": true,
  "data": {
    "posted_count": 10,
    "failed_count": 0,
    "success": [
      {
        "report_id": 1,
        "issue_number": 42,
        "issue_url": "https://github.com/xjanova/xmanstudio/issues/42"
      }
    ],
    "failed": []
  },
  "message": "10 reports posted to GitHub"
}
```

---

## Report Types

### 1. Bug Report (`bug`)

General software bugs.

**Example:**
```json
{
  "report_type": "bug",
  "title": "App crashes when clicking Settings",
  "description": "The app crashes every time I click the Settings button",
  "stack_trace": "java.lang.NullPointerException at ..."
}
```

---

### 2. Misclassification Report (`misclassification`)

For SMS parser misclassifications (specific to SmsChecker).

**Required metadata fields:**
- `bank` - Bank name (SCB, KBANK, etc.)
- `amount` - Transaction amount
- `detected_type` - What the app classified it as (CREDIT/DEBIT)
- `correct_type` - What it should be (CREDIT/DEBIT)
- `issue_type` - Type of issue (WRONG_TRANSACTION_TYPE, WRONG_AMOUNT, etc.)
- `original_message` - The original SMS text

**Example:**
```json
{
  "report_type": "misclassification",
  "title": "SCB repayment SMS classified as CREDIT",
  "description": "SMS containing both 'ชำระคืน' and 'รับเงิน' was misclassified",
  "metadata": {
    "bank": "SCB",
    "amount": "5000.00",
    "detected_type": "CREDIT",
    "correct_type": "DEBIT",
    "issue_type": "WRONG_TRANSACTION_TYPE",
    "original_message": "ชำระคืนเงินกู้ 5,000 บาท ธนาคารรับเงินแล้ว"
  }
}
```

---

### 3. Feature Request (`feature_request`)

User feature requests.

---

### 4. Crash (`crash`)

App crashes with stack traces.

---

### 5. Performance (`performance`)

Performance issues (slow loading, high CPU/memory usage, etc.).

---

## GitHub Integration

### Configuration

Add to `.env`:

```env
GITHUB_API_TOKEN=ghp_your_github_personal_access_token
GITHUB_OWNER=xjanova
GITHUB_REPO=xmanstudio
```

### Auto-posting (Optional)

To automatically post reports to GitHub immediately:

```env
GITHUB_AUTO_POST=true
```

Otherwise, reports must be manually posted via the admin endpoint.

---

### GitHub Issue Format

Issues are created with:

**Title:**
```
[SMSCHECKER] SCB payment SMS misclassified as credit
```

**Labels:**
```
smschecker
misclassification
priority:high
severity:major
sms-parser
bank:SCB
```

**Body:**

Contains:
- Description
- Product information
- Metadata (for misclassifications)
- Stack trace (if available)
- Additional info
- Report ID and timestamp

---

## Database Schema

### `bug_reports` Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| product_name | varchar(50) | Product identifier |
| product_version | varchar(20) | Product version |
| report_type | varchar(50) | Type of report |
| title | varchar(255) | Report title |
| description | text | Detailed description |
| metadata | json | Flexible JSON for product-specific data |
| user_id | bigint | User who submitted (nullable) |
| user_email | varchar(255) | Email (nullable) |
| device_id | varchar(255) | Device identifier |
| status | enum | new, analyzing, confirmed, fixed, etc. |
| priority | enum | low, medium, high, critical |
| severity | enum | minor, moderate, major, critical |
| github_issue_url | varchar | GitHub issue URL |
| github_issue_number | int | GitHub issue number |
| posted_to_github_at | timestamp | When posted to GitHub |
| is_analyzed | boolean | Analysis status |
| is_fixed | boolean | Fix status |
| ... | ... | Additional tracking fields |

---

## Android Integration Example

### 1. Add API Service

```kotlin
interface BugReportApi {
    @POST("v1/bug-reports")
    suspend fun submitReport(@Body report: BugReportRequest): Response<BugReportResponse>

    @POST("v1/bug-reports/batch")
    suspend fun submitBatchReports(@Body reports: BatchReportRequest): Response<BatchReportResponse>
}
```

### 2. Update Repository

```kotlin
class MisclassificationReportRepository @Inject constructor(
    private val reportDao: MisclassificationReportDao,
    private val bugReportApi: BugReportApi,
    private val secureStorage: SecureStorage
) {
    suspend fun syncReportsToBackend() {
        val unsyncedReports = reportDao.getUnsyncedReports()

        val requests = unsyncedReports.map { report ->
            BugReportRequest(
                productName = "smschecker",
                reportType = "misclassification",
                title = generateTitle(report),
                description = generateDescription(report),
                metadata = mapOf(
                    "bank" to report.bank,
                    "amount" to report.amount,
                    "detected_type" to report.detectedType,
                    "correct_type" to report.correctType,
                    "issue_type" to report.issueType.name,
                    "original_message" to report.originalMessage
                ),
                deviceId = secureStorage.getDeviceId(),
                appVersion = BuildConfig.VERSION_NAME
            )
        }

        val response = bugReportApi.submitBatchReports(
            BatchReportRequest(reports = requests)
        )

        if (response.isSuccessful) {
            // Mark as synced
            unsyncedReports.forEach { it.isSynced = true }
            reportDao.updateAll(unsyncedReports)
        }
    }
}
```

---

## Security & Rate Limiting

- **Rate Limit:** 30 requests/minute per IP
- **Max Batch Size:** 50 reports per request
- **Authentication:** Not required for submissions (public API)
- **Admin Endpoints:** Require Sanctum token

---

## Future Enhancements

1. **Email Notifications** - Notify admins of critical bugs
2. **Auto-labeling** - ML-based automatic categorization
3. **Duplicate Detection** - Find similar/duplicate reports
4. **Priority Calculation** - Auto-assign priority based on impact
5. **Integration with Slack** - Real-time notifications
6. **Web Dashboard** - Admin panel for managing reports
