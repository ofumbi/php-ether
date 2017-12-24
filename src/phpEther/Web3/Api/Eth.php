<?php

namespace phpEther\Web3\Api;

use phpEther\Web3;
use phpEther\Web3\Providers\Provider;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use phpseclib\Math\BigInteger;
use phpEther\Web3\Api\Eth\Contract;

class Eth implements Api
{

    public $web3;

    public $provider;

    public $defaultAccount;

    public $defaultBlock = self::DEFAULT_BLOCK_LATEST;

    CONST DEFAULT_BLOCK_EARLIEST = "earliest";
    CONST DEFAULT_BLOCK_LATEST = "latest";
    CONST DEFAULT_BLOCK_PENDING = "pending";

    /**
     * Eth constructor.
     * @param Web3 $web3
     * @param Provider $provider
     */
    public function __construct(Web3 $web3, Provider $provider)
    {
        $this->web3 = $web3;
        $this->provider = $provider;
    }
	
    
    /**
     * @param $addressHexString
     * @param string $defaultBlock
     * @return BigInteger
     */
    public function getBalance($addressHexString, $defaultBlock = "latest") : BigInteger
    {
        $balance = $this->provider->eth_getBalance($addressHexString, $defaultBlock);

        return new BigInteger($balance, 16);
    }

    /**
     * @param array $object
     * @return string
     */
    public function sendTransaction(\phpEther\Transaction $tx) : string
    {
        return $this->provider->eth_sendTransaction($tx->);
    }
	
	/**
     * @param array $object
     * @return string
     */
    public function sendRawTransaction($hex) : string
    {
        return $this->provider->eth_sendRawTransaction($hex);
    }

    /**
     * @param array $abi
     * @return Contract
     */
    public function contract(array $abi)
    {
        return new Contract($this, $abi);
    }

    /**
     * @return BigInteger
     */
    public function blockNumber()
    {
        $blockNumber = $this->provider->eth_blockNumber();
        return new BigInteger($blockNumber, 16);
    }



	public function getTransactionCount($addressHexString) {
        return $this->provider->eth_getTransactionCount($addressHexString);
    }
	
	
	public function gasPrice() {
        return $this->provider->eth_gasPrice();
    }
	
	public function getTransactionByHash($hash) {
        return $this->provider->eth_getTransactionByHash($hash);
    }
	
	public function blockNumber() {
        return $this->provider->eth_blockNumber();
    }
	
	public function getLogs(\phpEther\Filter $filter) {
        return $this->provider->eth_getLogs($filter);
    }
	
	public function getBlockByNumber($no) {
        return $this->provider->eth_getBlockByNumber($no);
    }
    /**
     * @param array $object
     * @param null $defaultBlock
     * @return string
     */
    public function call(\phpEther\Message $object, $defaultBlock = null) : string
    {
        return $this->provider->eth_call($object, $defaultBlock);
		
    }
}