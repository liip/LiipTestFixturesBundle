# Events

Available events:

- `LiipTestFixturesEvents::POST_FIXTURE_SETUP`
- `LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE`
- `LiipTestFixturesEvents::preFixtureBackupRestore`
- `LiipTestFixturesEvents::postReferenceSave`
- `LiipTestFixturesEvents::PRE_REFERENCE_SAVE`

## Registering events

### Add a class to subscribe to the event:

```php
<?php

declare(strict_types=1);

namespace Liip\Acme\Tests\AppConfigEvents\EventListener;

use Liip\TestFixturesBundle\Event\PreFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixturesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE => 'preFixtureBackupRestore',
        ];
    }

    public function preFixtureBackupRestore(PreFixtureBackupRestoreEvent $preFixtureBackupRestoreEvent): void
    {
        $manager = $preFixtureBackupRestoreEvent->getManager();
        $repository = $preFixtureBackupRestoreEvent->getRepository();
        $backupFilePath = $preFixtureBackupRestoreEvent->getBackupFilePath();

        // your code
    }
}
```

[Examples](../tests/AppConfigEvents/EventListener/FixturesSubscriber.php)

### Register the service in your tests configuration

```yaml
services:
    'Liip\Acme\Tests\AppConfigEvents\EventListener\FixturesSubscriber':
        tags:
            - { name: kernel.event_subscriber }
```
