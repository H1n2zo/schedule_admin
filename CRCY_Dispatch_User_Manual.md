# CRCY Dispatch System - User Manual

## Table of Contents
1. [System Overview](#system-overview)
2. [For Event Organizers (Users)](#for-event-organizers-users)
3. [For CRCY Administrators](#for-crcy-administrators)
4. [System Features](#system-features)
5. [Technical Requirements](#technical-requirements)
6. [Troubleshooting](#troubleshooting)

---

## System Overview

**CRCY Dispatch** is an Event Support Request Management System designed for the College Red Cross Youth (CRCY) at Eastern Visayas State University (EVSU). The system allows campus organizations to request volunteer support from CRCY members for their events.

### Key Features:
- **7-day advance notice requirement** for all requests
- **Automated conflict detection** to prevent scheduling overlaps
- **Email notifications** for request status updates
- **File attachment support** for event proposals and documents
- **Admin dashboard** with calendar view for managing requests
- **Security features** including rate limiting and audit logging

---

## For Event Organizers (Users)

### Getting Started

#### 1. Accessing the System
- Visit the CRCY Dispatch website
- Click "REQUEST SUPPORT NOW" on the homepage
- No registration required - submit requests directly

#### 2. System Requirements
- **Advance Notice**: Submit requests at least 7 days before your event
- **Time Restrictions**: Events must be scheduled between 5:00 AM and 8:30 PM
- **Volunteer Limits**: Request 1-10 volunteers per event
- **Required Documents**: At least one attachment (proposal, flyer, etc.)

### Submitting a Support Request

The request process consists of 5 steps:

#### Step 1: Event Information
- **Event Name**: Provide a clear, descriptive name
- **Organization**: Your department, club, or organization name
- **Event Date**: Must be at least 7 days in advance
- **Venue**: Specific location where the event will be held
- **Start Time**: Between 5:00 AM and 8:30 PM
- **End Time**: Must be after start time, maximum 12-hour duration

#### Step 2: Your Details
- **Full Name**: Your complete name as the requester
- **Position/Title**: Your role in the organization (optional)
- **Email Address**: Valid email for notifications
- **Contact Number**: 11-digit phone number for coordination (optional, numbers only)

#### Step 3: Volunteer Requirements
- **Expected Participants**: Estimated number of event attendees
- **Volunteers Needed**: Number of CRCY volunteers required (1-10)
- **Volunteer Roles**: Specific tasks (registration, crowd control, first aid, etc.)

#### Step 4: Event Details
- **Event Description**: Detailed information about your event
- **Special Requirements**: Any special equipment or training needed
- **Attachments**: Upload event proposals, flyers, or related documents
  - Supported formats: PDF, DOC, DOCX, JPG, PNG
  - Maximum file size: 10MB per file
  - At least one attachment is required

#### Step 5: Review & Submit
- Review all information before submitting
- Confirm submission through the modal dialog
- Receive a unique Request ID for tracking

### After Submission

#### What Happens Next:
1. **Immediate Confirmation**: You'll see a success page with your Request ID
2. **Email Notification**: Confirmation email sent to your provided address
3. **Review Process**: CRCY administrators review within 24-48 hours
4. **Status Update**: Email notification with approval/decline decision
5. **Volunteer Coordination**: If approved, offline coordination for volunteer assignments

#### Request Status Types:
- **Pending**: Under review by CRCY administrators
- **Approved**: Request accepted, volunteers will be assigned
- **Declined**: Request not approved (reason provided)

### Important Guidelines

#### Time Conflicts:
- The system automatically checks for scheduling conflicts
- Events need a 1-hour buffer between them
- If conflicts exist, you'll be notified to choose different times

#### Best Practices:
- Submit requests as early as possible (more than 7 days)
- Provide detailed event descriptions
- Include clear volunteer role requirements
- Attach comprehensive event proposals
- Use institutional email addresses when possible

---

## For CRCY Administrators

### Accessing the Admin Dashboard

#### Login Process:
1. **Hidden Access**: Press `Ctrl+Shift+A` on the homepage, or visit `/login.php`
2. **Default Credentials**: 
   - Password: `admin123` (change immediately after first login)
3. **Security Features**:
   - Account lockout after 5 failed attempts (15 minutes)
   - Session timeout after 4 hours
   - Rate limiting on login attempts

### Dashboard Overview

#### Main Interface:
- **Calendar View**: Gmail-style interface showing all events
- **Left Sidebar**: Mini calendar and status filters
- **Main Panel**: Monthly calendar with event details
- **Status Categories**: Filter by pending, approved, or declined requests

#### Navigation:
- **Month Navigation**: Use arrow buttons to browse months
- **Date Selection**: Click dates to view specific day events
- **Status Filters**: Click status categories to filter requests
- **Today Button**: Quick navigation to current date

### Managing Requests

#### Viewing Request Details:
1. Click on any event in the calendar
2. View comprehensive request information:
   - Event details and timing
   - Requester information
   - Volunteer requirements
   - Attached documents
   - Submission timestamp

#### Approving Requests:
1. Open the request details page
2. Click "Approve Request" button
3. Confirm approval in the modal dialog
4. System automatically:
   - Updates request status
   - Sends approval email to requester
   - Checks for and declines conflicting requests

#### Declining Requests:
1. Open the request details page
2. Click "Decline Request" button
3. Provide a detailed reason (minimum 10 characters)
4. Confirm decline in the modal dialog
5. System automatically:
   - Updates request status with reason
   - Sends decline email with explanation

### Conflict Management

#### Automatic Conflict Resolution:
- When approving a request, the system automatically identifies time conflicts
- Conflicting pending requests are automatically declined
- Decline reason includes details about the approved conflicting event
- Email notifications sent to affected requesters

#### Manual Conflict Checking:
- View calendar to identify potential scheduling issues
- Check event details for venue and time overlaps
- Consider 1-hour buffer requirements between events

### System Administration

#### Password Management:
1. Click admin dropdown in top-right corner
2. Select password change option
3. Provide current password and new password
4. New password must be at least 8 characters

#### Maintenance Mode:
1. Access through admin dropdown menu
2. Enable to prevent new submissions during system updates
3. Customize maintenance message for users
4. Disable when maintenance is complete

#### Security Monitoring:
- View security logs through admin menu
- Monitor failed login attempts
- Track request submissions and status changes
- Review system access patterns

### Email System

#### Automated Notifications:
- **Confirmation Emails**: Sent immediately upon request submission
- **Status Update Emails**: Sent when requests are approved/declined
- **HTML Format**: Professional formatting with CRCY branding

#### Email Configuration:
- Uses PHP's built-in mail() function
- Configured for XAMPP SMTP (development)
- Production deployment requires proper SMTP setup

### Best Practices for Administrators

#### Daily Operations:
1. **Morning Review**: Check pending requests from previous day
2. **Conflict Resolution**: Address any scheduling conflicts promptly
3. **Timely Responses**: Aim to respond within 24 hours
4. **Documentation**: Provide clear decline reasons

#### Weekly Tasks:
1. **Calendar Planning**: Review upcoming week for volunteer availability
2. **System Maintenance**: Check logs for any security issues
3. **Backup Verification**: Ensure database backups are current

#### Security Practices:
1. **Regular Password Changes**: Update admin password monthly
2. **Session Management**: Log out when not actively using system
3. **Access Monitoring**: Review security logs for unusual activity
4. **Maintenance Windows**: Use maintenance mode during updates

---

## System Features

### Security Features

#### Rate Limiting:
- **Login Attempts**: 5 attempts per 15 minutes per IP
- **Request Submissions**: 5 requests per hour per IP
- **Admin Actions**: 10 approval actions per 5 minutes

#### Data Protection:
- **Input Sanitization**: All user inputs are cleaned and validated
- **SQL Injection Prevention**: Prepared statements used throughout
- **File Upload Security**: Strict file type and size validation
- **Session Security**: Secure session configuration with timeouts

#### Audit Logging:
- All admin actions logged with timestamps
- Security events tracked (failed logins, rate limits)
- Request submissions and status changes recorded
- Log files stored in `/logs/security.log`

### Email System

#### Notification Types:
1. **Confirmation Email**: Sent upon request submission
2. **Approval Email**: Sent when request is approved
3. **Decline Email**: Sent when request is declined with reason

#### Email Features:
- HTML formatting with CRCY branding
- Request ID tracking
- Detailed event information
- Next steps guidance
- Professional appearance

### File Management

#### Supported File Types:
- **Documents**: PDF, DOC, DOCX
- **Images**: JPG, JPEG, PNG, GIF
- **Size Limit**: 10MB per file
- **Security**: File type validation and content checking

#### File Storage:
- Files stored in `/uploads/` directory
- Unique filenames prevent conflicts
- Secure download system with access control
- Automatic cleanup for declined requests

---

## Technical Requirements

### Server Requirements

#### Minimum Specifications:
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache or Nginx
- **Storage**: 1GB free space for file uploads
- **Memory**: 512MB RAM minimum

#### Development Environment:
- **XAMPP**: Recommended for local development
- **Database**: MySQL with UTF-8 support
- **Email**: SMTP configuration required for production

### Browser Compatibility

#### Supported Browsers:
- **Chrome**: Version 90+
- **Firefox**: Version 88+
- **Safari**: Version 14+
- **Edge**: Version 90+

#### Required Features:
- JavaScript enabled
- Cookies enabled
- File upload support
- Modern CSS support

### Database Configuration

#### Tables:
- `admin_users`: Administrator accounts
- `support_requests`: Event support requests
- `attachments`: Uploaded files
- `audit_log`: Security and action logging

#### Default Admin Account:
- **Username**: admin (ID: 1)
- **Password**: admin123 (change immediately)
- **Permissions**: Full system access

---

## Troubleshooting

### Common User Issues

#### "Too many requests" Error:
- **Cause**: Rate limiting triggered
- **Solution**: Wait 1 hour before submitting another request
- **Prevention**: Avoid multiple submissions of the same request

#### "Date too soon" Error:
- **Cause**: Event date is less than 7 days away
- **Solution**: Choose a date at least 7 days in the future
- **Note**: System shows next available date

#### File Upload Failures:
- **Cause**: File too large or unsupported format
- **Solution**: Use supported formats (PDF, DOC, JPG, PNG) under 10MB
- **Check**: Ensure at least one file is attached

#### Time Conflict Warnings:
- **Cause**: Another event scheduled at the same time
- **Solution**: Choose different time with 1-hour buffer
- **Note**: System shows conflicting events

### Common Admin Issues

#### Cannot Login:
- **Check**: Verify password (default: admin123)
- **Wait**: Account may be locked (15 minutes)
- **Contact**: System administrator if persistent

#### Email Not Sending:
- **Check**: XAMPP SMTP configuration
- **Verify**: Email settings in config.php
- **Test**: Send test email from server

#### Calendar Not Loading:
- **Check**: Database connection
- **Verify**: PHP error logs
- **Refresh**: Clear browser cache

#### File Downloads Failing:
- **Check**: File permissions on uploads directory
- **Verify**: File exists in database
- **Test**: Direct file access

### System Maintenance

#### Regular Tasks:
1. **Database Backup**: Weekly full backup
2. **Log Rotation**: Monthly log file archival
3. **File Cleanup**: Remove old attachments
4. **Security Updates**: Apply PHP and system updates

#### Emergency Procedures:
1. **System Down**: Enable maintenance mode
2. **Security Breach**: Change admin password, review logs
3. **Database Issues**: Restore from backup
4. **Email Problems**: Check SMTP configuration

### Getting Help

#### For Users:
- Contact CRCY office directly
- Check system status page
- Review this manual for guidance

#### For Administrators:
- Check security logs for error details
- Review PHP error logs
- Contact system developer for technical issues
- Refer to database documentation

---

## Contact Information

**CRCY Office - EVSU**
- **Email**: crcy.evsu.oc@gmail.com
- **System**: CRCY Dispatch - Event Support Request System
- **Version**: 1.0
- **Last Updated**: December 2024

---

*This manual covers the complete functionality of the CRCY Dispatch system. For technical support or system modifications, contact the development team.*