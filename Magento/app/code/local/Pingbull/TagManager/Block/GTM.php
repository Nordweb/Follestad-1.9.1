<?php
class  Pingbull_TagManager_Block_GTM extends Mage_Core_Block_Text
{
    protected function _toHtml()
    {
        $script = Mage::getStoreConfig(Pingbull_TagManager_Helper_Data::XML_PATH_TAG_SCRIPT);

        return $script;
    }
}
