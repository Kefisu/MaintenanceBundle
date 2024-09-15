<?php

namespace Kefisu\Bundle\MaintenanceBundle\Contract;

use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeAlreadyActiveException;
use Kefisu\Bundle\MaintenanceBundle\Exception\MaintenanceModeNotActiveException;

interface MaintenanceManagerInterface
{
    /**
     * Enables the maintenance mode.
     *
     * @param int|null $duration the duration in minutes for which the maintenance mode should be active
     * @param int $statusCode the status code to return when the maintenance mode is active
     *
     * @throws MaintenanceModeAlreadyActiveException if the maintenance mode is already active
     */
    public function enable(?int $duration = null, int $statusCode = 503): void;

    /**
     * Disables the maintenance mode.
     *
     * @throws MaintenanceModeNotActiveException if the maintenance mode is not active
     */
    public function disable(): void;

    /**
     * Returns whether the maintenance mode is active.
     */
    public function isActive(): bool;

    /**
     * Returns the secret that was generated when the maintenance mode was enabled.
     *
     * @throws MaintenanceModeNotActiveException if the maintenance mode is not active
     */
    public function getSecret(): ?string;

    /** @return array{time: int, duration: int|null, statusCode: int, secret: string} */
    public function getData(): array;

    /**
     * Validates the given secret against the one stored with the maintenance mode settings.
     *
     * @throws MaintenanceModeNotActiveException if the maintenance mode is not active
     */
    public function validateSecret(string $secret): bool;
}
