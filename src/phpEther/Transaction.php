<?php
namespace phpEther;

use phpEther\Encoder\Keccak;
use phpEther\Encoder\RplEncoder;
use phpEther\Tools\Hex;

class Transaction
{
    protected $chainId;
    protected $nonce;
    protected $gasPrice;
    protected $gasLimit;
    protected $to;
	protected $from;
    protected $value;
    protected $data;
	protected $raw;
	protected $web3;

    protected $v;
    protected $r;
    protected $s;
    public function __construct(\phpEther\Account $from = null, string $to = null, int $value = null, string $data = null, int $nonce = 1, int $gasPrice = 10000000000000, int $gasLimit = 196608 , $web3=NULL )
    {
		$this->from = $from??'';
        $this->nonce = Hex::fromDec($nonce);
        $this->gasPrice = Hex::fromDec($gasPrice);
        $this->gasLimit = Hex::fromDec($gasLimit);
        $this->to = $to ?? '';
        $this->value = null === $value ? '' : Hex::fromDec($value);
        $this->data = $data ?? '';
		$this->web3 = $web3;
		$this->raw =  bin2hex($this->serialize());
		$this->chainId = Hex::fromDec(1); // default mainnet;
    }
	
	/**
     * @param Decimal $nonce
     * @return \phpEther\Transaction 
     */
	public function setNonce($nonce = NULL){
		if(!is_null($nonce)){
			$this->nonce =  Hex::fromDec($nonce);
			return $this;
		}
		if(empty($this->from))
		throw new Exception('Tx "from" field is required to Determine the Nonce ');
		if(is_null($this->web3))
		throw new Exception('Please set a Web3 provider');
		$this->nonce = $this->web3->eth->getTransactionCount($this->from->address);
		return $this;
	}
	/**
     * @param Decimal $gasPrice
     * @return \phpEther\Transaction 
     */
	public function setGasPrice($gasPrice = NULL){
		if(!is_null($gasPrice)){
			$this->gasPrice = Hex::fromDec($gasPrice);
			return $this;
		}
		if(is_null($this->web3))
		throw new Exception('Please set a Web3 provider');
		$this->gasPrice = $this->web3->eth->gasPrice();
		return $this;
	}
	/**
     * @param Decimal $gasLimit
     * @return \phpEther\Transaction 
     */
	public function setGasLimit($gasLimit= NULL){
		if(!is_null($gasLimit)){
			$this->gasLimit = Hex::fromDec($gasLimit);
			return $this;
		}
		if(is_null($this->web3))
		throw new Exception('Please set a Web3 provider');
		$this->gasLimit = $this->web3->eth->estimateGas($this);
		return $this;
	}


	public function prefill(){
		return $this->setNonce()->setGasPrice()->setGasLimit();
	}
	
	public function send(){  
		if(is_null($this->from))
		throw new Exception('Cannot Send Transaction. Specify the From account');
		if(is_null($this->web3))
		throw new Exception('Please set a Web3 provider');
		if(is_null($this->web3))
		if(!is_null($this->from->password))
		return $this->web3->personal->sendTransaction($this, $this->from->password);
		if(!is_null($this->from->privateKey))
			$RawTx = $this->sign()->getRaw();
		return $this->web3->eth->sendRawTransaction($RawTx);
		throw new Exception('Cannot Send from Account. Both the PrivateKey and Password are Missing');
		
	}
	
	
	 /** use erc20 token contract
     * @param string $web3 \phpEther\Web3
     * @return \phpEther\Transaction 
     */
	public function setToken($token){
		$this->token = $token;
		$this->web3 = $token->eth->web3;
		$this->chainId = $token->eth->provider->get_chainId();
		return $this;
	}
	
	 /**
     * @param string $web3 \phpEther\Web3
     * @return \phpEther\Transaction 
     */
	
	public function setWeb3(\phpEther\Web3 $web3){
		$this->web3 = $web3;
		$this->chainId = $web3->eth->provider->get_chainId();
		return $this;
	}

    /**
     * return \phpEther\Transaction
     */
    
	
	public function getRaw()
    {
       return $this->raw;
    }
	
	
	function toArray()
	{
		return [ $this->getArray()];
	}
	
	public function getArray(){
		return[	
				'from'=>$this->from->address,
				'to'=>$this->to,
				'gas'=>$this->gasLimit,
				'gasPrice'=>$this->gasPrice,
				'value'=>$this->value,
				'data'=>$this->data,
				'nonce'=>$this->nonce
			];
	}
	
    protected function getInput()
    {
        return [
            "nonce" => $this->nonce,
            "gasPrice" => $this->gasPrice,
            "gasLimit" => $this->gasLimit,
            "to" => $this->to,
            "value" => $this->value,
            "data" => $this->data,
            "v" => $this->v,
            "r" => $this->r,
            "s" => $this->s,
        ];
    }

    protected function sign()
    {
		$pk = $this->from->privateKey;
		$this->v = null;
        $this->r = null;
        $this->s = null;
        $hash = $this->hash();
        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
        $msg32 = hex2bin($hash);
        $privateKey = pack("H*", $pk);
        if (!$privateKey) {
            throw new \Exception("Incorrect private key");
        }
        /** @var resource $signature */
        $signature = '';
        if (secp256k1_ecdsa_sign_recoverable($context, $signature, $msg32, $privateKey) != 1) {
            throw new \Exception("Failed to create signature");
        }
        $serialized = '';
        $recId = 0;
        secp256k1_ecdsa_recoverable_signature_serialize_compact($context, $signature, $serialized, $recId);
        $hexsign = bin2hex($serialized);
        $this->r = Hex::trim(substr($hexsign, 0, 64));
        $this->s = Hex::trim(substr($hexsign, 64));
        $this->v = Hex::fromDec($recId + 27 + hexdec($this->chainId) * 2 + 8);
		$this->raw =  bin2hex($this->serialize());
        return $this;
    }
	
	
	public function _sign(){
		$pk = $this->from->privateKey;
		$this->v = null;
        $this->r = null;
        $this->s = null;
        $hash = $this->hash();
		$ecAdapter = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();	
		$privateKey = BitWasp\Bitcoin\Key\PrivateKeyFactory::fromHex($pk);
        $sig = $ecAdapter->signCompact(
			$hash,
			$privateKey,
			new \BitWasp\Bitcoin\Crypto\Random\Rfc6979(
				$ecAdapter,
				$privateKey,
				$hash,
				'sha256'
			)
         );	 
        $this->r = Hex::trim(gmp_strval($sig->getR(),16));
        $this->s = Hex::trim(gmp_strval($sig->getS(),16));
        $this->v = Hex::fromDec($sig->getRecoveryId() + 27 + hexdec($this->chainId) * 2 + 8);
		$this->raw =  bin2hex($this->serialize());
        return $this;  
    }


    protected function hash()
    {
        $raw = $this->getInput();

        if (hexdec($this->chainId) > 0) {
            $raw['v'] = $this->chainId;
            $raw['r'] = "";
            $raw['s'] = "";
        } else {
            unset($raw['v']);
            unset($raw['r']);
            unset($raw['s']);
        }

        $raw = array_map('hex2bin', $raw);

        // create hash
        $hash = RplEncoder::encode($raw);
        $shaed = Keccak::hash($hash);
        return $shaed;
    }

    protected function serialize()
    {
        $raw = $this->getInput();
        $raw = array_map('hex2bin', $raw);
        return RplEncoder::encode($raw);
    }

}

