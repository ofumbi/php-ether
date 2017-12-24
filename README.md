# php-ether
Ethereum Wallet Functions to generate Addresses and transactions without Geth

## Pre-requisite

### secp256k1-php

You need [secp256k1-php](https://github.com/Bit-Wasp/secp256k1-php).
secp256k1-php needs [secp256k1](https://github.com/bitcoin-core/secp256k1) to be installed on your system.

*You will need `gcc`, `libtool`,  `make`, `automake` , which is standard package you can grab from apt, yum, brew...*

git clone https://github.com/afk11/secp256k1-php.git
git clone https://github.com/bitcoin-core/secp256k1.git
cd secp256k1
./autogen.sh && ./configure --enable-benchmark=no --enable-experimental --enable-module-{ecdh,recovery} && make && sudo make install
cd ../secp256k1-php/secp256k1
sudo phpize && ./configure --with-secp256k1 && make && sudo make install

Finally add extension to you *php.ini* file

```ini
extension=secp256k1.so
```

## Examples

You may run examples in `examples` folder.

### Creating a raw transaction

```php
$tx = new \EthereumRawTx\Transaction(
    'd44d259015b61a5fe5027221239d840d92583adb',
    5 * 10**18,
);

$raw = $tx->getRaw(MY_PRIVATE_KEY);
```

Demo :
```bash
php examples/simple.php
```