<?php

namespace Kefisu\Bundle\MaintenanceBundle\Service;

use Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeAlreadyActiveException;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeNotActiveException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FileBasedMaintenanceManager implements MaintenanceManagerInterface
{
    private string $cacheFileDir;

    /** @var array{time: int, duration: int|null, statusCode: int, secret: string} */
    private static array $data;

    public function __construct(
        #[Autowire('%kernel.cache_dir%')] private string $cacheDir,
    )
    {
        $this->cacheFileDir = sprintf('%s/maintenance', $this->cacheDir);
    }

    public function enable(?int $duration = null, int $statusCode = 503): void
    {
        if ($this->isActive()) {
            throw new MaintenanceModeAlreadyActiveException(
                message: 'Cannot enable maintenance mode because it is already active.'
            );
        }

        file_put_contents(
            $this->cacheFileDir,
            json_encode([
                'time' => time(),
                'duration' => $duration,
                'statusCode' => $statusCode,
                'secret' => bin2hex(random_bytes(16)),
            ], JSON_PRETTY_PRINT)
        );
    }

    public function disable(): void
    {
        if ($this->isActive()) {
            unlink($this->cacheFileDir);

            return;
        }

        throw new MaintenanceModeNotActiveException(
            message: 'Cannot disable maintenance mode because it is not active.'
        );
    }

    public function isActive(): bool
    {
        return file_exists($this->cacheFileDir);
    }

    /**
     * @throws \JsonException
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
     * @throws \JsonException
     */
    public function getData(): array
    {
        if ($this->isActive() === false) {
            throw new MaintenanceModeNotActiveException(
                message: 'Cannot get secret because maintenance mode is not active.'
            );
        }

        if (isset(self::$data) === false) {
            self::$data = json_decode(file_get_contents($this->cacheFileDir), true, 512, JSON_THROW_ON_ERROR);
        }

        return self::$data;
    }

    /**
     * @throws \JsonException
     */
    public
    function validateSecret(string $secret): bool
    {
        $storedSecret = $this->getSecret();

        if (is_string($storedSecret)) {
            return hash_equals($storedSecret, $secret);
        }

        return false;
    }
}