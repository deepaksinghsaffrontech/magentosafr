<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Model\ResourceModel\QuotePasswords;

use Amasty\Checkout\Model\QuotePasswords;
use Amasty\Checkout\Model\ResourceModel\QuotePasswords as ResourceQuotePasswords;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(QuotePasswords::class, ResourceQuotePasswords::class);
    }
}
