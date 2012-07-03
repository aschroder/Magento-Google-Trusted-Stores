<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ashley.schroder@gmail.com so we can send you a copy immediately.
 *
 * @category   Aschroder
 * @package    Aschroder_GoogleTrustedStore
 * @copyright  Copyright (c) 2009 Aschroder.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Aschroder_GoogleTrustedStore_FeedController extends Mage_Core_Controller_Front_Action {

	private $NUM_DAYS = 2; // but you can change it if you want
	
	// The Google docs requirethe word 'OTHER' but use 'Other' in an example.
	// See http://support.google.com/trustedstoresmerchant/bin/answer.py?hl=en&answer=2609890
	private $OTHER = "OTHER";
	
	private $CANCELLED = "MerchantCanceled";
	
	private $SHIPMENT_HEADER = array(
		"merchant order id",
	 	"tracking number", 
	 	"carrier code",
	 	"other carrier name",
	 	"ship date"); 
	
	private $CANCELLATION_HEADER = array(
		"merchant order id",
	 	"reason"); 
	
	private $PRIMARY_CARRIERS = array(
		"UPS", 
		"FedEx", 
		"USPS"); 
	
	private $OTHER_CARRIERS = array(
		"ABFS"=>"ABF Freight Systems",
		"AMWST"=>"America West",
		"BEKINS"=>"Bekins",
		"CNWY"=>"Conway",
		"DHL"=>"DHL",
		"ESTES"=>"Estes",
		"HDUSA"=>"Home Direct USA",
		"LASERSHIP"=>"LaserShip",
		"MYFLWR"=>"Mayflower",
		"ODFL"=>"Old Dominion Freight",
		"RDAWAY"=>"Reddaway",
		"TWW"=>"Team Worldwide",
		"WATKINS"=>"Watkins",
		"YELL"=>"Yellow Freight",
		"YRC"=>"YRC",
		"OTHER"=>"All Other Carriers"); 

	public function shipmentsAction() {
		
		if (!$this->_requestOK()) {
			return;
		}
		
		//Get a list of the recent shipments
		
		$startDate = new Zend_Date();
		$startDate->sub($this->NUM_DAYS, Zend_Date::DAY);
		
		$collection = Mage::getModel('sales/order_shipment')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToSort('updated_at', 'desc')
			->addAttributeToFilter('updated_at', array('from' => $startDate, 'date' => true));
		
		$collection->load();
		
		$output = implode($this->SHIPMENT_HEADER, "\t")."\n";
		
		foreach ($collection->getItems() as $shipment) {
			
			// Load order and track info
			$shipment = Mage::getModel('sales/order_shipment')->load($shipment->getId());
			$tracks = $shipment->getAllTracks();
			
			foreach($tracks as $track) {
				
				$code = $this->_getPrimaryCode($track->getCarrierCode());
				
				if (!$code) {
					$code = "Other";
					$other = $this->_otherCode($track->getCarrierCode());
				} else {
					$other = "";
				}
				
				$info = array(
					$shipment->getOrder()->getIncrementId(),
					$track->getNumber(),
					$code,
					$other,
					date("Y-m-d", strtotime($track->getCreatedAt()))
				);
				
				$output .= implode($info, "\t")."\n";
				
			}
		}
		
		$this->getResponse()->setBody($output);
	}
	
	public function cancellationsAction() {
		
		if (!$this->_requestOK()) {
			return;
		}
		
		//Get a list of the recent shipments
		
		$startDate = new Zend_Date();
		$startDate->sub($this->NUM_DAYS, Zend_Date::DAY);
		
		
        $orders = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
        	->addAttributeToFilter('state', array('eq' => Mage_Sales_Model_Order::STATE_CANCELED))
        	->addAttributeToFilter('updated_at', array('from' => $startDate, 'date' => true))
            ->setOrder('updated_at', 'desc');
		
		$orders->load();
		
		$output = implode($this->CANCELLATION_HEADER, "\t")."\n";
		
		foreach($orders as $order) {
		
			$info = array(
				$order->getIncrementId(),
				// TODO: devise a clever way to find out why it was cancelled.
				$this->CANCELLED // in the abscence of anything better for v0.1
			);
		
			$output .= implode($info, "\t")."\n";
		}
		
		$this->getResponse()->setBody($output);
	}
	
	private function _getPrimaryCode($code) {

		foreach ($this->PRIMARY_CARRIERS as $primaryCode) {
			if (strtolower($code) == strtolower($primaryCode)) {
				return $primaryCode;
			}
		}
		
		// not a primary code
		return false;
	}
	
	private function _otherCode($code) {
		
		foreach ($this->OTHER_CARRIERS as $otherCode=>$otherName) {
			if (strtolower($code) == strtolower($otherCode) ||
					strtolower($code) == strtolower($otherName)) {
				return $primaryCode;
			}
		}
		
		return $this->OTHER;
		
	}
	
	private function _requestOK() {
		
		if (!Mage::helper('googletrustedstores')->isEnabled()) {
				Mage::log("Google Trusted Stores not enabled");
			return false;
		}
		
		if (Mage::helper('googletrustedstores')->getKey() &&
			Mage::helper('googletrustedstores')->getKey() != $this->getRequest()->getParam('key')) {
				
			Mage::log("Key is set but does not match what was given: " . 
				$this->getRequest()->getParam('key') . " vs " . 
				Mage::helper('googletrustedstores')->getKey());
			return false;
		}
		
		return true;
	}
	
}
