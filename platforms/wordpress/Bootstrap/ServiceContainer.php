<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Bootstrap;

use MF\StaffCore\Auth\PlatformUserGateway;
use MF\StaffCore\Authorization\AuthorizationService;
use MF\StaffCore\Authorization\EmployeeScopeResolver;
use MF\StaffCore\Authorization\ManagerTeamScopeResolver;
use MF\StaffCore\Authorization\PermissionService;
use MF\StaffCore\Authorization\Repositories\TeamScopeRepository;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Authorization\Repositories\RolePermissionRepository;
use MF\StaffCore\Authorization\StaffUserBootstrapSeeder;
use MF\StaffCore\Authorization\StaffUserResolver;
use MF\StaffCore\Authorization\StaffUserResolverInterface;
use MF\StaffCore\Database\DatabaseGateway;
use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Notifications\Repositories\NotificationRepository as NotificationDbRepository;
use MF\StaffCore\Modules\AssetCategories\Repositories\AssetCategoryRepository;
use MF\StaffCore\Modules\AssetCategories\Services\AssetCategoryService;
use MF\StaffCore\Modules\Attendance\Repositories\AttendanceRepository;
use MF\StaffCore\Modules\Attendance\Services\AttendanceService;
use MF\StaffCore\Modules\LeaveRequests\Repositories\LeaveRequestRepository;
use MF\StaffCore\Modules\LeaveRequests\Services\LeaveRequestService;
use MF\StaffCore\Modules\CompanyInformation\Repositories\CompanyInformationRepository;
use MF\StaffCore\Modules\CompanyInformation\Services\CompanyInformationService;
use MF\StaffCore\Modules\Maintenance\Repositories\MaintenanceRepository;
use MF\StaffCore\Modules\Maintenance\Services\MaintenanceService;
use MF\StaffCore\Modules\MyProfile\Services\MyProfileService;
use MF\StaffCore\Modules\MyPayslips\Services\MyPayslipsService;
use MF\StaffCore\Modules\MyTeam\Repositories\MyTeamRepository;
use MF\StaffCore\Modules\MyTeam\Services\MyTeamService;
use MF\StaffCore\Modules\Payroll\Repositories\PayrollRepository;
use MF\StaffCore\Modules\Payroll\Services\PayrollService;
use MF\StaffCore\Modules\Assets\Repositories\AssetRepository;
use MF\StaffCore\Modules\Assets\Services\AssetService;
use MF\StaffCore\Modules\Departments\Repositories\DepartmentRepository;
use MF\StaffCore\Modules\Departments\Services\DepartmentService;
use MF\StaffCore\Modules\Dashboard\Repositories\DashboardRepository;
use MF\StaffCore\Modules\Dashboard\Services\DashboardService;
use MF\StaffCore\Modules\Employees\Repositories\EmployeeAllowanceRepository;
use MF\StaffCore\Modules\Employees\Repositories\EmployeeRepository;
use MF\StaffCore\Modules\Employees\Services\EmployeeService;
use MF\StaffCore\Modules\Positions\Repositories\PositionRepository;
use MF\StaffCore\Modules\Positions\Services\PositionService;
use MF\StaffCore\Modules\Suppliers\Repositories\SupplierRepository;
use MF\StaffCore\Modules\Suppliers\Services\SupplierService;
use MF\StaffCore\Modules\Users\Repositories\StaffUserRepository;
use MF\StaffCore\Modules\Users\Services\StaffUserService;
use MF\StaffCore\Notification\NotificationDispatcher;
use MF\StaffCore\Notification\NotificationService;
use MF\StaffCore\Notification\RecipientResolver;
use MF\StaffCore\Platforms\WordPress\Auth\WordPressAuthorizationBridge;
use MF\StaffCore\Platforms\WordPress\Auth\WordPressCurrentPlatformUserProvider;
use MF\StaffCore\Platforms\WordPress\Auth\WordPressPlatformUserGateway;
use MF\StaffCore\Platforms\WordPress\Database\WordPressDatabaseGateway;
use MF\StaffCore\Platforms\WordPress\Database\WordPressIdSequence;
use MF\StaffCore\Platforms\WordPress\Database\WordPressTableGateway;

