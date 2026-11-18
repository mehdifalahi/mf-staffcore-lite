<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Lite foreign keys — only tables shipped by the free edition.
 */
final class ForeignKeyStatements
{
    /**
     * @return list<string>
     */
    public static function canonicalAlterStatements(): array
    {
        return [
            'ALTER TABLE `maintenance`
              ADD CONSTRAINT `fk_maintenance_asset`
              FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`)
              ON DELETE CASCADE ON UPDATE CASCADE',
            'ALTER TABLE `maintenance`
              ADD CONSTRAINT `fk_maintenance_performed_by_employee`
              FOREIGN KEY (`performed_by_employee_id`) REFERENCES `employee`(`id`)
              ON DELETE SET NULL ON UPDATE CASCADE',
            'ALTER TABLE `assets`
              ADD CONSTRAINT `fk_assets_category`
              FOREIGN KEY (`category_id`) REFERENCES `asset_category`(`id`)
              ON DELETE RESTRICT ON UPDATE CASCADE',
            'ALTER TABLE `assets`
              ADD CONSTRAINT `fk_assets_supplier`
              FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
              ON DELETE SET NULL ON UPDATE CASCADE',
            'ALTER TABLE `assets`
              ADD CONSTRAINT `fk_assets_assigned_to`
              FOREIGN KEY (`assigned_to`) REFERENCES `employee`(`id`)
              ON DELETE SET NULL ON UPDATE CASCADE',
            'ALTER TABLE `position`
              ADD CONSTRAINT `fk_position_department`
              FOREIGN KEY (`department_id`) REFERENCES `department`(`id`)
              ON DELETE RESTRICT ON UPDATE CASCADE',
            'ALTER TABLE `employee`
              ADD CONSTRAINT `fk_employee_department`
              FOREIGN KEY (`department_id`) REFERENCES `department`(`id`)
              ON DELETE RESTRICT ON UPDATE CASCADE',
            'ALTER TABLE `employee`
              ADD CONSTRAINT `fk_employee_position`
              FOREIGN KEY (`position_id`) REFERENCES `position`(`id`)
              ON DELETE RESTRICT ON UPDATE CASCADE',
            'ALTER TABLE `department`
              ADD CONSTRAINT `fk_department_manager`
              FOREIGN KEY (`manager_id`) REFERENCES `employee`(`id`)
              ON DELETE SET NULL ON UPDATE CASCADE',
            'ALTER TABLE `attendance`
              ADD CONSTRAINT `fk_attendance_employee`
              FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
              ON DELETE CASCADE ON UPDATE CASCADE',
            'ALTER TABLE `leave_request`
              ADD CONSTRAINT `fk_leave_request_employee`
              FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
              ON DELETE CASCADE ON UPDATE CASCADE',
            'ALTER TABLE `payroll`
              ADD CONSTRAINT `fk_payroll_employee`
              FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
              ON DELETE CASCADE ON UPDATE CASCADE',
            'ALTER TABLE `employee_allowance`
              ADD CONSTRAINT `fk_employee_allowance_employee`
              FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)
              ON DELETE CASCADE ON UPDATE CASCADE',
            'ALTER TABLE `notification`
              ADD CONSTRAINT `fk_notification_recipient`
              FOREIGN KEY (`recipient_staff_user_id`) REFERENCES `staff_user`(`id`)
              ON DELETE CASCADE ON UPDATE CASCADE',
        ];
    }
}
