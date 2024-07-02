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

namespace Liip\TestFixturesBundle;

use Liip\TestFixturesBundle\Event\FixtureEvent;
use Liip\TestFixturesBundle\Event\PostFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\PreFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\ReferenceSaveEvent;

final class LiipTestFixturesEvents
{
    /** @see PreFixtureBackupRestoreEvent */
    public const PRE_FIXTURE_BACKUP_RESTORE = 'liip_test_fixtures.pre_fixture_backup_restore';

    /** @see FixtureEvent */
    public const POST_FIXTURE_SETUP = 'liip_test_fixtures.post_fixture_setup';

    /** @see PostFixtureBackupRestoreEvent */
    public const POST_FIXTURE_BACKUP_RESTORE = 'liip_test_fixtures.post_fixture_backup_restore';

    /** @see ReferenceSaveEvent */
    public const PRE_REFERENCE_SAVE = 'liip_test_fixtures.pre_reference_save';

    /** @see ReferenceSaveEvent */
    public const POST_REFERENCE_SAVE = 'liip_test_fixtures.post_reference_save';
}
