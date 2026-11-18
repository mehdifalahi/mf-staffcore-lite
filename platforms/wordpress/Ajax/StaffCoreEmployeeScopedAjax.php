<?php



declare(strict_types=1);



namespace MF\StaffCore\Platforms\WordPress\Ajax;



use MF\StaffCore\Authorization\AuthorizationException;

use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;



/**

 * Resolves employee row-level scope for the current StaffCore user (AJAX layer).

 */

trait StaffCoreEmployeeScopedAjax

{

    protected function scopeEmployeeId(): ?int

    {

        try {

            return ServiceContainer::instance()->scopeEmployeeId();

        } catch (AuthorizationException $exception) {

            wp_send_json_error(['message' => $exception->getMessage()], 403);

        }

    }



    /**

     * @return list<int>|null

     */

    protected function scopeEmployeeIdsForModule(string $moduleId): ?array

    {

        try {

            return ServiceContainer::instance()->scopeEmployeeIdsForModule($moduleId);

        } catch (AuthorizationException $exception) {

            wp_send_json_error(['message' => $exception->getMessage()], 403);

        }

    }

}


