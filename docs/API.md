# VOTESYS API Documentation

## Overview

This document provides comprehensive documentation for all API endpoints in the VOTESYS application. All API endpoints are located in the `admin/api/` directory and require proper authentication and CSRF protection.

## Authentication

All API endpoints require:
- Valid admin session
- CSRF token validation
- Proper HTTP methods

## Base URL
```
http://your-domain.com/VOTESYS/admin/api/
```

## Common Response Format

### Success Response
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {}
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error information"
}
```

## API Endpoints

### 1. Audit Logs API

#### GET `/audit.php`
Retrieve audit log entries

**Parameters:**
- `page` (optional): Page number for pagination
- `limit` (optional): Number of records per page
- `search` (optional): Search term for filtering

**Response:**
```json
{
    "success": true,
    "data": {
        "logs": [
            {
                "id": 1,
                "admin_id": 1,
                "action": "CREATE_ELECTION",
                "details": "Created election: Student Council 2024",
                "ip_address": "192.168.1.1",
                "created_at": "2024-01-15 10:30:00"
            }
        ],
        "total": 150,
        "page": 1,
        "limit": 20
    }
}
```

#### POST `/audit.php`
Create new audit log entry

**Request Body:**
```json
{
    "action": "USER_LOGIN",
    "details": "Admin user logged in",
    "csrf_token": "token_value"
}
```

### 2. Candidates API

#### GET `/candidates.php`
Retrieve candidates list

**Parameters:**
- `election_id` (optional): Filter by election
- `position_id` (optional): Filter by position
- `search` (optional): Search by name

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "position_id": 1,
            "position_name": "President",
            "election_id": 1,
            "photo": "candidate_123.jpg",
            "bio": "Student leader with 3 years experience",
            "created_at": "2024-01-15 09:00:00"
        }
    ]
}
```

#### POST `/candidates.php`
Create new candidate

**Request Body (multipart/form-data):**
- `name`: Candidate name
- `position_id`: Position ID
- `election_id`: Election ID
- `bio`: Candidate biography
- `photo`: Candidate photo file
- `csrf_token`: CSRF token

#### PUT `/candidates.php`
Update existing candidate

**Request Body:**
```json
{
    "id": 1,
    "name": "John Doe Updated",
    "bio": "Updated biography",
    "csrf_token": "token_value"
}
```

#### DELETE `/candidates.php`
Delete candidate

**Request Body:**
```json
{
    "id": 1,
    "csrf_token": "token_value"
}
```

### 3. Dashboard Statistics API

#### GET `/dashboard_stats.php`
Retrieve dashboard statistics

**Response:**
```json
{
    "success": true,
    "data": {
        "total_elections": 5,
        "total_candidates": 25,
        "active_candidates": 15,
        "total_votes": 1250,
        "voted_today": 45,
        "active_elections": 2,
        "recent_activity": [
            {
                "action": "VOTE_CAST",
                "timestamp": "2024-01-15 14:30:00"
            }
        ]
    }
}
```

### 4. Elections API

#### GET `/elections.php`
Retrieve elections list

**Parameters:**
- `status` (optional): Filter by status (active, upcoming, completed)
- `search` (optional): Search by title

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Student Council Elections 2024",
            "description": "Annual student council elections",
            "start_date": "2024-02-01 08:00:00",
            "end_date": "2024-02-03 18:00:00",
            "status": "active",
            "total_votes": 450,
            "created_at": "2024-01-15 10:00:00"
        }
    ]
}
```

#### POST `/elections.php`
Create new election

**Request Body:**
```json
{
    "title": "Student Council Elections 2024",
    "description": "Annual student council elections",
    "start_date": "2024-02-01 08:00:00",
    "end_date": "2024-02-03 18:00:00",
    "csrf_token": "token_value"
}
```

#### PUT `/elections.php`
Update existing election

**Request Body:**
```json
{
    "id": 1,
    "title": "Updated Election Title",
    "description": "Updated description",
    "start_date": "2024-02-01 08:00:00",
    "end_date": "2024-02-03 18:00:00",
    "csrf_token": "token_value"
}
```

#### DELETE `/elections.php`
Delete election

**Request Body:**
```json
{
    "id": 1,
    "csrf_token": "token_value"
}
```

### 5. Positions API

#### GET `/positions.php`
Retrieve positions list

**Parameters:**
- `election_id` (optional): Filter by election
- `search` (optional): Search by title

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "President",
            "description": "Student body president",
            "election_id": 1,
            "max_candidates": 5,
            "order_position": 1,
            "created_at": "2024-01-15 09:30:00"
        }
    ]
}
```

#### POST `/positions.php`
Create new position

**Request Body:**
```json
{
    "title": "Vice President",
    "description": "Student body vice president",
    "election_id": 1,
    "max_candidates": 3,
    "order_position": 2,
    "csrf_token": "token_value"
}
```

