<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Tools\ShippingCalculator;

/**
 * Test functionality of the postage table
 *
 * @package orders
 * @subpackage tests
 */
class PostageTest extends SapphireTest
{
	/**
	 * Add some scaffold order records
	 *
	 * @var string
	 * @config
	 */
	protected static $fixture_file = 'PostageTest.yml';

	/**
	 * Add some extra functionality on construction
	 *
	 * @return void
	 */	
	public function setUp()
    {
		parent::setUp();
	}

	/**
	 * Clean up after tear down
	 *
	 * @return void
	 */	
	public function tearDown()
    {
		parent::tearDown();
	}

	/**
	 * Test the postage results for an order with zero weight
	 * expected
	 *
	 * @return void
	 */
	public function testNoWeight()
    {
		$order = $this->objFromFixture(Invoice::class, 'noweightorder');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("Basic UK Postage", $postage_areas->first()->Title);
	}

	/**
	 * Test the postage results for a light order
	 *
	 * @return void
	 */
	public function testFirstLight()
    {
		$order = $this->objFromFixture(Invoice::class, 'lightorderone');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("Basic UK Postage", $postage_areas->first()->Title);
	}

	/**
	 * Test the postage results for a multi item order
	 *
	 * @return void
	 */
	public function testSecondLight()
    {
		$order = $this->objFromFixture(Invoice::class, 'lightordertwo');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("Light UK Postage", $postage_areas->first()->Title);
	}

	/**
	 * Test the postage results for a heavy order
	 *
	 * @return void
	 */
	public function testHeavy()
    {
		$order = $this->objFromFixture(Invoice::class, 'heavyorder');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("Heavy UK Postage", $postage_areas->first()->Title);
    }

	/**
	 * Test the postage results for an order with a different
     * postcode (offshore).
	 *
	 * @return void
	 */
	public function testOffshore()
    {
		$order = $this->objFromFixture(Invoice::class, 'offshoreorder');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("UK Offshore", $postage_areas->first()->Title);
    }

	/**
	 * Test the postage results for an order with an unrecognized
     * postcode
	 *
	 * @return void
	 */
	public function testWildcardPostcode()
    {
		$order = $this->objFromFixture(Invoice::class, 'wildcardorder');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("UK Wildcard", $postage_areas->first()->Title);
    }
 
	/**
	 * Test the postage results for an order with an unrecognized
     * postcode
	 *
	 * @return void
	 */
	public function testGlobal()
    {
		$order = $this->objFromFixture(Invoice::class, 'globalorder');

        $postage_areas = new ShippingCalculator($order->DeliveryPostCode, $order->DeliveryCountry);
        $postage_areas
            ->setCost($order->SubTotal)
            ->setWeight($order->TotalWeight)
            ->setItems($order->TotalItems);

        $postage_areas = $postage_areas->getPostageAreas();

		$this->assertEquals(1, $postage_areas->count());
		$this->assertEquals("Global Wildcard", $postage_areas->first()->Title);
	}
}