<?php

namespace SilverCommerce\OrdersAdmin\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\FieldType\DBCurrency;

class TestCustomisationOption extends DataObject implements TestOnly
{
    private static $table_name = "TestCustomisationOption";

    private static $db = [
        'Title'         => 'Varchar',
        'ModifyPrice'   => 'Decimal'
    ];

    private static $has_one = [
        "Parent"        => TestCustomisation::class
    ];

    private static $default_sort = 'Sort ASC';
}
