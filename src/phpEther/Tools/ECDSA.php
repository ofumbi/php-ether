<?php
/**
 *
 * @author Ofumbi Stephen
 */
namespace phpEther\Tools;
if (!extension_loaded('gmp')) {
    throw new \Exception('GMP extension seems not to be installed');
}
class ECDSA
{
	
	 /**
     * Get New Private Key
     *
     * This function generates a new private key, a number from 1 to $n.
     * Once it finds an acceptable value, it will encode it in hex, pad it,
     * and return the private key.
     *
     * @return    string
     */
    public static function get_new_private_key()
    {
        $math = EccFactory::getAdapter();
        $g = EccFactory::getSecgCurves($math)->generator256k1();
        $privKey = gmp_strval(gmp_init(bin2hex(self::get_random()), 16));
        while ($math->cmp($privKey, $g->getOrder()) !== -1) {
            $privKey = gmp_strval(gmp_init(bin2hex(self::get_random()), 16));
        }
        $privKeyHex = $math->dechex($privKey);
        return str_pad($privKeyHex, 64, '0', STR_PAD_LEFT);
    }
	
	/**
     * Private Key To Public Key
     *
     * Accepts a $privKey as input, and does EC multiplication to obtain
     * a new point along the curve. The X and Y coordinates are the public
     * key, which are returned as a hexadecimal string in uncompressed
     * format.
     *
     * @param    string  $privKey
     * @param    boolean $compressed
     * @return    string
     */
    public static function private_key_to_public_key($privKey, $compressed = false)
    {
        $math = EccFactory::getAdapter();
        $g = EccFactory::getSecgCurves($math)->generator256k1();
        $privKey = self::hex_decode($privKey);
        $secretG = $g->mul($privKey);
        $xHex = self::hex_encode($secretG->getX());
        $yHex = self::hex_encode($secretG->getY());
        $xHex = str_pad($xHex, 64, '0', STR_PAD_LEFT);
        $yHex = str_pad($yHex, 64, '0', STR_PAD_LEFT);
        $public_key = '04' . $xHex . $yHex;
        return ($compressed == true) ? self::compress_public_key($public_key) : $public_key;
    }
	
	
	    /**
     * Hex Encode
     *
     * Encodes a decimal $number into a hexadecimal string.
     *
     * @param    int $number
     * @return    string
     */
    public static function hex_encode($number)
    {
        $hex = gmp_strval(gmp_init($number, 10), 16);
        return (strlen($hex) % 2 != 0) ? '0' . $hex : $hex;
    }
    /**
     * Hex Decode
     *
     * Decodes a hexadecimal $hex string into a decimal number.
     *
     * @param    string $hex
     * @return    int
     */
    public static function hex_decode($hex)
    {
        return gmp_strval(gmp_init($hex, 16), 10);
    }
	
