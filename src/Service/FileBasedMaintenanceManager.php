<?php

namespace Kefisu\Bundle\MaintenanceBundle\Service;

use JsonException;
use Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeAlreadyActiveException;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeNotActiveException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

class FileBasedMaintenanceManager implements MaintenanceManagerInterface
{
    private string $filePath;

    private static ?array $data = null;

    public function __construct(string $filePath)
    {
        $this->filePath = sprintf('%s/maintenance', $filePath);
    }

    public function enable(?int $duration = null, int $statusCode = 503): void
    {
        if ($this->isActive()) {
            throw new MaintenanceModeAlreadyActiveException(
                message: 'Cannot enable maintenance mode because it is already active.'
            );
        }

        file_put_contents(
            $this->filePath,
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
            unlink($this->filePath);

            return;
        }

        throw new MaintenanceModeNotActiveException(
            message: 'Cannot disable maintenance mode because it is not active.'
        );
    }

    public function isActive(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * @throws JsonException
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
     * @throws JsonException
     *
     * @inheritDoc
     */
    public function getData(): array
    {
        if ($this->isActive() === false) {
            throw new MaintenanceModeNotActiveException(
                message: 'Cannot get secret because maintenance mode is not active.'
            );
        }

        if (self::$data === null) {
            $fileContent = file_get_contents($this->filePath);

            if (is_string($fileContent)) {
                /** @var array{time: int, duration: int|null, statusCode: int, secret: string} $data */
                self::$data = json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return self::$data ?? [];
    }

    /**
     * @throws JsonException
     */
    public function validateSecret(string $secret): bool
    {
        $storedSecret = $this->getSecret();

        if (is_string($storedSecret)) {
            return hash_equals($storedSecret, $secret);
        }

        return false;
    }
}
