<?php

namespace SilverCommerce\OrdersAdmin\Factory;

use LogicException;
use InvalidArgumentException;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverCommerce\OrdersAdmin\Traits\ExtraData;
use SilverStripe\Core\Config\Config;

class OrderFactory
{
    use Injectable, Configurable, ExtraData;

    /**
     * The class this factory uses to create an estimate
     *
     * @var string
     */
    private static $estimate_class = Estimate::class;

    /**
     * The class this factory uses to create an order
     *
     * @var string
     */
    private static $invoice_class = Invoice::class;

    /**
     * Parameter on estimates/invoices that is used as a "Reference number"
     *
     * @var string
     */
    private static $order_ref_param = "Ref";

    /**
     * Are we working with an invoice or an estimate?
     *
     * @var bool
     */
    protected $is_invoice;

    /**
     * An instance of an Invoice/Estimate
     *
     * @var \SilverCommerce\OrdersAdmin\Model\Estimate
     */
    protected $order;

    /**
     * The reference number for the invoice (if null, a new invoice is created)
     *
     * @var int
     */
    protected $ref;

    /**
     * The estimate/invoice ID (if null, a new estimate/invoice is created)
     *
     * @var int
     */
    protected $id;

    /**
     * Try to find the best prefix to use for an order number
     * based on @link SiteConfig
     * 
     * @throws LogicException
     *
     * @return string
     */
    public static function findBestPrefix(DataObject $object): string
    {
        $config = SiteConfig::current_site_config();
        $invoice_class = Config::inst()->get(static::class, 'invoice_class');
        $estimate_class = Config::inst()->get(static::class, 'estimate_class');
        $prefix = "";

        if (is_a($object, $invoice_class)) {
            $prefix = (string)$config->InvoiceNumberPrefix;
        } elseif (is_a($object, $estimate_class))  {
            $prefix = (string)$config->EstimateNumberPrefix;
        } else {
            throw new LogicException('Invalid object');
        }

        return $prefix;
    }

    /**
     * Generate a random string for use in estimate numbering
     *
     * @return string
     */
    public static function generateRandomString($length = 20): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    /**
     * Check if the provided order number is valid (not duplicated)
     *
     * @return bool
     */
    public static function validOrderRef($number, string $class): bool
    {
        $estimate_class = Config::inst()->get(static::class, 'estimate_class');

        $existing = DataObject::get($estimate_class)
            ->filter([
                "ClassName" => $class,
                "Ref" => $number
            ])->first();

        return !($existing);
    }

    /**
     * Check if the provided access key does not already exist
     * 
     * @return bool
     */
    public static function validAccessKey(string $key)
    {
        $estimate_class = Config::inst()->get(static::class, 'estimate_class');

        $existing = DataObject::get($estimate_class)
            ->filter("AccessKey", $key)
            ->first();

        return !($existing);
    }

    /**
     * Create a new instance of the factory and setup the estimate/invoice
     *
     * @param bool $invoice Is this an invoice? If not create estimate.
     * @param int  $id      Provide order id if we want existing estimate/invoice
     * @param int  $ref     Provide reference if we want existing estimate/invoice
     *
     * @return self
     */
    public function __construct($invoice = false, $id = null, $ref = null)
    {
        $this->setIsInvoice($invoice);

        if (isset($id)) {
            $this->setId($id);
        }

        if (isset($ref)) {
            $this->setRef($ref);
        }

        $this->findOrMake();
    }

    /**
     * Attempt to either find an existing order, or make a new one.
     * (based on submitted ID/Ref)
     *
     * @return self
     */
    public function findOrMake()
    {
        $ref_param = $this->config()->order_ref_param;
        $invoice = $this->getIsInvoice();
        $id = $this->getId();
        $ref = $this->getRef();
        $order = null;

        if ($invoice) {
            $class = $this->config()->invoice_class;
        } else {
            $class = $this->config()->estimate_class;
        }

        if (!empty($id)) {
            $order = DataObject::get($class)->byID($id);
        }

        if (!empty($ref)) {
            $order = DataObject::get($class)
                ->filter(
                    [
                        $ref_param => $ref,
                        'ClassName' => $class
                    ]
                )->first();
        }

        // If we have not found an order, create a new one
        if (empty($order)) {
            $order = $class::create();
        }

        $this->setOrder($order);

        return $this;
    }

