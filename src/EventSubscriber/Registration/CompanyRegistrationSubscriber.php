<?php

namespace App\EventSubscriber\Registration;

use App\Application\Registration\Step\Event\CompanyRegistrationEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompanyRegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CompanyRegistrationEvent::class => 'onCompanyRegistrationEvent',
        ];
    }

    public function onCompanyRegistrationEvent(CompanyRegistrationEvent $event): void
    {
        $this->logger?->info('Company registration event: {type} for company {companyId}', [
            'type' => $event->type,
            'companyId' => $event->companyId,
            'data' => $event->data,
        ]);
    }
}
