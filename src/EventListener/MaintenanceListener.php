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

            $secret = $request->get('secret');
            if (is_string($secret) && $this->maintenanceManager->validateSecret($secret)) {
                return;
            }

            $response = new Response('Maintenance mode', 503);

            $data = $this->maintenanceManager->getData();

            if (empty($data['statusCode']) === false) {
                $response->setStatusCode($this->maintenanceManager->getData()['statusCode']);
            }

            if (empty($data['duration']) === false && empty($data['time']) === false) {
                $response->headers->set('Retry-After', ($data['time'] + ($data['duration'] * 60) - time()));
            }

            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}