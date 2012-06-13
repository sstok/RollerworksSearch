<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Record;

use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FieldSet;
use Rollerworks\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\Validator;
use Rollerworks\RecordFilterBundle\Type\Date;
use Rollerworks\RecordFilterBundle\Type\Number;
use Rollerworks\RecordFilterBundle\Type\Decimal;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\StatusType;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\CustomerType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Doctrine\Tests\OrmTestCase as OrmTestCaseBase;

/**
 * Test the Validation generator. Its work is generating on-the-fly subclasses of a given model.
 * As you may have guessed, this is based on the Doctrine\ORM\Proxy module.
 */
class OrmTestCase extends OrmTestCaseBase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();

        $this->translator = $this->getMock('Symfony\\Component\\Translation\\TranslatorInterface');
        $this->translator->expects($this->any())
             ->method('trans')
             ->will($this->returnCallback(function ($id) { return $id; } ));

        $this->translator->expects($this->any())
             ->method('transChoice')
             ->will($this->returnCallback(function ($id) { return $id; } ));

        $this->formatter = new Formatter($this->translator);
        $this->formatter->registerModifier(new Validator());
    }

    /**
     * @return ContainerBuilder
     */
    protected function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__ . '/../.cache',
            'kernel.charset'   => 'UTF-8',
            'kernel.debug'     => false,
        )));

        $container->set('service_container', $container);

        return $container;
    }

    /**
     * @param null|string $fieldSetId
     * @return FieldSet
     */
    function getFieldSet($fieldSetId = null)
    {
        $fieldSet = new FieldSet('test');

        if ('invoice' == $fieldSetId) {
            $fieldSet = new FieldSet('invoice');
            $fieldSet
                ->set('invoice_label',    FilterConfig::create('invoice', new InvoiceType(), false)->setPropertyRef('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'label'))
                ->set('invoice_date',     FilterConfig::create('date', new Date(), false, true, true)->setPropertyRef('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'date'))
                ->set('invoice_customer', FilterConfig::create('customer', new Number(), false, true, true)->setPropertyRef('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'customer'))
                ->set('invoice_status',   FilterConfig::create('status', new StatusType())->setPropertyRef('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'status'))
                ->set('invoice_price',    FilterConfig::create('status', new Decimal(), false, true, true)->setPropertyRef('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow', 'price'))
            ;

        } elseif ('customer' == $fieldSetId) {
            $fieldSet = new FieldSet('customer');
            $fieldSet
                ->set('customer_id', FilterConfig::create('id', new CustomerType(), false, true, true)->setPropertyRef('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer', 'id'))
            ;
        }

        return $fieldSet;
    }

    /**
     * @param string $filterQuery
     * @param string $fieldSet
     *
     * @return FilterQuery
     */
    protected function newInput($filterQuery, $fieldSet = 'invoice')
    {
        if (!$fieldSet instanceof FieldSet) {
            $fieldSet = $this->getFieldSet($fieldSet);
        }

        $input = new FilterQuery($this->translator);
        $input->setFieldSet($fieldSet);
        $input->setInput($filterQuery);

        return $input;
    }

    /**
     * Cleans whitespace from the input SQL for easy testing.
     *
     * @param string $input
     *
     * @return string
     */
    protected function cleanSql($input)
    {
        return str_replace(array("(\n", ")\n"), array('(', ')'), $input);
    }
}