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

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

class MssqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * Returns the list of characters to escape.
     *
     * @return string
     */
    protected function getLikeEscapeChars(): string
    {
        return '%_[]';
    }
}