    /**
     * Find the last relevent reference for the current type of object
     * 
     * @return int
     */
    public function findLastRef(): int
    {
        $order = $this->getOrder();
        $classname = $order->ClassName;
        $base = 0;

        // Get the last instance of the current class
        $last = $classname::get()
            ->filter("ClassName", $classname)
            ->sort("Ref", "DESC")
            ->first();

        // If we have a last estimate/invoice, get the ID of the last invoice
        // so we can increment
        if (isset($last)) {
            $base = (int)$last->Ref;
        }

        return $base;
    }

    public function calculateNextRef(): int
    {
        $order = $this->getOrder();
        $base = $this->findLastRef();

        while (!OrderFactory::validOrderRef($base, $order->ClassName)) {
            $base++;
        }

        return $base;
    }

    /**
     * Add a line item to the current order based on the provided product
     *
     * This item can also be customised (EG Variations, colours, sizes, etc)
     * buy providing an array of custom date in the format:
     *
     *  - Title: The name of the customisation
     *  - Value: A description of the customisation
     *  - BasePrice: Modify this item by the given amount
     *
     * @param DataObject $product Instance of the product we want to add
     * @param int        $qty     Quanty of items to add
     * @param bool       $lock    Should this item be locked (cannot change quantity)
     * @param array      $custom  List of customisations to add
     * @param bool       $deliver Is this item deliverable?
     *
     * @throws LogicException
     *
     * @return self
     */
    public function addItem(
        DataObject $product,
        int $qty = 1,
        bool $lock = false,
        bool $deliver = true
    ) {
        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity($qty)
            ->setLock($lock)
            ->setDeliverable($deliver)
            ->setExtraData($this->getExtraData())
            ->makeItem()
            ->write();

        $this->addFromLineItemFactory($factory);

        return $this;
    }

