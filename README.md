# php-ether (DONT USE!!! under Dev)
Ethereum Wallet Functions to generate Addresses and transactions without Geth

## Optional Requirement(Could be Faster?)

### secp256k1-php/
[secp256k1-php](https://github.com/Bit-Wasp/secp256k1-php). could speed up cryptographic calculations.
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

### Usage

```php

$tx->send()

```

Demo :
```bash
php examples/example.php
```