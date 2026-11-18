<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

final class AjaxRegistrar
{
    /**
     * Registers handlers shipped with Lite. Pro handlers ship only in the Pro build.
     */
    public function register(): void
    {
        $categoryHandler = new AssetCategoryAjaxHandler();

        add_action('wp_ajax_mf_staffcore_asset_category_get', [$categoryHandler, 'get']);
        add_action('wp_ajax_mf_staffcore_asset_category_save', [$categoryHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_asset_category_delete', [$categoryHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_asset_category_bulk_delete', [$categoryHandler, 'bulkDelete']);

        $supplierHandler = new SupplierAjaxHandler();

        add_action('wp_ajax_mf_staffcore_supplier_save', [$supplierHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_supplier_delete', [$supplierHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_supplier_bulk_delete', [$supplierHandler, 'bulkDelete']);

        $assetHandler = new AssetAjaxHandler();

        add_action('wp_ajax_mf_staffcore_asset_save', [$assetHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_asset_delete', [$assetHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_asset_bulk_delete', [$assetHandler, 'bulkDelete']);
        add_action('wp_ajax_mf_staffcore_asset_remove_image', [$assetHandler, 'removeImage']);

        $maintenanceHandler = new MaintenanceAjaxHandler();

        add_action('wp_ajax_mf_staffcore_maintenance_save', [$maintenanceHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_maintenance_delete', [$maintenanceHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_maintenance_bulk_delete', [$maintenanceHandler, 'bulkDelete']);

        $positionHandler = new PositionAjaxHandler();

        add_action('wp_ajax_mf_staffcore_position_save', [$positionHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_position_delete', [$positionHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_position_bulk_delete', [$positionHandler, 'bulkDelete']);

        $departmentHandler = new DepartmentAjaxHandler();

        add_action('wp_ajax_mf_staffcore_department_save', [$departmentHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_department_delete', [$departmentHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_department_bulk_delete', [$departmentHandler, 'bulkDelete']);

        $employeeHandler = new EmployeeAjaxHandler();

        add_action('wp_ajax_mf_staffcore_employee_get', [$employeeHandler, 'get']);
        add_action('wp_ajax_mf_staffcore_employee_save', [$employeeHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_employee_delete', [$employeeHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_employee_bulk_delete', [$employeeHandler, 'bulkDelete']);
        add_action('wp_ajax_mf_staffcore_employee_remove_photo', [$employeeHandler, 'removePhoto']);

        $attendanceHandler = new AttendanceAjaxHandler();

        add_action('wp_ajax_mf_staffcore_attendance_save', [$attendanceHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_attendance_delete', [$attendanceHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_attendance_bulk_delete', [$attendanceHandler, 'bulkDelete']);

        $leaveRequestHandler = new LeaveRequestAjaxHandler();

        add_action('wp_ajax_mf_staffcore_leave_request_save', [$leaveRequestHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_leave_request_approve', [$leaveRequestHandler, 'approve']);
        add_action('wp_ajax_mf_staffcore_leave_request_delete', [$leaveRequestHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_leave_request_bulk_delete', [$leaveRequestHandler, 'bulkDelete']);

        $payrollHandler = new PayrollAjaxHandler();

        add_action('wp_ajax_mf_staffcore_payroll_save', [$payrollHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_payroll_delete', [$payrollHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_payroll_bulk_delete', [$payrollHandler, 'bulkDelete']);
        add_action('wp_ajax_mf_staffcore_payroll_prefill', [$payrollHandler, 'prefill']);

        $companyInformationHandler = new CompanyInformationAjaxHandler();

        add_action('wp_ajax_mf_staffcore_company_information_save', [$companyInformationHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_company_information_remove_logo', [$companyInformationHandler, 'removeLogo']);

        $usersHandler = new UsersAjaxHandler();

        add_action('wp_ajax_mf_staffcore_users_search_platform', [$usersHandler, 'searchPlatformUsers']);
        add_action('wp_ajax_mf_staffcore_users_list_link_candidates', [$usersHandler, 'listLinkCandidates']);
        add_action('wp_ajax_mf_staffcore_users_bulk_link', [$usersHandler, 'bulkLink']);
        add_action('wp_ajax_mf_staffcore_users_link', [$usersHandler, 'link']);
        add_action('wp_ajax_mf_staffcore_users_save', [$usersHandler, 'save']);
        add_action('wp_ajax_mf_staffcore_users_change_password', [$usersHandler, 'changePassword']);
        add_action('wp_ajax_mf_staffcore_users_reset_login', [$usersHandler, 'resetLoginAttempts']);
        add_action('wp_ajax_mf_staffcore_users_delete', [$usersHandler, 'delete']);
        add_action('wp_ajax_mf_staffcore_users_bulk_delete', [$usersHandler, 'bulkDelete']);

        $permissionsHandler = new PermissionsAjaxHandler();

        add_action('wp_ajax_mf_staffcore_permissions_save', [$permissionsHandler, 'save']);

        $notificationHandler = new NotificationAjaxHandler();

        add_action('wp_ajax_mf_staffcore_notifications_dropdown', [$notificationHandler, 'dropdown']);
        add_action('wp_ajax_mf_staffcore_notifications_unread_count', [$notificationHandler, 'unreadCount']);
        add_action('wp_ajax_mf_staffcore_notifications_inbox_list', [$notificationHandler, 'inboxList']);
        add_action('wp_ajax_mf_staffcore_notifications_mark_read', [$notificationHandler, 'markRead']);
        add_action('wp_ajax_mf_staffcore_notifications_mark_all_read', [$notificationHandler, 'markAllRead']);

        $myProfileHandler = new MyProfileAjaxHandler();

        add_action('wp_ajax_mf_staffcore_my_profile_change_password', [$myProfileHandler, 'changePassword']);
        add_action('wp_ajax_mf_staffcore_my_profile_upload_photo', [$myProfileHandler, 'uploadPhoto']);
    }
}
