<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

final class EventCatalog
{
    public const LEAVE_REQUEST_SUBMITTED = 'leave_request.submitted';
    public const LEAVE_REQUEST_APPROVED = 'leave_request.approved';
    public const LEAVE_REQUEST_REJECTED = 'leave_request.rejected';
    public const LEAVE_REQUEST_CANCELLED = 'leave_request.cancelled';

    public const DOCUMENT_ISSUED = 'document.issued';
    public const DOCUMENT_REVOKED = 'document.revoked';
    public const DOCUMENT_WARNING_ISSUED = 'document.warning_issued';
    public const DOCUMENT_TERMINATION_ISSUED = 'document.termination_issued';

    public const PAYROLL_CREATED = 'payroll.created';
    public const PAYROLL_PAYMENT_STATUS_PAID = 'payroll.payment_status.paid';
    public const PAYROLL_PAYMENT_STATUS_ON_HOLD = 'payroll.payment_status.on_hold';
    public const PAYROLL_PAYMENT_STATUS_PROCESSED = 'payroll.payment_status.processed';

    public const STAFF_USER_PROVISIONED = 'staff_user.provisioned';
    public const STAFF_USER_ROLE_CHANGED = 'staff_user.role_changed';
    public const STAFF_USER_DEACTIVATED = 'staff_user.deactivated';
    public const STAFF_USER_LOGIN_ATTEMPTS_RESET = 'staff_user.login_attempts_reset';

    public const ATTENDANCE_BIOMETRIC_PUNCH_UNMAPPED = 'attendance.biometric_punch_unmapped';

    public const ATTENDANCE_BIOMETRIC_SYNCED = 'attendance.biometric_synced';

    public const EMPLOYEE_CREATED = 'employee.created';
    public const EMPLOYEE_STATUS_CHANGED = 'employee.status_changed';
    public const EMPLOYEE_STATUS_TERMINATED = 'employee.status.terminated';
    public const EMPLOYEE_USER_LINKED = 'employee.user_linked';

    public const PERFORMANCE_REVIEW_CREATED = 'performance_review.created';
    public const PERFORMANCE_REVIEW_SUBMITTED = 'performance_review.submitted';
    public const PERFORMANCE_REVIEW_COMPLETED = 'performance_review.completed';

    public const KPI_EMPLOYEE_ASSIGNED = 'kpi_employee.assigned';

    public const EMPLOYEE_AWARD_GRANTED = 'employee_award.granted';
    public const COMPANY_AWARD_GRANTED = 'company_award.granted';
    public const COMPANY_AWARD_ANNOUNCED = 'company_award.announced';

    public const ASSET_ASSIGNED = 'asset.assigned';
    public const ASSET_STATUS_UNDER_REPAIR = 'asset.status.under_repair';

    public const MAINTENANCE_SCHEDULED = 'maintenance.scheduled';
}
