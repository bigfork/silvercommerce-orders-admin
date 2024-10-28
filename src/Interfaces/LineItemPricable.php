<?php

namespace SilverCommerce\OrdersAdmin\Interfaces;

use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverStripe\ORM\SS_List;

/**
 * Items that implement this class are responsible for calculating
 * a modifier that can be applied to a @link LineItem via @link LineItemFactory
 */
interface LineItemPricable
{
    /**
     * Modify price of a @link LineItem, via provided factory
     * with optional additional data
     */
    public function modifyItemPrice(
        LineItemFactory $factory,
        array $data = []
    ): SS_List;
}