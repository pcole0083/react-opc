<?php
class Pixafy_Pixajaxlogin_Helper_Data extends Mage_Core_Helper_Abstract{
    public function getAjaxLoginPostURL(){
        return Mage::getUrl('pixajaxlogin/index/login', array('_forced_secure'=>true));
    }
}
