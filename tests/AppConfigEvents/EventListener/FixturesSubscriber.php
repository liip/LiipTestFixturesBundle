<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\Acme\Tests\AppConfigEvents\EventListener;

use Liip\TestFixturesBundle\Event\FixtureEvent;
use Liip\TestFixturesBundle\Event\PostFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\PreFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\ReferenceSaveEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixturesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE => 'preFixtureBackupRestore',
            LiipTestFixturesEvents::POST_FIXTURE_SETUP => 'postFixtureSetup',
            LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE => 'postFixtureBackupRestore',
            LiipTestFixturesEvents::PRE_REFERENCE_SAVE => 'preReferenceSave',
            LiipTestFixturesEvents::POST_REFERENCE_SAVE => 'postReferenceSave',
        ];
    }

    public function preFixtureBackupRestore(PreFixtureBackupRestoreEvent $preFixtureBackupRestoreEvent): void
    {
        $manager = $preFixtureBackupRestoreEvent->getManager();
        $repository = $preFixtureBackupRestoreEvent->getRepository();
        $backupFilePath = $preFixtureBackupRestoreEvent->getBackupFilePath();

        // your code
    }

    public function postFixtureSetup(FixtureEvent $fixture): void
    {
        // There are no parameters
        // your code
    }

    public function postFixtureBackupRestore(PostFixtureBackupRestoreEvent $postFixtureBackupRestoreEvent): void
    {
        $backupFilePath = $postFixtureBackupRestoreEvent->getBackupFilePath();

        // your code
    }

    public function preReferenceSave(ReferenceSaveEvent $referenceSaveEvent): void
    {
        $manager = $referenceSaveEvent->getManager();
        $executor = $referenceSaveEvent->getExecutor();
        $backupFilePath = $referenceSaveEvent->getBackupFilePath();

        // your code
    }

    public function postReferenceSave(ReferenceSaveEvent $referenceSaveEvent): void
    {
        $manager = $referenceSaveEvent->getManager();
        $executor = $referenceSaveEvent->getExecutor();
        $backupFilePath = $referenceSaveEvent->getBackupFilePath();

        // your code
    }
}
