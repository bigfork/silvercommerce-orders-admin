<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

/**
 * GridField used for order items that allows a proper ReadOnly version
 */
class LineItemGridField extends GridField
{
    /**
     * Returns a readonly version of this field
     * @return GridField
     */
    public function performReadonlyTransformation()
    {
        $this->getConfig()
            ->removeComponentsByType("GridFieldDeleteAction")
            ->removeComponentsByType("GridFieldAddExistingAutocompleter")
            ->removeComponentsByType("GridFieldEditableColumns")
            ->removeComponentsByType("GridFieldAddNewButton")
            ->addComponent(new GridFieldDataColumns());
            
        return $this;
    }
}
