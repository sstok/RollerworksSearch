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

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Exports the SearchCondition as a structured PHP Array.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayExporter extends AbstractExporter
{
    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param bool        $isRoot
     *
     * @return array
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $isRoot = false)
    {
        $result = [];
        $fields = $valuesGroup->getFields();

        foreach ($fields as $name => $values) {
            if (0 === $values->count()) {
                continue;
            }

            $exportedValue = $this->exportValues($values, $fieldSet->get($name));

            // Only export fields with actual values.
            if (count($exportedValue) > 0) {
                $result['fields'][$name] = $exportedValue;
            }
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $result['groups'][] = $this->exportGroup($group, $fieldSet, false);
        }

        if (isset($result['fields']) && ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical()) {
            $result['logical-case'] = 'OR';
        }

        return $result;
    }

    /**
     * @param ValuesBag            $valuesBag
     * @param FieldConfigInterface $field
     *
     * @return string
     */
    protected function exportValues(ValuesBag $valuesBag, FieldConfigInterface $field)
    {
        $exportedValues = [];

        foreach ($valuesBag->getSimpleValues() as $value) {
            $exportedValues['simple-values'][] = $this->modelToNorm($value, $field);
        }

        foreach ($valuesBag->getExcludedSimpleValues() as $value) {
            $exportedValues['excluded-simple-values'][] = $this->modelToNorm($value, $field);
        }

        foreach ($valuesBag->get(Range::class) as $value) {
            $exportedValues['ranges'][] = $this->exportRangeValue($value, $field);
        }

        foreach ($valuesBag->get(ExcludedRange::class) as $value) {
            $exportedValues['excluded-ranges'][] = $this->exportRangeValue($value, $field);
        }

        foreach ($valuesBag->get(Compare::class) as $value) {
            $exportedValues['comparisons'][] = [
                'operator' => $value->getOperator(),
                'value' => $this->modelToNorm($value->getValue(), $field),
            ];
        }

        foreach ($valuesBag->get(PatternMatch::class) as $value) {
            $exportedValues['pattern-matchers'][] = [
                'type' => $this->getPatternMatchType($value),
                'value' => $value->getValue(),
                'case-insensitive' => $value->isCaseInsensitive(),
            ];
        }

        return $exportedValues;
    }

    /**
     * @param Range                $range
     * @param FieldConfigInterface $field
     *
     * @return array
     */
    protected function exportRangeValue(Range $range, FieldConfigInterface $field)
    {
        $result = [
            'lower' => $this->modelToNorm($range->getLower(), $field),
            'upper' => $this->modelToNorm($range->getUpper(), $field),
        ];

        if (!$range->isLowerInclusive()) {
            $result['inclusive-lower'] = false;
        }

        if (!$range->isUpperInclusive()) {
            $result['inclusive-upper'] = false;
        }

        return $result;
    }
}
