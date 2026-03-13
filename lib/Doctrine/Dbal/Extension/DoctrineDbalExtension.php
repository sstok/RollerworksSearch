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

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal;

use Rollerworks\Component\Search\AbstractExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\BirthdayTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\ChildCountType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\DateTimeTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\FieldTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\MoneyTypeExtension;

class DoctrineDbalExtension extends AbstractExtension
{
    protected function loadTypesExtensions(): array
    {
        return [
            new FieldTypeExtension(),
            new DateTimeTypeExtension(),
            new BirthdayTypeExtension(),
            new MoneyTypeExtension(),
        ];
    }

    protected function loadTypes(): array
    {
        return [
            new ChildCountType(),
        ];
    }
}
