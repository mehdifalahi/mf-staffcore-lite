CREATE TABLE `maintenance` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `asset_id` BIGINT NOT NULL COMMENT 'Reference to the asset being maintained',
  `maintenance_date` DATE NOT NULL COMMENT 'Date when maintenance was performed',
  `type` ENUM('REPAIR', 'SERVICE', 'INSPECTION') NOT NULL COMMENT 'Type of maintenance performed',
  `cost` DECIMAL(10,2) COMMENT 'Cost of the maintenance',
  `performed_by` VARCHAR(255) COMMENT 'Display name of person who performed the maintenance',
  `performed_by_employee_id` BIGINT COMMENT 'Reference to employee who performed the maintenance',
  `next_service_date` DATE COMMENT 'Scheduled date for next service',
  `notes` TEXT COMMENT 'Additional notes about the maintenance',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores maintenance records for assets';

CREATE TABLE `attendance` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `employee_id` BIGINT NOT NULL COMMENT 'Reference to employee',
  `attendance_date` DATE NOT NULL COMMENT 'Date of attendance record',
  `clock_in` TIME COMMENT 'Clock in time',
  `clock_out` TIME COMMENT 'Clock out time',
  `status` ENUM('PRESENT', 'LATE', 'ABSENT', 'ON_LEAVE') NOT NULL COMMENT 'Attendance status',
  `attendance_type` ENUM('MANUAL', 'AUTO') NOT NULL DEFAULT 'MANUAL' COMMENT 'How attendance was recorded',
  `remarks` TEXT COMMENT 'Additional remarks about attendance',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores employee attendance records';

