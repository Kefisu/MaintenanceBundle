<?php

namespace Kefisu\Bundle\MaintenanceBundle\Service;

use Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeAlreadyActiveException;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeNotActiveException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CacheBasedMaintenanceManager implements MaintenanceManagerInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidArgumentException|\Random\RandomException
     */
    public function enable(?int $duration = null, int $statusCode = 503): void
    {
        if ($this->isActive()) {
            throw new MaintenanceModeAlreadyActiveException(
                message: 'Cannot enable maintenance mode because it is already active.'
            );
        }

        $this->cache->save(
            $this->cache->getItem('maintenance')->set([
                'time' => time(),
                'duration' => $duration,
                'statusCode' => $statusCode,
                'secret' => bin2hex(random_bytes(16)),
            ])
        );
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidArgumentException
     */
    public function disable(): void
    {
        if ($this->isActive()) {
            $this->cache->deleteItem('maintenance');
        }

        throw new MaintenanceModeNotActiveException(
            message: 'Cannot disable maintenance mode because it is not active.'
        );
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidArgumentException
     */
    public function isActive(): bool
    {
        return $this->cache->getItem('maintenance')->isHit();
    }

    /**
     * @inheritDoc
     */
    public function getSecret(): ?string
    {
        $data = $this->getData();

        if (isset($data['secret'])) {
            return $data['secret'];
        }

        return null;
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidArgumentException
     */
    public function getData(): array
    {
        if ($this->isActive() === false) {
            throw new MaintenanceModeNotActiveException(
                message: 'Cannot get data because maintenance mode is not active.'
            );
        }

        return $this->cache->getItem('maintenance')->get();
    }

    /**
     * @inheritDoc
     */
    public function validateSecret(string $secret): bool
    {
        $storedSecret = $this->getSecret();

        return is_string($storedSecret) && hash_equals($storedSecret, $secret);
    }
}
