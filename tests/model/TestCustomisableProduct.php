<?php

namespace SilverCommerce\OrdersAdmin\Tests\Model;

use SilverStripe\ORM\SS_List;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverCommerce\OrdersAdmin\Interfaces\LineItemPricable;
use SilverCommerce\OrdersAdmin\Interfaces\LineItemCustomisable;

class TestCustomisableProduct extends TestProduct implements TestOnly, LineItemCustomisable, LineItemPricable
{
    private static $table_name = "TestCustomisableProduct";

    private static $has_many = [
        "Customisations" => TestCustomisation::class
    ];

    public function modifyItemPrice(
        LineItemFactory $factory,
        array $data = []
    ): void {
        $item = $factory->getItem();
        $product = $item->findStockItem();

        foreach ($data as $id => $value) {
            $customisation = $product->Customisations()->byID($id);

            if (empty($customisation)) {
                continue;
            }

            $option = $customisation
                ->Options()
                ->byID($value);

            if (empty($option)) {
                continue;
            }

            $factory->modifyPrice(
                $customisation->Title,
                (float)$option->ModifyPrice,
                $product
            );
        }

        return;
    }

    public function customiseLineItem(
        LineItemFactory $factory,
        array $data = []
    ): void {
        $item = $factory->getItem();
        $product = $item->findStockItem();

        foreach ($data as $id => $value) {
            $customisation = $product
                ->Customisations()
                ->byID($id);

            if (empty($customisation)) {
                continue;
            }

            $option = $customisation
                ->Options()
                ->byID($value);

            if (empty($option)) {
                continue;
            }

            $factory->customise(
                $customisation->Title,
                $option->Title,
                [],
                $customisation
            );
        }

        return;
    }
}