final class ServiceContainer
{
    private static ?self $instance = null;

    private ?TableGateway $tableGateway = null;

    private ?DatabaseGateway $databaseGateway = null;

    private ?IdSequence $idSequence = null;

    private ?AssetCategoryService $assetCategoryService = null;

    private ?SupplierService $supplierService = null;

    private ?AssetService $assetService = null;

    private ?DepartmentService $departmentService = null;

    private ?PositionService $positionService = null;

    private ?EmployeeService $employeeService = null;

    private ?AttendanceService $attendanceService = null;

    private ?LeaveRequestService $leaveRequestService = null;

    private ?PayrollService $payrollService = null;

    private ?MaintenanceService $maintenanceService = null;

    private ?DashboardService $dashboardService = null;

    private ?MyTeamService $myTeamService = null;

    private ?MyProfileService $myProfileService = null;
    private ?MyPayslipsService $myPayslipsService = null;

    private ?CompanyInformationService $companyInformationService = null;

    private ?StaffUserService $staffUserService = null;

    private ?PlatformUserGateway $platformUserGateway = null;

    private ?StaffUserRepository $staffUserRepository = null;

    private ?StaffUserResolverInterface $staffUserResolver = null;

    private ?AuthorizationService $authorizationService = null;

    private ?WordPressAuthorizationBridge $authorizationBridge = null;

    private ?StaffUserBootstrapSeeder $staffUserBootstrapSeeder = null;

    private ?EmployeeScopeResolver $employeeScopeResolver = null;

    private ?TeamScopeRepository $teamScopeRepository = null;

    private ?ManagerTeamScopeResolver $managerTeamScopeResolver = null;

    private ?PermissionService $permissionService = null;

    private ?NotificationService $notificationService = null;

    private ?NotificationDispatcher $notificationDispatcher = null;

    private ?RecipientResolver $notificationRecipientResolver = null;

    private ?NotificationDbRepository $notificationRepository = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function tableGateway(): TableGateway
    {
        if ($this->tableGateway === null) {
            global $wpdb;

            $this->tableGateway = new WordPressTableGateway(
                $wpdb,
                WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX,
            );
        }

        return $this->tableGateway;
    }

    public function databaseGateway(): DatabaseGateway
    {
        if ($this->databaseGateway === null) {
            global $wpdb;

            $this->databaseGateway = new WordPressDatabaseGateway(
                $wpdb,
                WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX,
            );
        }

        return $this->databaseGateway;
    }

    public function idSequence(): IdSequence
    {
        if ($this->idSequence === null) {
            $this->idSequence = new WordPressIdSequence($this->tableGateway());
        }

        return $this->idSequence;
    }

