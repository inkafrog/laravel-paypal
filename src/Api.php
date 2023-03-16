<?php

namespace Ammadkhalid\paypal;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Payer;
use PayPal\Api\ItemList;
use PayPal\Api\Item;
use PayPal\Api\Amount;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
use Config;

class Api {

	private $api;
	private $paymentMethod;
	private $_paymentMethod;
	private $items;
	private $config;
	private $totalAmount = 0;
	private $amount;
	private $transDescription;
	private $redirectUrls;

	public function __construct()
	{
		$this->config = (object) Config::get('paypal');
		
        $this->api = new ApiContext(new OAuthTokenCredential(
            $this->config->client_id,
            $this->config->secret));

        $this->api->setConfig($this->config->settings);

        // set items
        $this->items = new ItemList;
	}

	public function getApi()
	{
		return $this->api;
	}

	/**
	 * [getTotalItems description]
	 * @return [type] [description]
	 */
	public function getItems(): ItemList
	{
		return $this->items;
	}

	/**
	 * 
	 */
	public function getPaymentMethod()
	{
		return $this->getPaymentMethod();
	}

	/**
	 * [getMethod description]
	 * @return [type] [description]
	 */
	public function getMethod(): String
	{
		return $this->_paymentMethod;
	}

	/**
	 * [getTotalAmount description]
	 * @return [type] [description]
	 */
	public function getTotalAmount()
	{
		return $this->totalAmount;
	}

	/**
	 * [setPaymentMethod description]
	 * @param String $method [description]
	 */
	public function setPaymentMethod(String $method = 'paypal')
	{
		$this->paymentMethod = (new Payer())->setPaymentMethod($method);
		$this->_paymentMethod = $method;

		return $this;
	}

	/**
	 * [addItem description]
	 * @param String $name     [description]
	 * @param Int    $quantity [description]
	 * @param [type] $amount   [description]
	 */
	public function addItem(String $name, Int $quantity, float $amount)
	{
		$item = (new Item())->setName($name)->setCurrency(
							$this->config->currency
						)
						->setQuantity($quantity)
			        ->setPrice($amount);

		// add total amount
		$this->totalAmount += $amount;

		// add tiem
		$this->items->addItem($item);

		return $this;
	}

	/**
	 * [setTransAmount description]
	 * @param array $amountInfo [description]
	 */
	public function setTransAmount(array $amountInfo = [])
	{
		if (!empty($amountInfo)) {
			$totalAmount = $amountInfo['total_amount'];
			$currency = $amountInfo['currency'];
		} else {
			$totalAmount = $this->totalAmount;
			$currency = $this->config->currency;
		}

		$this->amount = (new Amount)->setCurrency($currency)
            ->setTotal((float) $totalAmount);

        return $this;
	}

	/**
	 * [setTransDescription description]
	 * @param String $text [description]
	 */
	public function setTransDescription(String $text) {
		$this->transDescription = $text;

		return $this;
	}

	/**
	 * [setRedirectUrls description]
	 * @param [type] $returnUrl [description]
	 * @param [type] $cancelUrl [description]
	 */
	public function setRedirectUrls(String $returnUrl, String $cancelUrl)
	{
		$this->redirectUrls = (new RedirectUrls())
			->setReturnUrl($returnUrl)->setCancelUrl($cancelUrl);

		return $this;
	}

	/**
	 * [createTransacation description]
	 * @return [type] [description]
	 */
	public function createTransacation()
	{
		$transaction = new Transaction();
        $transaction->setAmount($this->totalAmount)
            ->setItemList($this->items)
            ->setDescription($this->transDescription);

        return $transaction;
	}


	public function createPayment($transDescription = null)
	{
		// set transacation description.
		!is_null($transDescription) ? $this->setTransDescription($transDescription) : '';

		$trans = $this->createTransacation();

		$payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($this->getPaymentMethod())
            ->setRedirectUrls($this->redirectUrls)
            ->setTransactions($trans);
        
        try{
        	return dd($payment->create($this->api));
        } catch(\PayPal\Exception\PayPalConnectionException $e) {
        	return dd(($e), $this);
        }
	}

}