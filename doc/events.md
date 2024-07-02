# Events

Available events:

- `LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE`: triggered before restoring the backup
- `LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE`: triggered after the backup has been restored
- `LiipTestFixturesEvents::POST_FIXTURE_SETUP`: triggered before purging the database
- `LiipTestFixturesEvents::PRE_REFERENCE_SAVE`: triggered before saving the backup of fixtures
- `LiipTestFixturesEvents::POST_REFERENCE_SAVE`: triggered before the backup of fixtures has been saved

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

« [Database](./database.md) • [Examples](./examples.md) »
