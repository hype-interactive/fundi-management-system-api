# Database Schema Documentation

## Table of Contents
- [Fundi Management System](#fundi-management-system)
  - [User Management](#user-management)
  - [Geographic Structure](#geographic-structure)  
  - [Worker Management](#worker-management)
  - [Project Management](#project-management)
  - [Financial Management](#financial-management)
  - [Communication System](#communication-system)
  - [System Management](#system-management)

---

# Fundi Management system

## User Management

### roles
- id (BIGINT UNSIGNED, PRIMARY KEY)
- name (VARCHAR 255, NOT NULL)
- timestamps (created_at, updated_at)

### users
- id (BIGINT UNSIGNED, PRIMARY KEY)
- name (VARCHAR 255, NOT NULL)
- email (VARCHAR 255, UNIQUE)
- phone (VARCHAR 20, NOT NULL, UNIQUE)
- password (VARCHAR 255, NOT NULL)
- role_id (BIGINT UNSIGNED, NOT NULL)
- status (ENUM: 'active', 'inactive', DEFAULT 'active')
- profile_photo (VARCHAR 255, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- deleted_at (TIMESTAMP, NULLABLE)

## Geographic Structure

### regions
- id (BIGINT UNSIGNED, PRIMARY KEY)
- name (VARCHAR 255, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)

### districts
- id (BIGINT UNSIGNED, PRIMARY KEY)
- region_id (BIGINT UNSIGNED, NOT NULL)
- name (VARCHAR 255, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - region_id → regions(id) ON DELETE CASCADE

### wards
- id (BIGINT UNSIGNED, PRIMARY KEY)
- district_id (BIGINT UNSIGNED, NOT NULL)
- name (VARCHAR 255, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - district_id → districts(id) ON DELETE CASCADE

### operator_districts
- id (BIGINT UNSIGNED, PRIMARY KEY)
- user_id (BIGINT UNSIGNED, NOT NULL)
- district_id (BIGINT UNSIGNED, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - user_id → users(id) ON DELETE CASCADE
  - district_id → districts(id) ON DELETE CASCADE
- **Unique Keys:**
  - (user_id, district_id)

## Worker Management

### fundis
- id (BIGINT UNSIGNED, PRIMARY KEY)
- unique_id (VARCHAR 20, NOT NULL, UNIQUE)
- name (VARCHAR 255, NOT NULL)
- gender (ENUM: 'male', 'female', 'other', NOT NULL)
- nida_number (VARCHAR 20, NULLABLE, UNIQUE)
- date_of_birth (DATE, NULLABLE)
- primary_phone (VARCHAR 20, NOT NULL)
- transaction_phone (VARCHAR 20, NOT NULL)
- ward_id (BIGINT UNSIGNED, NOT NULL)
- ranking (ENUM: 'fundi_kuu', 'fundi_msaidizi', NOT NULL)
- profile_photo (VARCHAR 255, NULLABLE)
- status (ENUM: 'active', 'inactive', 'pending', DEFAULT 'pending')
- created_by (BIGINT UNSIGNED, NOT NULL)
- approved_by (BIGINT UNSIGNED, NULLABLE)
- approved_at (TIMESTAMP, NULLABLE)
- legacy_match (BOOLEAN, DEFAULT FALSE)
- legacy_id (VARCHAR 50, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- deleted_at (TIMESTAMP, NULLABLE)
- **Foreign Keys:**
  - ward_id → wards(id)
  - created_by → users(id)
  - approved_by → users(id)

### legacy_fundis
- id (BIGINT UNSIGNED, PRIMARY KEY)
- name (VARCHAR 255, NOT NULL)

### products
- id (BIGINT UNSIGNED, PRIMARY KEY)
- name (VARCHAR 255, NOT NULL)
- description (TEXT, NULLABLE)
- visibility (ENUM: 'public', 'private', DEFAULT 'public')
- availability (ENUM: 'available', 'unavailable', DEFAULT 'available')
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)

## Project Management

### site_visits
- id (BIGINT UNSIGNED, PRIMARY KEY)
- operator_id (BIGINT UNSIGNED, NOT NULL)
- ward_id (BIGINT UNSIGNED, NOT NULL)
- site_name (VARCHAR 255, NOT NULL)
- site_description (TEXT, NULLABLE)
- visit_date (DATE, NOT NULL)
- gps_latitude (DECIMAL 10,8, NULLABLE)
- gps_longitude (DECIMAL 11,8, NULLABLE)
- status (ENUM: 'pending', 'approved', 'rejected', DEFAULT 'pending')
- approved_by (BIGINT UNSIGNED, NULLABLE)
- approved_at (TIMESTAMP, NULLABLE)
- rejection_reason (TEXT, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - operator_id → users(id)
  - ward_id → wards(id)
  - approved_by → users(id)

### site_visit_photos
- id (BIGINT UNSIGNED, PRIMARY KEY)
- site_visit_id (BIGINT UNSIGNED, NOT NULL)
- photo_path (VARCHAR 255, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - site_visit_id → site_visits(id) ON DELETE CASCADE

### site_visit_products
- id (BIGINT UNSIGNED, PRIMARY KEY)
- site_visit_id (BIGINT UNSIGNED, NOT NULL)
- product_id (BIGINT UNSIGNED, NOT NULL)
- quantity (INT, NOT NULL)
- notes (TEXT, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - site_visit_id → site_visits(id) ON DELETE CASCADE
  - product_id → products(id)

### site_visit_fundis
- id (BIGINT UNSIGNED, PRIMARY KEY)
- site_visit_id (BIGINT UNSIGNED, NOT NULL)
- fundi_id (BIGINT UNSIGNED, NOT NULL)
- notes (TEXT, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - site_visit_id → site_visits(id) ON DELETE CASCADE
  - fundi_id → fundis(id)

## Financial Management

### budget_allocations
- id (BIGINT UNSIGNED, PRIMARY KEY)
- title (VARCHAR 255, NOT NULL)
- description (TEXT, NULLABLE)
- total_amount (DECIMAL 12,2, NOT NULL)
- remaining_amount (DECIMAL 12,2, NOT NULL)
- created_by (BIGINT UNSIGNED, NOT NULL)
- status (ENUM: 'active', 'completed', 'cancelled', DEFAULT 'active')
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - created_by → users(id)

### disbursement_batches
- id (BIGINT UNSIGNED, PRIMARY KEY)
- budget_allocation_id (BIGINT UNSIGNED, NOT NULL)
- title (VARCHAR 255, NOT NULL)
- description (TEXT, NULLABLE)
- total_amount (DECIMAL 12,2, NOT NULL)
- disbursement_date (TIMESTAMP, NOT NULL)
- status (ENUM: 'pending', 'in_progress', 'completed', 'failed', DEFAULT 'pending')
- created_by (BIGINT UNSIGNED, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - budget_allocation_id → budget_allocations(id)
  - created_by → users(id)

### transactions
- id (BIGINT UNSIGNED, PRIMARY KEY)
- disbursement_batch_id (BIGINT UNSIGNED, NOT NULL)
- fundi_id (BIGINT UNSIGNED, NOT NULL)
- amount (DECIMAL 10,2, NOT NULL)
- transaction_reference (VARCHAR 255, NULLABLE)
- status (ENUM: 'pending', 'processing', 'completed', 'failed', DEFAULT 'pending')
- mno_response (TEXT, NULLABLE)
- failure_reason (TEXT, NULLABLE)
- processed_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - disbursement_batch_id → disbursement_batches(id)
  - fundi_id → fundis(id)

### operator_incentives
- id (BIGINT UNSIGNED, PRIMARY KEY)
- operator_id (BIGINT UNSIGNED, NOT NULL)
- site_visit_id (BIGINT UNSIGNED, NULLABLE)
- amount (DECIMAL 10,2, NOT NULL)
- description (TEXT, NULLABLE)
- status (ENUM: 'pending', 'approved', 'paid', 'rejected', DEFAULT 'pending')
- approved_by (BIGINT UNSIGNED, NULLABLE)
- approved_at (TIMESTAMP, NULLABLE)
- paid_at (TIMESTAMP, NULLABLE)
- transaction_reference (VARCHAR 255, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - operator_id → users(id)
  - site_visit_id → site_visits(id)
  - approved_by → users(id)

## Communication System

### sms_templates
- id (BIGINT UNSIGNED, PRIMARY KEY)
- title (VARCHAR 255, NOT NULL)
- content (TEXT, NOT NULL)
- created_by (BIGINT UNSIGNED, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - created_by → users(id)

### sms_campaigns
- id (BIGINT UNSIGNED, PRIMARY KEY)
- title (VARCHAR 255, NOT NULL)
- message (TEXT, NOT NULL)
- recipient_type (ENUM: 'fundis', 'operators', 'both', NOT NULL)
- scheduled_at (TIMESTAMP, NULLABLE)
- status (ENUM: 'draft', 'scheduled', 'processing', 'completed', 'cancelled', DEFAULT 'draft')
- created_by (BIGINT UNSIGNED, NOT NULL)
- sent_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - created_by → users(id)

### sms_campaign_filters
- id (BIGINT UNSIGNED, PRIMARY KEY)
- sms_campaign_id (BIGINT UNSIGNED, NOT NULL)
- filter_type (ENUM: 'region', 'district', 'ward', 'ranking', 'status', NOT NULL)
- filter_value (VARCHAR 255, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - sms_campaign_id → sms_campaigns(id) ON DELETE CASCADE

### sms_messages
- id (BIGINT UNSIGNED, PRIMARY KEY)
- sms_campaign_id (BIGINT UNSIGNED, NOT NULL)
- recipient_type (ENUM: 'fundi', 'operator', NOT NULL)
- recipient_id (BIGINT UNSIGNED, NOT NULL)
- phone_number (VARCHAR 20, NOT NULL)
- message (TEXT, NOT NULL)
- status (ENUM: 'pending', 'sent', 'delivered', 'failed', DEFAULT 'pending')
- provider_message_id (VARCHAR 255, NULLABLE)
- sent_at (TIMESTAMP, NULLABLE)
- delivered_at (TIMESTAMP, NULLABLE)
- failure_reason (TEXT, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - sms_campaign_id → sms_campaigns(id)

## System Management

### settings
- id (BIGINT UNSIGNED, PRIMARY KEY)
- setting_key (VARCHAR 100, NOT NULL, UNIQUE)
- setting_value (TEXT, NULLABLE)
- setting_group (VARCHAR 100, NULLABLE)
- is_public (BOOLEAN, DEFAULT FALSE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)

### legacy_imports
- id (BIGINT UNSIGNED, PRIMARY KEY)
- file_name (VARCHAR 255, NOT NULL)
- imported_by (BIGINT UNSIGNED, NOT NULL)
- total_records (INT, NOT NULL, DEFAULT 0)
- successful_records (INT, NOT NULL, DEFAULT 0)
- failed_records (INT, NOT NULL, DEFAULT 0)
- status (ENUM: 'processing', 'completed', 'failed', DEFAULT 'processing')
- started_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- completed_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - imported_by → users(id)

### legacy_import_failures
- id (BIGINT UNSIGNED, PRIMARY KEY)
- legacy_import_id (BIGINT UNSIGNED, NOT NULL)
- row_number (INT, NOT NULL)
- raw_data (TEXT, NOT NULL)
- error_message (TEXT, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Foreign Keys:**
  - legacy_import_id → legacy_imports(id) ON DELETE CASCADE

### activity_logs
- id (BIGINT UNSIGNED, PRIMARY KEY)
- user_id (BIGINT UNSIGNED, NULLABLE)
- log_type (VARCHAR 50, NOT NULL)
- description (TEXT, NOT NULL)
- model_type (VARCHAR 255, NULLABLE)
- model_id (BIGINT UNSIGNED, NULLABLE)
- properties (JSON, NULLABLE)
- ip_address (VARCHAR 45, NULLABLE)
- user_agent (TEXT, NULLABLE)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

## Database Indexes

```sql
CREATE INDEX idx_fundis_status ON fundis(status);
CREATE INDEX idx_fundis_ward ON fundis(ward_id);
CREATE INDEX idx_site_visits_status ON site_visits(status);
CREATE INDEX idx_site_visits_operator ON site_visits(operator_id);
CREATE INDEX idx_site_visits_date ON site_visits(visit_date);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_disbursement_batches_status ON disbursement_batches(status);
CREATE INDEX idx_sms_messages_status ON sms_messages(status);
```

---
