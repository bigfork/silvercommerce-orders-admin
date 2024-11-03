<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\i18n\i18n;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use SilverCommerce\GeoZones\Model\Zone;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Model\PriceModifier;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverCommerce\OrdersAdmin\Tests\Model\TestProduct;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;
use SilverCommerce\OrdersAdmin\Model\LineItemCustomisation;
use SilverCommerce\OrdersAdmin\Tests\Model\TestCustomisation;
use SilverCommerce\OrdersAdmin\Tests\Model\TestCustomisableProduct;
use SilverCommerce\OrdersAdmin\Tests\Model\TestCustomisationOption;

class LineItemFactoryTest extends SapphireTest
{
    protected static $fixture_file = 'FactoryScaffold.yml';

    protected static $extra_dataobjects = [
        TestProduct::class,
        TestCustomisation::class,
        TestCustomisationOption::class
    ];

    public function setUp(): void
    {
        // Ensure we setup a session and the current request
        $request = new HTTPRequest('GET', '/');
        $session = new Session(null);
        $session->init($request);
        $request->setSession($session);
        Injector::inst()
            ->registerService($request, HTTPRequest::class);

        i18n::set_locale('en_GB');

        parent::setUp();
    }

    public function testMakeItem()
    {
        $socks = $this->objFromFixture(CatalogueProduct::class, 'socks');
        $basic_item = LineItemFactory::create()
            ->setProduct($socks)
            ->setQuantity(3)
            ->makeItem()
            ->getItem();

        $this->assertNotEmpty($basic_item);
        $this->assertEquals("Socks", $basic_item->Title);
        $this->assertEquals(3, $basic_item->Quantity);
        $this->assertEquals(5.99, $basic_item->BasePrice);
        $this->assertEquals(17.97, $basic_item->Total);
        $this->assertEquals(CatalogueProduct::class, $basic_item->ProductClass);

        $notax = $this->objFromFixture(CatalogueProduct::class, 'notax');
        $notax_item = LineItemFactory::create()
            ->setProduct($notax)
            ->setQuantity(5)
            ->makeItem()
            ->getItem();

        $this->assertNotEmpty($notax_item);
        $this->assertEquals("No Tax Item", $notax_item->Title);
        $this->assertEquals(5, $notax_item->Quantity);
        $this->assertEquals(6.50, $notax_item->BasePrice);
        $this->assertEquals(32.50, $notax_item->Total);
        $this->assertEquals(CatalogueProduct::class, $notax_item->ProductClass);

        /** Test item creation using new versioned products */
        $versioned_product_one = CatalogueProduct::create();
        $versioned_product_one->Title = "Simple Versioned Product";
        $versioned_product_one->StockID = "SVP-1";
        $versioned_product_one->BasePrice = 6.75;
        $versioned_product_one->write();

        $versioned_item = LineItemFactory::create()
            ->setProduct($versioned_product_one)
            ->setQuantity(1)
            ->makeItem()
            ->getItem();

        $item_id = $versioned_item->write();
        $versioned_product_one->BasePrice = 7.50;
        $versioned_product_one->write();

        $versioned_item = LineItem::get()->byID($item_id);
        $this->assertNotEmpty($versioned_item);

        $versioned_product_one = $versioned_item->findStockItem();
        $this->assertNotEmpty($versioned_product_one);
        $this->assertEquals(1, $versioned_item->ProductVersion);
        $this->assertEquals(1, $versioned_product_one->Version);

        $this->assertEquals("Simple Versioned Product", $versioned_item->Title);
        $this->assertEquals(1, $versioned_item->Quantity);
        $this->assertEquals(6.75, $versioned_item->BasePrice);
        $this->assertEquals(6.75, $versioned_item->Total);
        $this->assertEquals(CatalogueProduct::class, $versioned_item->ProductClass);

        /** Test item creation using new versioned product between price changes */
        $versioned_product_two = CatalogueProduct::create();
        $versioned_product_two->Title = "Another Versioned Product";
        $versioned_product_two->StockID = "AVP-1";
        $versioned_product_two->BasePrice = 5.25;
        $versioned_product_two->write();

        $versioned_product_two->BasePrice = 7.50;
        $versioned_product_two->write();

        $versioned_item = LineItemFactory::create()
            ->setProduct($versioned_product_two)
            ->setQuantity(3)
            ->makeItem()
            ->getItem();
        $item_id = $versioned_item->write();

        $versioned_product_two->BasePrice = 6.99;
        $versioned_product_two->write();

        $versioned_item = LineItem::get()->byID($item_id);
        $this->assertNotEmpty($versioned_item);

        $this->assertEquals(3, $versioned_product_two->Version);
        $versioned_product_two = $versioned_item->findStockItem();
        $this->assertNotEmpty($versioned_product_two);
        $this->assertEquals(2, $versioned_item->ProductVersion);
        $this->assertEquals(2, $versioned_product_two->Version);

        $this->assertEquals("Another Versioned Product", $versioned_item->Title);
        $this->assertEquals(3, $versioned_item->Quantity);
        $this->assertEquals(7.50, $versioned_item->BasePrice);
        $this->assertEquals(22.5, $versioned_item->Total);
        $this->assertEquals(CatalogueProduct::class, $versioned_item->ProductClass);

        $this->expectException(ValidationException::class);
        LineItemFactory::create()->makeItem();
    }

