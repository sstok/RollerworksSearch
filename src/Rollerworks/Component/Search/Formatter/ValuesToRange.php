<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Formatter;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValueIncrementerInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Converts incremented values to inclusive ranges.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesToRange implements FormatterInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var ValueIncrementerInterface
     */
    private $comparison;

    /**
     * {@inheritDoc}
     */
    public function format(SearchConditionInterface $condition)
    {
        $fieldSet = $condition->getFieldSet();
        $valuesGroup = $condition->getValuesGroup();

        $optimize = false;
        foreach ($fieldSet->all() as $field) {
            if ($field->acceptRanges() && $field->getValueComparison() instanceof ValueIncrementerInterface) {
                $optimize = true;

                break;
            }

        }

        // None of the fields supports ranges or value-increments so don't optimize
        if (!$optimize) {
            return;
        }

        $this->optimizeValuesInGroup($valuesGroup, $fieldSet);
    }

    /**
     * SingleValue sorter callback.
     *
     * This method is defined at class level to
     * prevent creating a large number of closures.
     *
     * @param SingleValue $first
     * @param SingleValue $second
     *
     * @return integer
     *
     * @internal
     */
    public function valuesSorter(SingleValue $first, SingleValue $second)
    {
        $a = $first->getValue();
        $b = $second->getValue();

        if ($this->comparison->isEqual($a, $b, $this->options)) {
            return 0;
        }

        return $this->comparison->isLower($a, $b, $this->options) ? -1 : 1;
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function optimizeValuesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!$fieldSet->has($fieldName)) {
                continue;
            }

            $config = $fieldSet->get($fieldName);
            if ($config->acceptRanges() && ($values->hasSingleValues() || $values->hasExcludedValues())) {
                $this->optimizeValuesInValuesBag($config, $values);
            }
        }

        // now traverse the subgroups
        foreach ($valuesGroup->getGroups() as $group) {
            $this->optimizeValuesInGroup($group, $fieldSet);
        }
    }

    /**
     * @param FieldConfigInterface $config
     * @param ValuesBag            $valuesBag
     * @param ValuesBag            $valuesBag
     */
    private function optimizeValuesInValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $this->comparison = $config->getValueComparison();
        $this->options = $config->getOptions();

        // repress the warning about a changed array for uasort()
        // this seems to happen because we are using an object for the comparison

        if ($valuesBag->hasSingleValues()) {
            $values = $valuesBag->getSingleValues();
            @uasort($values, array($this, 'valuesSorter'));

            $this->listToRanges($values, $valuesBag, $config);
        }

        if ($valuesBag->hasExcludedValues()) {
            $excludes = $valuesBag->getExcludedValues();
            @uasort($excludes, array($this, 'valuesSorter'));

            $this->listToRanges($excludes, $valuesBag, $config, true);
        }
    }

    /**
     * Converts a list of values to ranges.
     *
     * @param SingleValue[]        $values
     * @param ValuesBag            $valuesBag
     * @param FieldConfigInterface $config
     * @param boolean              $exclude
     */
    private function listToRanges($values, ValuesBag $valuesBag, FieldConfigInterface $config, $exclude = false)
    {
        /** @var ValueIncrementerInterface $comparison */
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        $prevIndex = null;
        /** @var SingleValue $prevValue */
        $prevValue = null;

        $rangeLower = null;
        $rangeUpper = null;

        $valuesCount = count($values);
        $curCount = 0;

        foreach ($values as $valIndex => $value) {
            $curCount++;

            if (null === $prevValue) {
                $prevIndex = $valIndex;
                $prevValue = $value;

                continue;
            }

            $unsetIndex = null;
            $increasedValue = $comparison->getIncrementedValue($prevValue->getValue(), $options);

            if ($comparison->isEqual($value->getValue(), $increasedValue, $options)) {
                if (null === $rangeLower) {
                    $rangeLower = $prevValue;
                }

                $rangeUpper = $value;
            }

            if (null !== $rangeUpper) {
                $unsetIndex = $prevIndex;

                if (!$comparison->isEqual($value->getValue(), $increasedValue, $options) || $curCount === $valuesCount) {
                    $range = new Range($rangeLower->getValue(), $rangeUpper->getValue(), true, true, $rangeLower->getViewValue(), $rangeUpper->getViewValue());

                    if ($exclude) {
                        $valuesBag->addExcludedRange($range);
                    } else {
                        $valuesBag->addRange($range);
                    }

                    $unsetIndex = $prevIndex;

                    if ($comparison->isEqual($value->getValue(), $increasedValue, $options) && $curCount === $valuesCount) {
                        $unsetIndex = $valIndex;
                    }

                    $rangeLower = $rangeUpper = null;
                }

                $prevIndex = $valIndex;
                $prevValue = $value;
            }

            if (null !== $unsetIndex) {
                if ($exclude) {
                    $valuesBag->removeExcludedValue($unsetIndex);
                } else {
                    $valuesBag->removeSingleValue($unsetIndex);
                }
            }
        }
    }
}