    public function assetCategories(): AssetCategoryService
    {
        if ($this->assetCategoryService === null) {
            $this->assetCategoryService = new AssetCategoryService(
                new AssetCategoryRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->assetCategoryService;
    }

    public function suppliers(): SupplierService
    {
        if ($this->supplierService === null) {
            $this->supplierService = new SupplierService(
                new SupplierRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->supplierService;
    }

    public function assets(): AssetService
    {
        if ($this->assetService === null) {
            $this->assetService = new AssetService(
                new AssetRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                new AssetCategoryRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                new SupplierRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->notificationDispatcher(),
            );
        }

        return $this->assetService;
    }

    public function departments(): DepartmentService
    {
        if ($this->departmentService === null) {
            $this->departmentService = new DepartmentService(
                new DepartmentRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->staffUserRepository(),
                $this->platformUserGateway(),
                new EmployeeRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                new PositionRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->departmentService;
    }

    public function positions(): PositionService
    {
        if ($this->positionService === null) {
            $this->positionService = new PositionService(
                new PositionRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->departments(),
            );
        }

        return $this->positionService;
    }

    public function employees(): EmployeeService
    {
        if ($this->employeeService === null) {
            $this->employeeService = new EmployeeService(
                new EmployeeRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                new EmployeeAllowanceRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->departments(),
                $this->notificationDispatcher(),
                $this->platformUserGateway(),
                new StaffUserRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->employeeService;
    }

    public function attendance(): AttendanceService
    {
        if ($this->attendanceService === null) {
            $this->attendanceService = new AttendanceService(
                new AttendanceRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->employeeScopeResolver(),
            );
        }

        return $this->attendanceService;
    }

    public function leaveRequests(): LeaveRequestService
    {
        if ($this->leaveRequestService === null) {
            $this->leaveRequestService = new LeaveRequestService(
                new LeaveRequestRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->notificationDispatcher(),
                $this->employeeScopeResolver(),
            );
        }

        return $this->leaveRequestService;
    }

    public function payroll(): PayrollService
    {
        if ($this->payrollService === null) {
            $this->payrollService = new PayrollService(
                new PayrollRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->notificationDispatcher(),
                $this->employeeScopeResolver(),
            );
        }

        return $this->payrollService;
    }

    public function maintenance(): MaintenanceService
    {
        if ($this->maintenanceService === null) {
            $this->maintenanceService = new MaintenanceService(
                new MaintenanceRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->notificationDispatcher(),
            );
        }

        return $this->maintenanceService;
    }

    public function dashboard(): DashboardService
    {
        if ($this->dashboardService === null) {
            $this->dashboardService = new DashboardService(
                new DashboardRepository($this->tableGateway()),
            );
        }

        return $this->dashboardService;
    }

    public function myTeam(): MyTeamService
    {
        if ($this->myTeamService === null) {
            $this->myTeamService = new MyTeamService(
                new MyTeamRepository(
                    $this->tableGateway(),
                    $this->teamScopeRepository(),
                ),
            );
        }

        return $this->myTeamService;
    }

    public function myProfile(): MyProfileService
    {
        if ($this->myProfileService === null) {
            $this->myProfileService = new MyProfileService(
                $this->employees(),
                $this->platformUserGateway(),
            );
        }

        return $this->myProfileService;
    }

    public function myPayslips(): MyPayslipsService
    {
        if ($this->myPayslipsService === null) {
            $this->myPayslipsService = new MyPayslipsService(
                new PayrollRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                new EmployeeRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->myPayslipsService;
    }

    public function companyInformation(): CompanyInformationService
    {
        if ($this->companyInformationService === null) {
            $this->companyInformationService = new CompanyInformationService(
                new CompanyInformationRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->companyInformationService;
    }

    public function registerPlatformUserGateway(PlatformUserGateway $gateway): void
    {
        $this->platformUserGateway = $gateway;
        $this->staffUserService = null;
    }

    public function staffUserResolver(): StaffUserResolverInterface
    {
        if ($this->staffUserResolver === null) {
            $this->staffUserResolver = new StaffUserResolver($this->staffUserRepository());
        }

        return $this->staffUserResolver;
    }

    public function permissionService(): PermissionService
    {
        if ($this->permissionService === null) {
            $this->permissionService = new PermissionService(
                new RolePermissionRepository($this->tableGateway()),
            );
        }

        return $this->permissionService;
    }

    public function authorization(): AuthorizationService
    {
        if ($this->authorizationService === null) {
            $this->authorizationService = new AuthorizationService(
                $this->staffUserResolver(),
                new WordPressCurrentPlatformUserProvider(),
                $this->permissionService(),
            );
        }

        return $this->authorizationService;
    }

    public function authorizationBridge(): WordPressAuthorizationBridge
    {
        if ($this->authorizationBridge === null) {
            $this->authorizationBridge = new WordPressAuthorizationBridge(
                $this->authorization(),
                $this->staffUserResolver(),
            );
        }

        return $this->authorizationBridge;
    }

    public function staffUserBootstrapSeeder(): StaffUserBootstrapSeeder
    {
        if ($this->staffUserBootstrapSeeder === null) {
            $this->staffUserBootstrapSeeder = new StaffUserBootstrapSeeder(
                $this->staffUserRepository(),
            );
        }

        return $this->staffUserBootstrapSeeder;
    }

    public function employeeScopeResolver(): EmployeeScopeResolver
    {
        if ($this->employeeScopeResolver === null) {
            $this->employeeScopeResolver = new EmployeeScopeResolver(
                new EmployeeRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
            );
        }

        return $this->employeeScopeResolver;
    }

    /**
     * Employee row filter for the current user, or null when unrestricted.
     *
     * @throws \MF\StaffCore\Authorization\AuthorizationException when employee role has no linked HR record
     */
    public function scopeEmployeeId(): ?int
    {
        $this->authorizationBridge()->resolveCurrentStaffUser();

        return $this->employeeScopeResolver()->scopeEmployeeIdForContext(
            $this->authorization()->current(),
        );
    }

    public function teamScopeRepository(): TeamScopeRepository
    {
        if ($this->teamScopeRepository === null) {
            $this->teamScopeRepository = new TeamScopeRepository(
                $this->tableGateway(),
            );
        }

        return $this->teamScopeRepository;
    }

    public function managerTeamScopeResolver(): ManagerTeamScopeResolver
    {
        if ($this->managerTeamScopeResolver === null) {
            $this->managerTeamScopeResolver = new ManagerTeamScopeResolver(
                $this->teamScopeRepository(),
                $this->employeeScopeResolver(),
            );
        }

        return $this->managerTeamScopeResolver;
    }

    public function isLineManager(): bool
    {
        $this->authorizationBridge()->resolveCurrentStaffUser();

        return $this->managerTeamScopeResolver()->isLineManager(
            $this->authorization()->current(),
        );
    }

    /**
     * Employee row filter for list queries on the given module.
     *
     * @return list<int>|null null = unrestricted (administrator, HR ops, or module without team scope)
     *
     * @throws AuthorizationException when employee role has no linked HR record
     */
    public function scopeEmployeeIdsForModule(string $moduleId): ?array
    {
        $this->authorizationBridge()->resolveCurrentStaffUser();
        $context = $this->authorization()->current();

        if ($context !== null && $context->role === RoleRegistry::ROLE_EMPLOYEE) {
            return [$this->employeeScopeResolver()->mustHaveLinkedEmployee($context)];
        }

        return $this->managerTeamScopeResolver()->scopeEmployeeIdsForModule($context, $moduleId);
    }

    /**
     * @return list<int>|null
     */
    public function performanceScopeEmployeeIds(): ?array
    {
        return $this->scopeEmployeeIdsForModule('performance_reviews');
    }

    public function staffUsers(): StaffUserService
    {
        if ($this->staffUserService === null) {
            $this->staffUserService = new StaffUserService(
                $this->staffUserRepository(),
                $this->platformUserGateway(),
                new EmployeeRepository(
                    $this->tableGateway(),
                    $this->idSequence(),
                ),
                $this->teamScopeRepository(),
                $this->notificationDispatcher(),
            );
        }

        return $this->staffUserService;
    }

    private function staffUserRepository(): StaffUserRepository
    {
        if ($this->staffUserRepository === null) {
            $this->staffUserRepository = new StaffUserRepository(
                $this->tableGateway(),
                $this->idSequence(),
            );
        }

        return $this->staffUserRepository;
    }

    private function platformUserGateway(): PlatformUserGateway
    {
        if ($this->platformUserGateway === null) {
            $this->platformUserGateway = new WordPressPlatformUserGateway();
        }

        return $this->platformUserGateway;
    }

    public function notifications(): NotificationService
    {
        if ($this->notificationService === null) {
            $this->notificationService = new NotificationService(
                $this->notificationRepository(),
            );
        }

        return $this->notificationService;
    }

    public function notificationDispatcher(): NotificationDispatcher
    {
        if ($this->notificationDispatcher === null) {
            $this->notificationDispatcher = new NotificationDispatcher(
                $this->notificationRepository(),
                $this->notificationRecipientResolver(),
            );
        }

        return $this->notificationDispatcher;
    }

    private function notificationRecipientResolver(): RecipientResolver
    {
        if ($this->notificationRecipientResolver === null) {
            $this->notificationRecipientResolver = new RecipientResolver(
                $this->tableGateway(),
            );
        }

        return $this->notificationRecipientResolver;
    }

    private function notificationRepository(): NotificationDbRepository
    {
        if ($this->notificationRepository === null) {
            $this->notificationRepository = new NotificationDbRepository(
                $this->tableGateway(),
                $this->idSequence(),
            );
        }

        return $this->notificationRepository;
    }
}
