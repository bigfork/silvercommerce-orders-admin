<?php

namespace SilverCommerce\OrdersAdmin\Tests\Model;

use LogicException;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Tests\Model\TestCustomisableProduct;
use SilverCommerce\OrdersAdmin\Tests\Model\TestCustomisationOption;

class TestCustomisation extends DataObject implements TestOnly
{
    private static $table_name = "TestCustomisation";

    private static $db = [
        'Title'     => 'Varchar'
    ];

    private static $has_one = [
        'Parent'    => TestCustomisableProduct::class
    ];

    private static $has_many = [
        'Options'   => TestCustomisationOption::class
    ];
}