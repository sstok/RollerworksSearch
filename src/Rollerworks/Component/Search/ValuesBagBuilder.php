<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

class ValuesBagBuilder extends ValuesBag
{
    /**
     * @var SearchConditionBuilder
     */
    protected $parent;

    /**
     * Constructor.
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        parent::__construct();
    }

    /**
     * @return SearchConditionBuilder
     */
    public function end()
    {
        return $this->parent;
    }
}
