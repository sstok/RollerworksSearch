<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Mocks\DatabasePlatformMock;

/**
 * Extend this class class for platform specific functionality.
 */
abstract class PlatformSpecificFunction extends FunctionNode
{
    /**
     * Returns the platform name, returns a class-name if the platform could not be detected.
     *
     * @return 'mysql'|'sqlite'|'pgsql'|'oci'|'sqlsrv'|'mock'|string
     */
    protected function getPlatformName(Connection $connection): string
    {
        $platform = $connection->getDatabasePlatform();

        return match (true) {
            $platform instanceof AbstractMySQLPlatform => 'mysql',
            $platform instanceof SQLitePlatform => 'sqlite',
            $platform instanceof PostgreSQLPlatform => 'pgsql',
            $platform instanceof OraclePlatform => 'oci',
            $platform instanceof SQLServerPlatform => 'sqlsrv',
            $platform instanceof DatabasePlatformMock => 'mock',
            default => $platform::class,
        };
    }
}
