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

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessorInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

// TODO Add some tests with empty fields and groups (and they should be able to process)
abstract class SearchConditionExporterTestCase extends SearchIntegrationTestCase
{
    /**
     * @return ExporterInterface
     */
    abstract protected function getExporter();

    /**
     * @return InputProcessorInterface
     */
    abstract protected function getInputProcessor();

    /**
     * {@inheritdoc}
     */
    protected function getFieldSet($build = true)
    {
        $fieldSet = new FieldSetBuilder($this->getFactory());
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('name', TextType::class);
        $fieldSet->add('lastname', TextType::class);
        $fieldSet->add('date', DateType::class, ['format' => 'MM-dd-yyyy']);

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @test
     */
    public function it_exporters_values()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value ');
        $values->addSimpleValue('-value2');
        $values->addSimpleValue('value2-');
        $values->addSimpleValue('10.00');
        $values->addSimpleValue('10,00');
        $values->addSimpleValue('hÌ');
        $values->addSimpleValue('٤٤٤٦٥٤٦٠٠');
        $values->addSimpleValue('doctor"who""');
        $values->addExcludedSimpleValue('value3');

        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideSingleValuePairTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideSingleValuePairTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideSingleValuePairTest();

    /**
     * @test
     */
    public function it_exporters_multiple_fields()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addSimpleValue($date);
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMultipleValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideSingleValuePairTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideMultipleValuesTest();

    /**
     * @test
     */
    public function it_exporters_range_values()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new Range(1, 10));
        $values->add(new Range(15, 30));
        $values->add(new Range(100, 200, false));
        $values->add(new Range(310, 400, true, false));
        $values->add(new ExcludedRange(50, 70));
        $expectedGroup->addField('id', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');
        $date2 = new \DateTime('2014-12-20 00:00:00 UTC');

        $values = new ValuesBag();
        $values->add(new Range($date, $date2, true, true));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideRangeValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideSingleValuePairTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideRangeValuesTest();

    /**
     * @test
     */
    public function it_exporters_comparisons()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new Compare(1, '>'));
        $values->add(new Compare(2, '<'));
        $values->add(new Compare(5, '<='));
        $values->add(new Compare(8, '>='));
        $expectedGroup->addField('id', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->add(new Compare($date, '>='));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideComparisonValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideComparisonValuesTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideComparisonValuesTest();

    /**
     * @test
     */
    public function it_exporters_matchers()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new PatternMatch('value', PatternMatch::PATTERN_CONTAINS));
        $values->add(new PatternMatch('value2', PatternMatch::PATTERN_STARTS_WITH, true));
        $values->add(new PatternMatch('value3', PatternMatch::PATTERN_ENDS_WITH));
        $values->add(new PatternMatch('^foo|bar?', PatternMatch::PATTERN_REGEX));
        $values->add(new PatternMatch('value4', PatternMatch::PATTERN_NOT_CONTAINS));
        $values->add(new PatternMatch('value5', PatternMatch::PATTERN_NOT_CONTAINS, true));
        $values->add(new PatternMatch('value9', PatternMatch::PATTERN_EQUALS));
        $values->add(new PatternMatch('value10', PatternMatch::PATTERN_NOT_EQUALS));
        $values->add(new PatternMatch('value11', PatternMatch::PATTERN_EQUALS, true));
        $values->add(new PatternMatch('value12', PatternMatch::PATTERN_NOT_EQUALS, true));
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMatcherValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideMatcherValuesTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideMatcherValuesTest();

    /**
     * @test
     */
    public function it_exporters_groups()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $values = new ValuesBag();
        $values->addSimpleValue('value3');
        $values->addSimpleValue('value4');

        $subGroup = new ValuesGroup();
        $subGroup->addField('name', $values);
        $expectedGroup->addGroup($subGroup);

        $values = new ValuesBag();
        $values->addSimpleValue('value8');
        $values->addSimpleValue('value10');

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('name', $values);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideGroupTest();

    /**
     * @test
     */
    public function it_exporters_multiple_subgroups()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');

        $subGroup = new ValuesGroup();
        $subGroup->addField('name', $values);

        $values = new ValuesBag();
        $values->addSimpleValue('value3');
        $values->addSimpleValue('value4');
        $expectedGroup->addGroup($subGroup);

        $subGroup2 = new ValuesGroup();
        $subGroup2->addField('name', $values);
        $expectedGroup->addGroup($subGroup2);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMultipleSubGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideMultipleSubGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideMultipleSubGroupTest();

    /**
     * @test
     */
    public function it_exporters_nested_subgroups()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();
        $nestedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $nestedGroup->addField('name', $values);

        $subGroup = new ValuesGroup();
        $subGroup->addGroup($nestedGroup);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideNestedGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideNestedGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideNestedGroupTest();

    /**
     * @test
     */
    public function it_exporters_with_empty_fields()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideEmptyValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideEmptyValuesTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideEmptyValuesTest();

    /**
     * @test
     */
    public function it_exporters_with_empty_group()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addGroup(new ValuesGroup());

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideEmptyGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideEmptyGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideEmptyGroupTest();

    protected function assertExportEquals($expected, $actual)
    {
        self::assertEquals($expected, $actual);
    }
}
