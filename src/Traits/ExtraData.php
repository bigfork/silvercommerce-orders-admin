<?php

namespace SilverCommerce\OrdersAdmin\Traits;

trait ExtraData
{
    /**
     * Array of optional extra data that can be passed to this factory.
     * (and will be passed into @link LineItemFactory).
     * 
     * The intention is to allow custom extensions to pass generic data
     * that can be accessed further down the stack
     * 
     * @var array
     */
    protected $extra_data = [];

    public function getExtraData(): array
    {
        return $this->extra_data;
    }

    public function setExtraData(array $data): self
    {
        $this->extra_data = $data;
        return $this;
    }
}