    public function testUpdate()
    {
        $socks = $this->objFromFixture(CatalogueProduct::class, 'socks');
        $notax = $this->objFromFixture(CatalogueProduct::class, 'notax');

        $factory = LineItemFactory::create()
            ->setProduct($socks)
            ->setQuantity(1)
            ->makeItem();
    
        $this->assertEquals(1, $factory->getItem()->Quantity);
        $this->assertEquals(5.99, $factory->getItem()->BasePrice);

        $factory->setQuantity(3);
        $this->assertEquals(1, $factory->getItem()->Quantity);

        $factory->update();
        $this->assertEquals(3, $factory->getItem()->Quantity);

        $factory->setProduct($notax)->update();
        $this->assertEquals(3, $factory->getItem()->Quantity);
        $this->assertEquals(6.50, $factory->getItem()->BasePrice);
    }

    public function testCustomise()
    {
        $item = /** Test item creation using new versioned products */
        $product = CatalogueProduct::create();
        $product->Title = "A-Nother Product";
        $product->StockID = "ANP-222";
        $product->BasePrice = 2.50;
        $product->write();

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->makeItem()
            ->write();

        $customisation = $factory->customise('Colour', "Blue");

        $this->assertNotEmpty($customisation);
        $this->assertInstanceOf(LineItemCustomisation::class, $customisation);
        $this->assertEquals("Colour", $customisation->Title);
        $this->assertEquals("Blue", $customisation->Value);

        $this->assertCount(1, $factory->getItem()->Customisations());
    }

    public function testModifyPrice()
    {
        $item = /** Test item creation using new versioned products */
        $product = CatalogueProduct::create();
        $product->Title = "A-Nother Product";
        $product->StockID = "ANP-333";
        $product->BasePrice = 2.50;
        $product->write();

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->makeItem()
            ->write();

        $modification = $factory->modifyPrice('Large', 1.50);
        $item = $factory->getItem();

        $this->assertNotEmpty($modification);
        $this->assertInstanceOf(PriceModifier::class, $modification);
        $this->assertEquals("Large", $modification->Name);
        $this->assertEquals(1.5, $modification->ModifyPrice);
        $this->assertEquals(2.5, $item->BasePrice);
        $this->assertEquals(4, $item->NoTaxPrice);

        $this->assertCount(1, $item->PriceModifications());
    }

    public function testAutomaticCustomisation()
    {
        $product = $this->objFromFixture(
            TestCustomisableProduct::class,
            'predefined'
        );
        $colour = $this->objFromFixture(
            TestCustomisation::class,
            'colour'
        );
        $red = $this->objFromFixture(
            TestCustomisationOption::class,
            'colour_red'
        );
        $blue = $this->objFromFixture(
            TestCustomisationOption::class,
            'colour_blue'
        );
        $green = $this->objFromFixture(
            TestCustomisationOption::class,
            'colour_green'
        );

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->setExtraData([$colour->ID => $red->ID])
            ->makeItem()
            ->write();

        $item = $factory->getItem();

        $this->assertCount(1, $item->Customisations());
        $this->assertEquals(
            'Colour',
            $item->Customisations()->first()->Title
        );
        $this->assertEquals(
            'Red',
            $item->Customisations()->first()->Value
        );

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->setExtraData([$colour->ID => $blue->ID])
            ->makeItem()
            ->write();

        $item = $factory->getItem();

        $this->assertCount(1, $item->Customisations());
        $this->assertEquals(
            'Colour',
            $item->Customisations()->first()->Title
        );
        $this->assertEquals(
            'Blue',
            $item->Customisations()->first()->Value
        );

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->setExtraData([$colour->ID => $red->ID])
            ->makeItem()
            ->write();

        $item = $factory->getItem();

        $this->assertCount(1, $item->Customisations());
        $this->assertEquals(
            'Colour',
            $item->Customisations()->first()->Title
        );
        $this->assertEquals(
            'Red',
            $item->Customisations()->first()->Value
        );
    }

