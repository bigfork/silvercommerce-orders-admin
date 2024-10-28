<?php

namespace SilverCommerce\OrdersAdmin\Interfaces;

use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverStripe\ORM\SS_List;

/**
 * Items that implement this class are responsible for calculating
 * a modifier that can be applied to a @link LineItem via @link LineItemFactory
 */
interface LineItemCustomisable
{
    /**
     * Customise a @link LineItem via the provided factory
     * with optional additional data
     */
    public function customiseLineItem(
        LineItemFactory $factory,
        array $data = []
    ): SS_List;
}