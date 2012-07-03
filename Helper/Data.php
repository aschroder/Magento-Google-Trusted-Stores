<?php

/**
 * Various Helper functions for the GoogleTrustedStore extension
 * 
 * @author Ashley Schroder (aschroder.com)
 */

class Aschroder_GoogleTrustedStore_Helper_Data extends Mage_Core_Helper_Abstract {
	
	private $DEFAULT_SHIP_DAYS = 3;
	
	public function isEnabled() {
		return Mage::getStoreConfig('google/googletrustedstores/enabled');
	}
	public function getKey() {
		return Mage::getStoreConfig('google/googletrustedstores/key');
	}
	public function getGoogleId() {
		return Mage::getStoreConfig('google/googletrustedstores/googleid');
	}
	public function getShipDays() {
		
		if (Mage::getStoreConfig('google/googletrustedstores/shipdays') == "" ||
			!is_int(Mage::getStoreConfig('google/googletrustedstores/shipdays'))) {
			
			return $this->DEFAULT_SHIP_DAYS;
		}
		
		return Mage::getStoreConfig('google/googletrustedstores/shipdays');
	}
}
