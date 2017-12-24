<?php
namespace phpEther\Web3\Providers;

use Graze\GuzzleHttp\JsonRpc\Client;
use phpEther\Web3\Providers\Geth;
class Infura extends Geth implements Provider 
{
    protected $client;
    protected $id = 0;
	const API_URL = "https://mainnet.infura.io/";
	const TESTNET_ROPSTEN = "ropsten";
	const TESTNET_MORDEN = "mordern";
    const TESTNET_KOVAN = "kovan";
    const TESTNET_RINKEBY = "rinkeby";
	const MAINNET = "mainnet";
   
	public function __construct(string $apiKeyToken , string $net ) {
        if (is_null($apiKeyToken)) {
            return;
        }
		$this->net = $net;
        $this->apiKeyToken = $apiKeyToken;
		parent::__construct(self::getAPIUrl(),$net);
        
    }
	
	public static function getAPIUrl() {
        if (is_null($this->net)) {
            return self::API_URL.$this->apiKeyToken;
        }
        return "https://{$this->net}.infura.io/".$this->apiKeyToken;
    }

	public function __call($method, $params){ // just in case
		return $this->request($method,$params);
	}
}