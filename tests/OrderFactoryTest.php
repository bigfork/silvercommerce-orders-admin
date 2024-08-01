<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Factory\OrderFactory;
use SilverCommerce\OrdersAdmin\Tests\Model\TestProduct;

class OrderFactoryTest extends SapphireTest
{
    /**
     * Add some scaffold order records
     *
     * @var string
     */
    protected static $fixture_file = 'OrdersScaffold.yml';

    /**
     * Setup test only objects
     *
     * @var array
     */
    protected static $extra_dataobjects = [
        TestProduct::class
    ];

    public function testFindBestPrefix()
    {
        $config = SiteConfig::current_site_config();
        $config->EstimateNumberPrefix = "est";
        $config->InvoiceNumberPrefix = "inv";
        $config->write();

        $estimate = Estimate::create();
        $invoice = Invoice::create();

        $prefix = OrderFactory::findBestPrefix($estimate);
        $this->assertEquals('est', $prefix);
    
        $prefix = OrderFactory::findBestPrefix($estimate);
        $this->assertEquals('est', $prefix);
    }

    public function testGenerateRandomString()
    {
        $result = OrderFactory::generateRandomString(10);
        $this->assertIsString($result);
        $this->assertEquals(10, strlen($result));

        $result = OrderFactory::generateRandomString(20);
        $this->assertIsString($result);
        $this->assertEquals(20, strlen($result));

        $result = OrderFactory::generateRandomString(40);
        $this->assertIsString($result);
        $this->assertEquals(40, strlen($result));

        $result = OrderFactory::generateRandomString(255);
        $this->assertIsString($result);
        $this->assertEquals(255, strlen($result));
    }

    public function testValidOrderRef()
    {
        $result = OrderFactory::validOrderRef(1231, Estimate::class);
        $this->assertFalse($result);

        $result = OrderFactory::validOrderRef(5555, Invoice::class);
        $this->assertFalse($result);

        $result = OrderFactory::validOrderRef(9999, Estimate::class);
        $this->assertTrue($result);

        $result = OrderFactory::validOrderRef(9999, Invoice::class);
        $this->assertTrue($result);
    }

    public function testFindLastRef()
    {
        $factory = OrderFactory::create();
        $this->assertEquals(1237, $factory->findLastRef());

        $factory = OrderFactory::create(true);
        $this->assertEquals(5555, $factory->findLastRef());
    }

    public function testCalculateNextRef()
    {
        $factory = OrderFactory::create();
        $this->assertEquals(1238, $factory->calculateNextRef());

        $factory = OrderFactory::create(true);
        $this->assertEquals(5556, $factory->calculateNextRef());
    }

    public function testFindOrMake()
    {
        $existing = $this->objFromFixture(Estimate::class, 'addressdetails_uk');
        $new_estimate = OrderFactory::create();
        $new_invoice = OrderFactory::create(true);
        $id = OrderFactory::create(false, $existing->ID);
        $ref = OrderFactory::create(false, null, '1232');

        $this->assertNotEmpty($new_estimate->getOrder());
        $this->assertEquals(Estimate::class, $new_estimate->getOrder()->ClassName);
        $this->assertFalse($new_estimate->getOrder()->exists());
        $this->assertEquals(0, $new_estimate->getOrder()->ID);

        $this->assertNotEmpty($new_invoice->getOrder());
        $this->assertEquals(Invoice::class, $new_invoice->getOrder()->ClassName);
        $this->assertFalse($new_invoice->getOrder()->exists());
        $this->assertEquals(0, $new_invoice->getOrder()->ID);

        $this->assertNotEmpty($id->getOrder());
        $this->assertTrue($id->getOrder()->exists());
        $this->assertEquals($existing->ID, $id->getOrder()->ID);

        $this->assertNotEmpty($ref->getOrder());
        $this->assertTrue($ref->getOrder()->exists());
        $this->assertEquals('1232', $ref->getOrder()->Ref);
    }
}