    /**
     * Add a new item to the current Estimate/Invoice from a pre-created
     * line item factory
     *
     * *NOTE* this method expects a LineItemFactory ro be pre-written
     *
     * @param LineItemFactory
     *
     * @return self
     */
    public function addFromLineItemFactory(LineItemFactory $factory)
    {
        // First check if this item exists
        $items = $this->getItems();
        $existing = null;
        $key = $factory->getKey();
        $qty = $factory->getQuantity();

        if ($items->count() > 0) {
            $existing = $items->find("Key", $key);
        }

        // If object already in the cart, update quantity and delete new item
        // else add as usual
        if (isset($existing)) {
            $this->updateItem($existing->Key, $qty);
            $factory->delete();
        } else {
            if (!$factory->checkStockLevel()) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . ".NotEnoughStock",
                        "Not enough of '{title}' available",
                        ['title' => $factory->getItem()->Title]
                    )
                );
            } else {
                $this->getItems()->add($factory->getItem());
            }
        }

        return $this;
    }

    /**
     * Update the quantity of a line item from the current order based on the
     * provided key
     *
     * @param string $key       The key of the item to remove
     * @param int    $qty       The amount to increment the item by
     * @param bool   $increment Should the quantity increase or change?
     *
     * @return self
     */
    public function updateItem(string $key, int $qty, bool $increment = true)
    {
        $item = $this->getItems()->find("Key", $key);

        if (!empty($item)) {
            $factory = LineItemFactory::create()->setItem($item)->update();
            $new_qty = ($increment) ? $factory->getQuantity() + $qty : $qty;

            $factory
                ->setQuantity($new_qty)
                ->update();

            if (!$factory->checkStockLevel()) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . ".NotEnoughStock",
                        "Not enough of '{title}' available",
                        ['title' => $factory->getItem()->Title]
                    )
                );
            }

            $factory->write();
        }

        return $this;
    }

    /**
     * Remove a line item from the current order based on the provided key
     *
     * @param string $key The key of the item to remove
     *
     * @return self
     */
    public function removeItem(string $key)
    {
        $item = $this->getItems()->find("Key", $key);

        if (!empty($item)) {
            $item->delete();
        }

        return $this;
    }

    /**
     * Add the provided customer to the Invoice/Estimate
     *
     * @param \SilverCommerce\ContactAdmin\Model\Contact $contact
     *
     * @return self
     */
    public function setCustomer(Contact $contact)
    {
        $order = $this->getOrder();

        if (isset($order)) {
            $order->CustomerID = $contact->ID;
            $this->setOrder($order);
        }

        return $this;
    }

    /**
     * Factory method to convert this estimate to an
     * order.
     *
     * This method writes and reloads the object so
     * we are now working with the new object type
     *
     * If the current object is already a type of
     * invoice, we just return the current object
     * with no further action
     *
     * @return DataObject
     */
    public function convertEstimateToInvoice(): DataObject
    {
        $order = $this->getOrder();
        $is_invoice = $this->getIsInvoice();

        if ($is_invoice) {
            return $this;
        }
    
        $id = $order->ID;
        $order->ClassName = Invoice::class;
        $order->write();
        unset($order);

        // Re-retrieve Invoice from DB (so ORM generates
        // correct object)
        $order = Invoice::get()->byID($id);

        // Re-configure invoice details
        $order->Ref = $this->calculateNextRef();
        $order->Prefix = self::findBestPrefix($order);
        $order->StartDate = null;
        $order->EndDate = null;
        $order->write();

        $this->setOrder($order);

        return $order;
    }

    /**
     * Write the currently selected order
     *
     * @return self
     */
    public function write()
    {
        $order = $this->getOrder();

        if (!empty($order)) {
            $order->write();
            $this->setOrder($order);
        }

        return $this;
    }

    /**
     * Delete the current Estimate/Invoice from the DB
     *
     * @return self
     */
    public function delete()
    {
        $order = $this->order;
        
        if (isset($order)) {
            $order->delete();
        }

        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $is_invoice = $this->getIsInvoice();
        
        if ($is_invoice === true) {
            $class = $this->config()->invoice_class;
        } else {
            $class = $this->config()->estimate_class;
        }

        if (!is_a($order, $class)) {
            throw new InvalidArgumentException('Order must be an instance of ' . $class);
        }

        $this->order = $order;
        return $this;
    }

    /**
     * Get the current Invoice/Estimate items list
     *
     * @throws \SilverStripe\ORM\ValidationException
     *
     * @return \SilverStripe\ORM\SS_List
     */
    protected function getItems()
    {
        $order = $this->getOrder();
        $association = null;
        $associations = array_merge(
            $order->hasMany(),
            $order->manyMany()
        );

        // Find an applicable association
        foreach ($associations as $key => $value) {
            $class = $value::create();
            if (is_a($class, LineItemFactory::ITEM_CLASS)) {
                $association = $key;
                break;
            }
        }

        if (empty($association)) {
            throw new ValidationException(_t(
                __CLASS__ . ".NoItems",
                "The class '{class}' has no item association",
                ['class' => $order->ClassName]
            ));
        }
        
        return $order->{$association}();
    }

    /**
     * Get are we working with an invoice or an estimate?
     *
     * @return boolean
     */
    public function getIsInvoice()
    {
        return $this->is_invoice;
    }

    /**
     * Set are we working with an invoice or an estimate?
     *
     * @param bool $invoice Are we working with an invoice or an estimate?
     *
     * @return self
     */
    public function setIsInvoice(bool $invoice)
    {
        $this->is_invoice = $invoice;
        return $this;
    }

    /**
     * Get the reference number for the invoice (if null, a new invoice is created)
     *
     * @return int
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set the reference number for the invoice (if null, a new invoice is created)
     *
     * @param int $ref reference number
     *
     * @return self
     */
    public function setRef(int $ref)
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * Get the estimate/invoice ID (if null, a new estimate/invoice is created)
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the estimate/invoice ID (if null, a new estimate/invoice is created)
     *
     * @param int $id estimate/invoice ID
     *
     * @return self
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }
}