#### PUT `/positions.php`
Update existing position

**Request Body:**
```json
{
    "id": 1,
    "title": "Updated Position Title",
    "description": "Updated description",
    "max_candidates": 5,
    "csrf_token": "token_value"
}
```

#### DELETE `/positions.php`
Delete position

**Request Body:**
```json
{
    "id": 1,
    "csrf_token": "token_value"
}
```

### 6. Voters API

#### GET `/voters.php`
Retrieve voters list

**Parameters:**
- `search` (optional): Search by name or student ID
- `course` (optional): Filter by course
- `page` (optional): Page number
- `limit` (optional): Records per page

**Response:**
```json
{
    "success": true,
    "data": {
        "voters": [
            {
                "id": 1,
                "student_id": "STU001",
                "name": "Jane Smith",
                "email": "jane.smith@university.edu",
                "course": "Computer Science",
                "year_level": "3rd Year",
                "has_voted": false,
                "created_at": "2024-01-10 08:00:00"
            }
        ],
        "total": 500,
        "page": 1,
        "limit": 20
    }
}
```

#### POST `/voters.php`
Create new voter with auto-generated password

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Student",
    "student_id": "STU002",
    "email": "john.student@university.edu",
    "course": "Engineering",
    "year_level": "2nd Year",
    "csrf_token": "token_value"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Voter added successfully and password has been sent to their email address.",
    "voter_id": 123
}
```

**Note:** A secure password is automatically generated and sent to the voter's email address.

#### PUT `/voters.php`
Update existing voter

**Request Body:**
```json
{
    "id": 1,
    "first_name": "Updated First",
    "last_name": "Updated Last",
    "email": "updated.email@university.edu",
    "course": "Updated Course",
    "year_level": "4th Year",
    "reset_password": "1",
    "csrf_token": "token_value"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Voter updated successfully and new password has been sent to their email address."
}
```

**Parameters:**
- `reset_password` (optional): Set to "1" to generate and email a new password
- When `reset_password` is set, a new secure password is automatically generated and emailed to the voter

#### DELETE `/voters.php`
Delete voter

**Request Body:**
```json
{
    "id": 1,
    "csrf_token": "token_value"
}
```

### 7. Results API

#### GET `/results.php`
Retrieve election results

**Parameters:**
- `election_id`: Election ID (required)
- `position_id` (optional): Filter by position

**Response:**
```json
{
    "success": true,
    "data": {
        "election": {
            "id": 1,
            "title": "Student Council Elections 2024",
            "total_votes": 450
        },
        "positions": [
            {
                "id": 1,
                "title": "President",
                "candidates": [
                    {
                        "id": 1,
                        "name": "John Doe",
                        "votes": 250,
                        "percentage": 55.6
                    },
                    {
                        "id": 2,
                        "name": "Jane Smith",
                        "votes": 200,
                        "percentage": 44.4
                    }
                ]
            }
        ]
    }
}
```

### 8. Monitoring API

#### GET `/monitoring.php`
Retrieve real-time monitoring data

**Response:**
```json
{
    "success": true,
    "data": {
        "active_elections": 2,
        "total_votes_today": 125,
        "votes_per_hour": [
            {"hour": "14:00", "votes": 25},
            {"hour": "15:00", "votes": 30}
        ],
        "system_status": {
            "database": "healthy",
            "server_load": "normal",
            "last_backup": "2024-01-15 12:00:00"
        }
    }
}
```

### 9. Voting Status API

#### GET `/get_voting_status.php`
Get current voting status

**Parameters:**
- `election_id`: Election ID

**Response:**
```json
{
    "success": true,
    "data": {
        "is_active": true,
        "start_date": "2024-02-01 08:00:00",
        "end_date": "2024-02-03 18:00:00",
        "time_remaining": "2 days 4 hours",
        "total_votes": 450
    }
}
```

#### POST `/update_voting_status.php`
Update voting status

**Request Body:**
```json
{
    "election_id": 1,
    "action": "start|stop|pause",
    "csrf_token": "token_value"
}
```

### 8. Email System Integration

#### Auto-Generated Password System
The VOTESYS API integrates with an automated email system for secure password generation and delivery.

**Features:**
- **Secure Password Generation**: Cryptographically secure random passwords
- **Automatic Email Delivery**: Passwords sent via SMTP to voter email addresses
- **HTML Email Templates**: Professional, branded email notifications
- **Error Handling**: Graceful fallback when email delivery fails

**Email Configuration Requirements:**
```bash
# Required environment variables
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=VOTESYS - University Voting System
```

**Email Triggers:**
- **Voter Creation**: New voter accounts automatically receive login credentials
- **Password Reset**: Updated voters with `reset_password=1` receive new passwords
- **Bulk Import**: All imported voters receive individual password emails

**Email Content:**
- Student ID and auto-generated password
- Security instructions and best practices
- Login URL and system information
- Professional HTML formatting with university branding

**Error Handling:**
```json
{
    "success": true,
    "message": "Voter added successfully, but email could not be sent. Please provide the password manually: ABC123XYZ"
}
```

**Security Features:**
- Passwords are never stored in plain text
- Email transmission uses TLS/SSL encryption
- Generated passwords meet complexity requirements
- Audit logging for all email operations

## Voter Authentication Endpoints

### Overview
Voter-facing endpoints handle election-specific authentication and voting processes. These endpoints support dynamic election routing through URL parameters.

### Base URL
```
http://your-domain.com/VOTESYS/
```

### 1. Voter Page Access

#### GET `/voter_page.php`
Main voter interface for accessing elections

**URL Parameters:**
- `election_id` (optional): Specific election ID to access
  - If provided: Directs voter to the specified election
  - If not provided: Uses default election from system configuration
  - If invalid: Falls back to default election

**Examples:**
```bash
# Access default election
GET /voter_page.php

