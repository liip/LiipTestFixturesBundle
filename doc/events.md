# Events

Available events:

- `LiipTestFixturesEvents::POST_FIXTURE_SETUP`: triggered before purging the database

## Registering events

### Add a class to subscribe to the event:

```php
<?php

declare(strict_types=1);

namespace Liip\Acme\Tests\AppConfigEvents\EventListener;

use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixturesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LiipTestFixturesEvents::POST_FIXTURE_SETUP => 'postFixtureSetup',
        ];
    }

    public function postFixtureSetup(FixtureEvent $fixtureEvent): void
    {
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