CREATE TABLE `leave_request` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `employee_id` BIGINT NOT NULL COMMENT 'Reference to employee',
  `leave_type` VARCHAR(100) NOT NULL COMMENT 'Type of leave requested',
  `start_date` DATE NOT NULL COMMENT 'Start date of leave',
  `end_date` DATE NOT NULL COMMENT 'End date of leave',
  `total_days` INT NOT NULL COMMENT 'Total number of leave days',
  `reason` TEXT COMMENT 'Reason for leave request',
  `notes` TEXT COMMENT 'Additional notes about the leave request',
  `status` ENUM('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'PENDING' COMMENT 'Status of the leave request',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores employee leave requests';

CREATE TABLE `payroll` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `employee_id` BIGINT NOT NULL COMMENT 'Reference to employee',
  `payroll_month` INT NOT NULL COMMENT 'Payroll month (1-12)',
  `payroll_year` INT NOT NULL COMMENT 'Payroll year',
  `basic_salary` DECIMAL(12,2) NOT NULL COMMENT 'Basic salary amount',
  `allowances` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Total allowances',
  `overtime_amount` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Overtime payment amount',
  `bonus` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Bonus amount',
  `commission` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Commission amount',
  `tax` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Tax deduction amount',
  `insurance` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Insurance deduction amount',
  `advance_deduction` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Advance salary deduction',
  `other_deductions` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Other deductions',
  `working_days` INT NOT NULL COMMENT 'Total working days in the month',
  `present_days` INT NOT NULL COMMENT 'Days employee was present',
  `absent_days` INT NOT NULL DEFAULT 0 COMMENT 'Days employee was absent',
  `leave_days` INT NOT NULL DEFAULT 0 COMMENT 'Days employee was on leave',
  `overtime_hours` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Total overtime hours',
  `payment_status` ENUM('PENDING', 'PROCESSED', 'PAID', 'ON_HOLD') NOT NULL DEFAULT 'PENDING' COMMENT 'Status of payroll payment',
  `payment_date` DATE COMMENT 'Date when payment was made',
  `payment_method` ENUM('CASH', 'BANK', 'CHEQUE') COMMENT 'Method of payment',
  `payment_reference` VARCHAR(255) COMMENT 'Payment reference number',
  `notes` TEXT COMMENT 'Additional notes about payroll',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores employee payroll information';

CREATE TABLE `employee_allowance` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `employee_id` BIGINT NOT NULL COMMENT 'Reference to employee',
  `name` VARCHAR(255) NOT NULL COMMENT 'Name of the allowance',
  `amount` DECIMAL(12,2) NOT NULL COMMENT 'Amount of the allowance',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores employee allowances information';

CREATE TABLE `assets` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `code` VARCHAR(100) NOT NULL COMMENT 'Unique asset code identifier',
  `name` VARCHAR(255) NOT NULL COMMENT 'Name of the asset',
  `category_id` BIGINT NOT NULL COMMENT 'Reference to asset category',
  `purchase_date` DATE COMMENT 'Date when asset was purchased',
  `purchase_cost` DECIMAL(12,2) COMMENT 'Original purchase cost of the asset',
  `current_value` DECIMAL(12,2) COMMENT 'Current estimated value of the asset',
  `serial_number` VARCHAR(255) COMMENT 'Serial number of the asset',
  `warranty_expiry` DATE COMMENT 'Warranty expiration date',
  `status` ENUM('ACTIVE', 'INACTIVE', 'UNDER_REPAIR', 'DISPOSED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Current status of the asset',
  `condition` ENUM('EXCELLENT', 'GOOD', 'FAIR', 'POOR') NOT NULL DEFAULT 'GOOD' COMMENT 'Physical condition of the asset',
  `assigned_to` BIGINT COMMENT 'Employee ID to whom asset is assigned',
  `location` VARCHAR(255) COMMENT 'Current location of the asset',
  `supplier_id` BIGINT COMMENT 'Reference to the supplier',
  `image` VARCHAR(500) COMMENT 'Path or URL to asset image',
  `notes` TEXT COMMENT 'Additional notes about the asset',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores asset information for HR management system';

CREATE TABLE `supplier` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `supplier_name` VARCHAR(255) NOT NULL COMMENT 'Name of the supplier company',
  `contact_person` VARCHAR(100) COMMENT 'Primary contact person at the supplier',
  `email` VARCHAR(255) COMMENT 'Supplier''s email address',
  `phone` VARCHAR(20) COMMENT 'Supplier''s phone number',
  `address` TEXT COMMENT 'Supplier''s street address',
  `city` VARCHAR(100) COMMENT 'Supplier''s city',
  `state` VARCHAR(100) COMMENT 'Supplier''s state or province',
  `country` VARCHAR(100) COMMENT 'Supplier''s country',
  `postal_code` VARCHAR(20) COMMENT 'Supplier''s postal or ZIP code',
  `website` VARCHAR(255) COMMENT 'Supplier''s website URL',
  `payment_terms` TEXT COMMENT 'Payment terms and conditions',
  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Current status of the supplier',
  `notes` TEXT COMMENT 'Additional notes about the supplier',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores supplier information for asset management';

CREATE TABLE `asset_category` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Name of the asset category',
  `description` TEXT COMMENT 'Description of the asset category',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores asset categories for classification';

CREATE TABLE `department` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Name of the department',
  `code` VARCHAR(50) NOT NULL COMMENT 'Unique department code',
  `description` TEXT COMMENT 'Description of the department',
  `manager_id` BIGINT COMMENT 'Employee ID of the department manager',
  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Current status of the department',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores department information for HR management system';

CREATE TABLE `position` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL COMMENT 'Job title or position name',
  `code` VARCHAR(50) NOT NULL COMMENT 'Unique position code',
  `department_id` BIGINT NOT NULL COMMENT 'Reference to department',
  `level` ENUM('ENTRY', 'JUNIOR', 'MID', 'SENIOR', 'LEAD', 'MANAGER', 'DIRECTOR', 'EXECUTIVE') NOT NULL COMMENT 'Job level in organizational hierarchy',
  `min_salary` DECIMAL(12,2) COMMENT 'Minimum salary for this position',
  `max_salary` DECIMAL(12,2) COMMENT 'Maximum salary for this position',
  `description` TEXT COMMENT 'Job description and responsibilities',
  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Current status of the position',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores job positions and their details';

CREATE TABLE `employee` (
  `id` BIGINT NOT NULL PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL COMMENT 'Unique employee code identifier',
  `department_id` BIGINT NOT NULL COMMENT 'Reference to department',
  `position_id` BIGINT NOT NULL COMMENT 'Reference to position',
  `first_name` VARCHAR(100) NOT NULL COMMENT 'Employee''s first name',
  `last_name` VARCHAR(100) NOT NULL COMMENT 'Employee''s last name',
  `email` VARCHAR(255) NOT NULL COMMENT 'Employee''s email address',
  `phone` VARCHAR(20) COMMENT 'Employee''s phone number',
  `user_id` BIGINT COMMENT 'Linked platform user account ID',
  `birth_date` DATE COMMENT 'Employee date of birth',
  `hire_date` DATE NOT NULL COMMENT 'Date when employee was hired',
  `employment_status` ENUM('ACTIVE', 'ON_LEAVE', 'SUSPENDED', 'RESIGNED', 'TERMINATED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Current employment status',
  `employment_type` ENUM('FULLTIME', 'PARTTIME', 'CONTRACT', 'TEMPORARY', 'INTERN', 'FREELANCE') NOT NULL DEFAULT 'FULLTIME' COMMENT 'Type of employment',
  `basic_salary` DECIMAL(12,2) COMMENT 'Employee''s basic salary',
  `annual_leave` INT DEFAULT 0 COMMENT 'Annual leave days allocated',
  `address` TEXT COMMENT 'Employee''s street address',
  `city` VARCHAR(100) COMMENT 'Employee''s city',
  `province` VARCHAR(100) COMMENT 'Employee''s province or state',
  `country` VARCHAR(100) COMMENT 'Employee''s country',
  `zip_code` VARCHAR(20) COMMENT 'Employee''s ZIP or postal code',
  `photo` VARCHAR(500) COMMENT 'Path or URL to employee photo',
  `notes` TEXT COMMENT 'Additional notes about the employee',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores employee information for HR management system';

CREATE TABLE `company_information` (
  `id` BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Company name',
  `email` VARCHAR(255) COMMENT 'Company email address',
  `phone` VARCHAR(20) COMMENT 'Company phone number',
  `address` TEXT COMMENT 'Company street address',
  `city` VARCHAR(100) COMMENT 'Company city',
  `province` VARCHAR(100) COMMENT 'Company province or state',
  `country` VARCHAR(100) COMMENT 'Company country',
  `website` VARCHAR(255) COMMENT 'Company website URL',
  `currency` VARCHAR(10) COMMENT 'Default currency code (e.g. USD, EUR)',
  `timezone` VARCHAR(100) COMMENT 'Company timezone (e.g. Asia/Tehran)',
  `logo` VARCHAR(500) COMMENT 'Path or URL to company logo',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Stores company profile and configuration information';

-- StaffCore authorization profile (WP table: {prefix}mfsc_staff_user). See docs/sql/staff-user-reference.sql
CREATE TABLE `staff_user` (
  `id` BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `platform_user_id` BIGINT NOT NULL COMMENT 'Linked platform account ID (WordPress/Joomla user)',
  `role` ENUM('administrator', 'hr_manager', 'employee') NOT NULL COMMENT 'StaffCore role assignment',
  `manager_scope_mode` ENUM('auto', 'team', 'all') NOT NULL DEFAULT 'auto' COMMENT 'Effective manager data scope override; auto = detect from department.manager_id',
  `phone` VARCHAR(20) COMMENT 'Contact phone for this user',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Whether the user may access StaffCore',
  `failed_login_attempts` INT NOT NULL DEFAULT 0 COMMENT 'Count of consecutive failed login attempts',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `created_by` BIGINT COMMENT 'User ID who created the record',
  `updated_by` BIGINT COMMENT 'User ID who last updated the record'
) COMMENT = 'Maps platform users to StaffCore roles and profile fields';

CREATE TABLE `notification` (
  `id` BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `recipient_staff_user_id` BIGINT NOT NULL COMMENT 'Recipient staff_user.id',
  `event_key` VARCHAR(100) NOT NULL COMMENT 'Canonical event key (e.g. leave_request.approved)',
  `title` VARCHAR(255) NOT NULL COMMENT 'Short title shown in UI',
  `body` TEXT COMMENT 'Optional detail text',
  `entity_type` VARCHAR(50) COMMENT 'Entity type for deep links',
  `entity_id` BIGINT COMMENT 'Entity id for deep links',
  `action_url` VARCHAR(500) COMMENT 'Relative StaffCore admin URL',
  `priority` ENUM('low', 'normal', 'high') NOT NULL DEFAULT 'normal',
  `read_at` DATETIME COMMENT 'NULL = unread',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp'
) COMMENT = 'Stores in-app notifications per StaffCore user';

CREATE TABLE `role_permission` (
  `role` ENUM('administrator', 'hr_manager', 'employee') NOT NULL COMMENT 'StaffCore role id',
  `permission` VARCHAR(128) NOT NULL COMMENT 'Permission key: {module}.{view|create|edit|delete}',
  PRIMARY KEY (`role`, `permission`)
) COMMENT = 'Configurable role permission grants (Settings → Permissions)';

ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maintenance_asset`
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maintenance_performed_by_employee`
  FOREIGN KEY (`performed_by_employee_id`) REFERENCES `employee`(`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_category`
  FOREIGN KEY (`category_id`) REFERENCES `asset_category`(`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_supplier`
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_assigned_to`
  FOREIGN KEY (`assigned_to`) REFERENCES `employee`(`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `position`
  ADD CONSTRAINT `fk_position_department`
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_department`
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_position`
  FOREIGN KEY (`position_id`) REFERENCES `position`(`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `department`
  ADD CONSTRAINT `fk_department_manager`
  FOREIGN KEY (`manager_id`) REFERENCES `employee`(`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_employee`
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `leave_request`
  ADD CONSTRAINT `fk_leave_request_employee`
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee`
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `employee_allowance`
  ADD CONSTRAINT `fk_employee_allowance_employee`
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_recipient`
  FOREIGN KEY (`recipient_staff_user_id`) REFERENCES `staff_user`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

CREATE INDEX `idx_maintenance_asset_id`
  ON `maintenance` (`asset_id`);

CREATE INDEX `idx_maintenance_maintenance_date`
  ON `maintenance` (`maintenance_date`);

CREATE INDEX `idx_maintenance_type`
  ON `maintenance` (`type`);

CREATE INDEX `idx_maintenance_next_service_date`
  ON `maintenance` (`next_service_date`);

CREATE INDEX `idx_attendance_employee_id`
  ON `attendance` (`employee_id`);

CREATE INDEX `idx_attendance_attendance_date`
  ON `attendance` (`attendance_date`);

CREATE INDEX `idx_attendance_status`
  ON `attendance` (`status`);

CREATE UNIQUE INDEX `idx_attendance_employee_date`
  ON `attendance` (`employee_id`, `attendance_date`);

CREATE INDEX `idx_leave_requests_employee_id`
  ON `leave_request` (`employee_id`);

CREATE INDEX `idx_leave_requests_status`
  ON `leave_request` (`status`);

CREATE INDEX `idx_leave_requests_start_date`
  ON `leave_request` (`start_date`);

CREATE INDEX `idx_leave_requests_end_date`
  ON `leave_request` (`end_date`);

CREATE INDEX `idx_leave_requests_leave_type`
  ON `leave_request` (`leave_type`);

CREATE INDEX `idx_payroll_employee_id`
  ON `payroll` (`employee_id`);

CREATE INDEX `idx_payroll_payment_status`
  ON `payroll` (`payment_status`);

CREATE INDEX `idx_payroll_period`
  ON `payroll` (`payroll_year`, `payroll_month`);

CREATE UNIQUE INDEX `idx_payroll_employee_period`
  ON `payroll` (`employee_id`, `payroll_year`, `payroll_month`);

CREATE INDEX `idx_payroll_payment_date`
  ON `payroll` (`payment_date`);

CREATE INDEX `idx_employee_allowances_employee_id`
  ON `employee_allowance` (`employee_id`);

CREATE INDEX `idx_employee_allowances_name`
  ON `employee_allowance` (`name`);

CREATE UNIQUE INDEX `idx_assets_code`
  ON `assets` (`code`);

CREATE INDEX `idx_assets_category_id`
  ON `assets` (`category_id`);

CREATE INDEX `idx_assets_status`
  ON `assets` (`status`);

CREATE INDEX `idx_assets_assigned_to`
  ON `assets` (`assigned_to`);

CREATE INDEX `idx_assets_supplier_id`
  ON `assets` (`supplier_id`);

CREATE UNIQUE INDEX `idx_assets_serial_number`
  ON `assets` (`serial_number`);

CREATE UNIQUE INDEX `idx_suppliers_email`
  ON `supplier` (`email`);

CREATE INDEX `idx_suppliers_status`
  ON `supplier` (`status`);

CREATE INDEX `idx_suppliers_name`
  ON `supplier` (`supplier_name`);

CREATE UNIQUE INDEX `idx_asset_categories_name`
  ON `asset_category` (`name`);

CREATE UNIQUE INDEX `idx_departments_code`
  ON `department` (`code`);

CREATE INDEX `idx_departments_name`
  ON `department` (`name`);

CREATE INDEX `idx_departments_manager_id`
  ON `department` (`manager_id`);

CREATE INDEX `idx_departments_status`
  ON `department` (`status`);

CREATE UNIQUE INDEX `idx_positions_code`
  ON `position` (`code`);

CREATE INDEX `idx_positions_department_id`
  ON `position` (`department_id`);

CREATE INDEX `idx_positions_level`
  ON `position` (`level`);

CREATE INDEX `idx_positions_title`
  ON `position` (`title`);

CREATE INDEX `idx_positions_status`
  ON `position` (`status`);

CREATE UNIQUE INDEX `idx_employees_code`
  ON `employee` (`code`);

CREATE UNIQUE INDEX `idx_employees_email`
  ON `employee` (`email`);

CREATE INDEX `idx_employees_department_id`
  ON `employee` (`department_id`);

CREATE INDEX `idx_employees_position_id`
  ON `employee` (`position_id`);

CREATE INDEX `idx_employees_employment_status`
  ON `employee` (`employment_status`);

CREATE INDEX `idx_employees_employment_type`
  ON `employee` (`employment_type`);

CREATE INDEX `idx_employees_hire_date`
  ON `employee` (`hire_date`);

CREATE INDEX `idx_employees_user_id`
  ON `employee` (`user_id`);

CREATE UNIQUE INDEX `idx_staff_user_platform_user_id` ON `staff_user` (`platform_user_id`);

CREATE INDEX `idx_staff_user_role`                    ON `staff_user` (`role`);

CREATE INDEX `idx_staff_user_status`                  ON `staff_user` (`status`);

CREATE INDEX `idx_notification_recipient_read_created` ON `notification` (`recipient_staff_user_id`, `read_at`, `created_at`);

CREATE INDEX `idx_role_permission_permission`         ON `role_permission` (`permission`);