    public function testAutomaticPriceModification()
    {
        $product = $this->objFromFixture(
            TestCustomisableProduct::class,
            'chargable'
        );
        $size = $this->objFromFixture(
            TestCustomisation::class,
            'size'
        );
        $sm = $this->objFromFixture(
            TestCustomisationOption::class,
            'size_sm'
        );
        $md = $this->objFromFixture(
            TestCustomisationOption::class,
            'size_md'
        );
        $lg = $this->objFromFixture(
            TestCustomisationOption::class,
            'size_lg'
        );

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->setExtraData([$size->ID => $sm->ID])
            ->makeItem()
            ->write();

        $item = $factory->getItem();

        $this->assertCount(1, $item->PriceModifications());
        $this->assertEquals(
            13.25,
            $item->getNoTaxPrice()
        );
        $this->assertEquals(
            'Size',
            $item->PriceModifications()->first()->Title
        );

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->setExtraData([$size->ID => $md->ID])
            ->makeItem()
            ->write();

        $item = $factory->getItem();

        $this->assertCount(1, $item->PriceModifications());
        $this->assertEquals(
            14.50,
            $item->getNoTaxPrice()
        );
        $this->assertEquals(
            'Size',
            $item->PriceModifications()->first()->Title
        );

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->setExtraData([$size->ID => $lg->ID])
            ->makeItem()
            ->write();

        $item = $factory->getItem();

        $this->assertCount(1, $item->PriceModifications());
        $this->assertEquals(
            16.00,
            $item->getNoTaxPrice()
        );
        $this->assertEquals(
            'Size',
            $item->PriceModifications()->first()->Title
        );
    }

    public function testFindBestTaxRate()
    {
        // First test a static rate
        $item = $this->objFromFixture(LineItem::class, 'taxitemone');
        $estimate = $item->Parent();
        $factory = LineItemFactory::create()
            ->setItem($item)
            ->setParent($estimate);

        $this->assertEquals(20, $item->TaxPercentage);
        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "NZ";
        $estimate->DeliveryCounty = "AUK";
        $factory->setParent($estimate);

        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "US";
        $estimate->DeliveryCounty = "AL";
        $factory->setParent($estimate);

        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "DE";
        $estimate->DeliveryCounty = "BE";
        $factory->setParent($estimate);

        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        // Now test a more flexible category
        $item = $this->objFromFixture(LineItem::class, 'taxtestableuk');
        $estimate = $item->Parent();

        $factory = LineItemFactory::create()
            ->setItem($item)
            ->setParent($estimate);
        
        $rate = $factory->findBestTaxRate();

        $this->assertEquals(0, $item->TaxPercentage);
        $this->assertEquals(20, $rate->Rate);

        $estimate->DeliveryCountry = "NZ";
        $estimate->DeliveryCounty = "AUK";
        $factory->setParent($estimate);

        $this->assertEquals(5, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "US";
        $estimate->DeliveryCounty = "AL";
        $factory->setParent($estimate);

        $this->assertEquals(0, $factory->findBestTaxRate()->Rate);

        // Erronious result should return 0
        $estimate->DeliveryCountry = "DE";
        $estimate->DeliveryCounty = "BE";
        $factory->setParent($estimate);

        $this->assertEquals(0, $factory->findBestTaxRate()->Rate);
    }

    public function testCheckStockLevel()
    {
        $item = $this->objFromFixture(LineItem::class, 'sockitem');
        $factory = LineItemFactory::create()
            ->setItem($item);

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(5)
            ->update();

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(9)
            ->update();

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(10)
            ->update();

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(15)
            ->update();

        $this->assertFalse($factory->checkStockLevel());

        $factory
            ->setQuantity(20)
            ->update();

        $this->assertFalse($factory->checkStockLevel());
    }
}
