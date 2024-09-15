# Symfony Maintenance Bundle

The Symfony Maintenance Bundle provides a simple way to manage maintenance mode for your Symfony application. It allows
you to enable or disable maintenance mode and customize the response returned to users when the application is in
maintenance mode.

## Installation

To install the bundle use Composer:

```bash
composer require kefisu/maintenance-bundle
```

Add the bundle to your `config/bundles.php` file if Symfony doesn't do it automagically:

```php
return [
    // ...
    Kefisu\Bundle\MaintenanceBundle\MaintenanceBundle::class => ['all' => true],
];
```

## Usage

### Enable maintenance mode

To enable maintenance mode, run the following command:

```bash
php bin/console maintenance:enable
```

A secret will be generated for each maintenance mode activation. This secret can be used to bypass the maintenance mode
and access the application. The secret is displayed in the command output.

#### Customizing the maintenance mode response

You can customize the following maintenance mode response options:

- HTTP Status code, by using the --status option the HTTP Status code that is returned can be customized.
- Duration, by using the --duration option the duration of the maintenance mode can be set in minutes. This will return
  a Retry-After header with the duration in seconds. If the duration is not set, the Retry-After header will not be set.

### Disable maintenance mode

To disable maintenance mode, run the following command:

```bash
php bin/console maintenance:disable
```

### Checking maintenance mode status

To check if maintenance mode is enabled, run the following command:

```bash
php bin/console maintenance:status
```

## How it works

### Maintenance Managers

Any implementation of the `Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface` is responsible for
managing the maintenance mode status. The bundle provides a default implementation of this interface for:

- `Kefisu\Bundle\MaintenanceBundle\Service\FileBasedMaintenanceManager` - reads and writes the maintenance mode status
  to the filesystem. The status is stored in a file named `maintenance` in the project cache directory.

### Maintenance Listener

The `Kefisu\Bundle\MaintenanceBundle\EventListener\MaintenanceListener` class listens for kernel requests and checks if
maintenance mode is active. If it is, it blocks the request and returns a 503 response.
When maintenance mode is disabled, the request is passed to the next listener.
If you use the secret generated when you enable maintenance mode, the listener will allow the request to pass through.