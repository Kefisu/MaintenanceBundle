<?php

namespace Kefisu\Bundle\MaintenanceBundle\EventListener;

use Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaintenanceListener implements EventSubscriberInterface
{
    public function __construct(
        private MaintenanceManagerInterface $maintenanceManager,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 1024], // We should place ourselves before everything to exit as early as possible
            ],
        ];
    }

    /**
     * We should check if the maintenance mode is active
     * If it is, we should block the request and return a 503 response
     *
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        if ($this->maintenanceManager->isActive()) {
            $request = $event->getRequest();

            $secret = $request->query->get('secret') ?? $request->headers->get('secret');
            if (is_string($secret) && $this->maintenanceManager->validateSecret($secret)) {
                return;
            }

            $template = file_get_contents(__DIR__ . '/../../template/maintenance.html');

            $response = new Response($template ?: 'Maintenance mode', 503);

            $data = $this->maintenanceManager->getData();

            if (is_int($data['statusCode'] ?? null)) {
                $response->setStatusCode($this->maintenanceManager->getData()['statusCode']);
            }

            if (is_int($data['duration'] ?? null) && is_int($data['time'] ?? null)) {
                $tryAgainAfter = $data['time'] + ($data['duration'] * 60) - time();

                if ($tryAgainAfter > 0) {
                    $response->headers->set('Retry-After', (string) $tryAgainAfter);
                }
            }

            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
