<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

final class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly RecipientResolver $recipientResolver,
    ) {
    }

    public function dispatch(StaffCoreEvent $event): void
    {
        $recipientIds = $this->recipientResolver->resolveRecipientStaffUserIds(
            $event->recipientCodes,
            $event->context,
            $event->actorPlatformUserId,
            $event->includeActor,
        );

        if ($recipientIds === []) {
            return;
        }

        $this->repository->createMany(
            $recipientIds,
            $event->eventKey,
            $event->title,
            $event->body,
            $event->entityType,
            $event->entityId,
            $event->actionUrl,
            $event->priority,
        );
    }
}
