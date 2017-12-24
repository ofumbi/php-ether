<?php
namespace phpEther;

use phpEther\Encoder\Keccak;
use phpEther\Encoder\RplEncoder;
use phpEther\Tools\Hex;
use phpEther\Tools\ECDSA;

class Account
{
	public $privateKey = NULL;
	public $publicKey = NULL;
	public $address = NULL;
	public $password = NULL;
	public function __construct($key =NULL , $net ='mainnet'){
		$this->net = strtolower($net);
		$this->determineChainId($this->net);
		if(is_null($key)){
			list($this->privateKey , $this->publicKey ) = ECDSA::get_new_key_pair();
			$this->address =  ECDSA::public_key_to_address($this->publicKey);
		}else{
			if(is_array($key)){
				list($this->address, $this->password) = $key;
			}else{
				$this->private_key = $key;
				$this->public_key = ECDSA::private_key_to_public_key($this->privateKey);
				$this->address =  ECDSA::public_key_to_address($this->publicKey);
			}
		}
		if(is_null($this->private_key)&&is_null($this->password))
		throw new Exception('Invalid Account. Both the PrivateKey and Password are Missing');
		
	}
	
	public function getPublicKey(){
		return $this->publicKey;
	}
	
	public function getPrivateKey(){
		return $this->privateKey;
	}
	
	public function getAddress(){
		return $this->address;
	}
	
	public function setChainId($chainId){
		$this->chainId = $chainId;
	}
	/*
	0: Olympic, Ethereum public pre-release testnet
	1: Frontier, Homestead, Metropolis, the Ethereum public main network
	1: Classic, the (un)forked public Ethereum Classic main network, chain ID 61
	1: Expanse, an alternative Ethereum implementation, chain ID 2
	2: Morden, the public Ethereum testnet, now Ethereum Classic testnet
	3: Ropsten, the public cross-client Ethereum testnet
	4: Rinkeby, the public Geth Ethereum testnet
	42: Kovan, the public Parity Ethereum testnet
	7762959: Musicoin, the music blockchain
	*/
	
	
}