	 /**
     * Generate a 32 byte string of random data.
     *
     * This function can be overridden if you have a more sophisticated
     * random number generator, such as a hardware based random number
     * generator, or a system capable of delivering lot's of entropy for
     * MCRYPT_DEV_RANDOM. Do not override this if you do not know what
     * you are doing!
     *
     * @return string
     */
    protected static function get_random()
    {
        return mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM);
    }

	/**
     * Private Key To Address
     *
     * Converts a $privKey to the corresponding public key, and then
     * converts to the bitcoin address, using the $address_version.
     *
     * @param $private_key
     * @param $address_version
     * @return string
     */
    public static function private_key_to_address($private_key, $address_version = null)
    {
        $public_key = self::private_key_to_public_key($private_key);
        return self::public_key_to_address($public_key);
    }
	
	
	
	 /**
     * Public Key To Address
     *
     * This function accepts an uncompressed $publickey  and
     * returns an etheruem address 
     *
     * @param    string $public_key
     * @return    string
     */
    public static function public_key_to_address($publickey)
    {
        $pubk =  mb_substr($publickey, -128, 128, 'utf-8'); //remove 04
		$address = \phpEther\Encoder\Keccak::hash($pubk,256);
		$addres1 = '0x'.mb_substr($address, -40, 40, 'utf-8');
    }
	
	
	 /**
     * Get New Key Pair
     *
     * Generate a new private key, and convert to an uncompressed public key.
     *
     * @return array
     */
    public static function get_new_key_pair()
    {
        $private_key = self::get_new_private_key();
        $public_key = self::private_key_to_public_key($private_key);
        return array('privKey' => $private_key,
            'pubKey' => $public_key);
    }
	
	
	 /**
     * Import Public Key
     *
     * Imports an arbitrary $public_key, and returns it untreated if the
     * left-most bit is '04', or else decompressed the public key if the
     * left-most bit is '02' or '03'.
     *
     * @param    string $public_key
     * @return    string
     */
    public static function import_public_key($public_key)
    {
        $first = substr($public_key, 0, 2);
        if (($first == '02' || $first == '03') && strlen($public_key) == '66') {
            // Compressed public key, need to decompress.
            $decompressed = self::decompress_public_key($public_key);
            return ($decompressed == false) ? false : $decompressed['public_key'];
        } else if ($first == '04') {
            // Regular public key, pass back untreated.
            return $public_key;
        }
        throw new \InvalidArgumentException("Invalid public key");
    }
    /**
     * Compress Public Key
     *
     * Converts an uncompressed public key to the shorter format. These
     * compressed public key's have a prefix of 02 or 03, indicating whether
     * Y is odd or even (tested by gmp_mod2(). With this information, and
     * the X coordinate, it is possible to regenerate the uncompressed key
     * at a later stage.
     *
     * @param    string $public_key
     * @return    string
     */
    public static function compress_public_key($public_key)
    {
        $math = EccFactory::getAdapter();
        $x_hex = substr($public_key, 2, 64);
        $y = $math->hexDec(substr($public_key, 66, 64));
        $parity = $math->mod($y, 2);
        return (($parity == 0) ? '02' : '03') . $x_hex;
    }
    /**
     * Decompress Public Key
     *
     * Accepts a y_byte, 02 or 03 indicating whether the Y coordinate is
     * odd or even, and $passpoint, which is simply a hexadecimal X coordinate.
     * Using this data, it is possible to deconstruct the original
     * uncompressed public key.
     *
     * @param $key
     * @return array|bool
     */
    public static function decompress_public_key($key)
    {
        $math = EccFactory::getAdapter();
        $y_byte = substr($key, 0, 2);
        $x_coordinate = substr($key, 2);
        $x = self::hex_decode($x_coordinate);
        $theory = EccFactory::getNumberTheory($math);
        $generator = EccFactory::getSecgCurves($math)->generator256k1();
        $curve = $generator->getCurve();
        try {
            $x3 = $math->powmod($x, 3, $curve->getPrime());
            $y2 = $math->add($x3, $curve->getB());
            $y0 = $theory->squareRootModP($y2, $curve->getPrime());
            if ($y0 == null) {
                throw new \InvalidArgumentException("Invalid public key");
            }
            $y1 = $math->sub($curve->getPrime(), $y0);
            $y = ($y_byte == '02')
                ? (($math->mod($y0, 2) == '0') ? $y0 : $y1)
                : (($math->mod($y0, 2) !== '0') ? $y0 : $y1);
            $y_coordinate = str_pad($math->decHex($y), 64, '0', STR_PAD_LEFT);
            $point = $curve->getPoint($x, $y);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid public key");
        }
        return array(
            'x' => $x_coordinate,
            'y' => $y_coordinate,
            'point' => $point,
            'public_key' => '04' . $x_coordinate . $y_coordinate
        );
    }
    /**
     * Validate Public Key
     *
     * Validates a public key by attempting to create a point on the
     * secp256k1 curve.
     *
     * @param    string $public_key
     * @return    boolean
     */
    public static function validate_public_key($public_key)
    {
        if (strlen($public_key) == '66') {
            // Compressed key
            // Attempt to decompress the public key. If the point is not
            // generated, or the function fails, then the key is invalid.
            $decompressed = self::decompress_public_key($public_key);
            return $decompressed == true;
        } else if (strlen($public_key) == '130') {
            $math = EccFactory::getAdapter();
            $generator = EccFactory::getSecgCurves($math)->generator256k1();
            // Uncompressed key, try to create the point
            $x = $math->hexDec(substr($public_key, 2, 64));
            $y = $math->hexDec(substr($public_key, 66, 64));
            // Attempt to create the point. Point returns false in the
            // constructor if anything is invalid.
            try {
                $generator->getCurve()->getPoint($x, $y);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
	
	
	    /**
     * helper function to ensure a hex has all it's preceding 0's (which PHP tends to trim off)
     *
     * @param      $hex
     * @param null $length
     * @return string
     */
    public static function padHex($hex, $length = null)
    {
        if (!$length) {
            $length = strlen($hex);
            if ($length % 2 !== 0) {
                $length += 1;
            }
        }
        return str_pad($hex, $length, "0", STR_PAD_LEFT);
    }
	
}