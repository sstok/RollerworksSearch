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

namespace Rollerworks\Component\Search\Doctrine\Dbal\Query;

use Doctrine\DBAL\Types\Type as DbType;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * Holds the mapping information of a search field for Doctrine DBAL.
 *
 * Information is provided in public properties for better performance.
 * This information is read-only and should not be changed afterwards.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class QueryField
{
    public readonly DbType $dbType;
    public readonly string $dbTypeName;
    public readonly string $column;
    public ?object $columnConversion;
    public ?object $valueConversion;
    public readonly string $tableColumn;

    public function __construct(
        public string $mappingName,
        public FieldConfig $fieldConfig,
        string $dbType,
        string $column,
        public ?string $alias = null,
    ) {
        $this->tableColumn = $column;
        $this->column = ($alias ? $alias . '.' : '') . $column;
        $this->dbType = DbType::getType($dbType);
        $this->dbTypeName = $dbType;

        $this->initConversions($fieldConfig);
    }

    /**
     * @return array{mapping_name: string, field: string, db_type: string}
     */
    public function __serialize(): array
    {
        return [
            'mapping_name' => $this->mappingName,
            'field' => $this->fieldConfig->getName(),
            'db_type' => $this->dbTypeName,
        ];
    }

    /**
     * @param mixed[] $data
     */
    public function __unserialize(array $data): void
    {
        // noop
    }

    protected function initConversions(FieldConfig $fieldConfig): void
    {
        $converter = $fieldConfig->getOption('doctrine_dbal_conversion');

        if ($converter instanceof \Closure) {
            $converter = $converter();
        }

        $this->columnConversion = $converter instanceof ColumnConversion ? $converter : null;
        $this->valueConversion = $converter instanceof ValueConversion ? $converter : null;
    }
}