# Access specific election
GET /voter_page.php?election_id=12

# Access with invalid ID (falls back to default)
GET /voter_page.php?election_id=999
```

**Response Behavior:**
- Displays election information for the specified or default election
- Shows positions and candidates for the target election
- Handles authentication and session management
- Validates election existence and accessibility

### 2. Vote Processing

#### POST `/process_vote.php`
Processes submitted votes with election-specific handling

**Form Parameters:**
- `election_id`: Election ID from the voting form
- `position_[id]`: Selected candidate for each position
- `csrf_token`: CSRF protection token

**Request Body (Form Data):**
```
election_id=12
position_1=candidate_5
position_2=candidate_8
csrf_token=abc123xyz
```

**Response:**
```json
{
    "success": true,
    "message": "Vote submitted successfully",
    "election_id": 12,
    "timestamp": "2024-01-15 14:30:00"
}
```

### 3. Election Parameter Handling

#### Dynamic Election Routing
The system supports flexible election access through URL parameters:

**Parameter Processing:**
1. **URL Parameter Detection**: System checks for `election_id` in URL
2. **Validation**: Verifies election ID exists and is accessible
3. **Fallback Logic**: Uses `CURRENT_ELECTION_ID` constant if parameter is missing/invalid
4. **Session Management**: Maintains election context throughout voter session

**Configuration Constants:**
- `CURRENT_ELECTION_ID`: Default election ID (defined in `constants.php`)
- Used as fallback when no valid `election_id` parameter is provided

**Security Considerations:**
- Election ID validation prevents access to non-existent elections
- Input sanitization protects against injection attacks
- Session-based authentication maintains voter security
- CSRF protection on all vote submission forms

**Use Cases:**
- **Single Election**: Use default URL without parameters
- **Multiple Elections**: Generate election-specific links with `election_id` parameter
- **Email Campaigns**: Include election-specific links in voter notifications
- **Website Integration**: Embed direct links to specific elections

### 4. Authentication Flow

#### Voter Login Process
1. **Access**: Voter accesses `voter_page.php` (with or without `election_id`)
2. **Authentication**: System validates voter credentials
3. **Election Context**: System determines target election (parameter or default)
4. **Session Setup**: Establishes secure session with election context
5. **Voting Interface**: Displays election-specific voting interface

#### Session Management
- Election ID stored in voter session
- Consistent election context throughout voting process
- Automatic logout after session timeout
- Secure session token validation

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Invalid session |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation errors |
| 500 | Internal Server Error - Server error |

## Rate Limiting

- API requests are limited to 100 requests per minute per IP
- Bulk operations may have additional restrictions
- Rate limit headers are included in responses

## Security Considerations

1. **CSRF Protection**: All POST/PUT/DELETE requests require valid CSRF tokens
2. **Session Validation**: All requests validate admin session
3. **Input Sanitization**: All inputs are sanitized and validated
4. **SQL Injection Prevention**: Prepared statements used throughout
5. **File Upload Security**: Uploaded files are validated and stored securely

## Testing

Use tools like Postman or curl to test API endpoints:

```bash
# Example: Get elections list
curl -X GET "http://your-domain.com/VOTESYS/admin/api/elections.php" \
     -H "Cookie: PHPSESSID=your_session_id"

# Example: Create new candidate
curl -X POST "http://your-domain.com/VOTESYS/admin/api/candidates.php" \
     -H "Content-Type: application/json" \
     -H "Cookie: PHPSESSID=your_session_id" \
     -d '{"name":"Test Candidate","position_id":1,"election_id":1,"csrf_token":"token"}'
```

---

**Note**: This API documentation is automatically updated as the system evolves. Always refer to the latest version for accurate endpoint information.