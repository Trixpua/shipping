# Shipping @Trixpua

[![Source Code](http://img.shields.io/badge/source-Trixpua/shipping-blue.svg?style=flat-square)](https://github.com/Trixpua/shipping)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/trixpua/shipping.svg?style=flat-square)](https://packagist.org/packages/Trixpua/shipping)
[![Latest Version](https://img.shields.io/github/release/trixpua/shipping.svg?style=flat-square)](https://github.com/Trixpua/shipping/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build](https://img.shields.io/scrutinizer/build/g/trixpua/shipping.svg?style=flat-square)](https://scrutinizer-ci.com/g/Trixpua/shipping)
[![Quality Score](https://img.shields.io/scrutinizer/g/trixpua/shipping.svg?style=flat-square)](https://scrutinizer-ci.com/g/Trixpua/shipping)
[![Total Downloads](https://img.shields.io/packagist/dt/trixpua/shipping.svg?style=flat-square)](https://packagist.org/packages/Trixpua/shipping)

###### Shipping is a set of classes optimized to get shipping quotes and tracking information from some Brazilian shipping companies.

Shipping é um conjunto de classes otimizadas para obter cotações de envio e informações de rastreamento de algumas empresas de transporte brasileiras.

### Shipping Companies (Empresas de transporte)

- TNT Mercúrio (Quote)
- Jamef Encomendas Urgentes (Quote)
- JadLog (Quote and Tracking)
- Correios (Quote)
- Tam Cargo (Quote) - Tam Cargo don't provide a official webservice to quote, so this class is unstable and not recommended to implementation (Tam Cargo não fornece um webservice oficial para cotação, portanto essa classe é instável e não é recomendada para implementação)

## Installation

Shipping is available via Composer:

```bash
"Trixpua/shipping": "^2.0"
```

or run

```bash
composer require Trixpua/shipping
```

## Documentation

###### For details on how to use the shipping, see a example folder in the component directory. In it you will have an example of use for each class. Shipping with minimum parameters works like this:

Para mais detalhes sobre como usar o shipping, veja a pasta de exemplo no diretório do componente. Nela terá um exemplo de uso para cada classe. Shipping com o mínimo de parâmetros funciona assim:

#### Quote with TNT

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Tnt\Quote\Tnt;
use Trixpua\Shipping\ShippingInfo;

$tnt = new Tnt('YOUR-ZIP-CODE', 'yourlogin@email.com', 'yourPassword', 'YOUR-DIVISION-CODE', 'YOUR-TAX-SITUATION', 'YOUR-TAX-ID', 'YOUR-STATE-REGISTRATION-NUMBER');

$shippingInfo = new ShippingInfo('DESTINY-ZIP-CODE', 'WEIGHT', 'COMMODITY-VALUE', 'VOLUME');
$tnt->setData($shippingInfo);

$tnt->makeRequest();

$return = $tnt->getResult();
```

#### Quote with Jamef

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Jamef\Quote\Jamef;
use Trixpua\Shipping\ShippingInfo;

$jamef = new Jamef('yourUser', 'YOUR-TAX-ID', 'YOUR STATE', 'YOUR CITY NAME', 'YOUR-QUOTE-BRANCH');

$shippingInfo = new ShippingInfo('DESTINY-ZIP-CODE', 'WEIGHT', 'COMMODITY-VALUE', 'VOLUME');
$jamef->setData($shippingInfo);

$jamef->makeRequest();

$return = $jamef->getResult();
```

#### Quote with Jadlog

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Jadlog\Quote\Jadlog;
use Trixpua\Shipping\ShippingInfo;

$jadlog = new Jadlog('YOUR-ZIP-CODE','yourPassword', 'YOUR-TAX-ID');

$shippingInfo = new ShippingInfo('DESTINY-ZIP-CODE', 'WEIGHT', 'COMMODITY-VALUE', 'VOLUME');
$jadlog->setData($shippingInfo);

$jadlog->makeRequest();

$return = $jadlog->getResult();
```

#### Quote with Correios

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Correios\Quote\Correios;
use Trixpua\Shipping\ShippingInfo;

$correios = new Correios('YOUR-ZIP-CODE', 'YOUR-LOGIN', 'yourPassword');

$shippingInfo = new ShippingInfo('DESTINY-ZIP-CODE', 'WEIGHT', 'COMMODITY-VALUE', 'VOLUME');
$correios->setData($shippingInfo);

$correios->makeRequest();

$return = $correios->getResult();
```

#### Quote with Tam Cargo

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\ShippingInfo;
use Trixpua\Shipping\TamCargo\Quote\TamCargo;

$tam = new TamCargo('YOUR-ZIP-CODE', 'yourlogin@email.com', 'yourPassword');

$shippingInfo = new ShippingInfo('DESTINY-ZIP-CODE', 'WEIGHT', 'COMMODITY-VALUE', 'VOLUME');
$tam->setData($shippingInfo);

$tam->makeRequest();

$return = $tam->getResult();
```

#### Track with TNT

```php
TODO
```

#### Track with Jamef

```php
TODO
```

#### Track with Jadlog

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Jadlog\Tracking\Jadlog;

$jadlog = new Jadlog('yourPassword', 'YOUR-TAX-ID');

$jadlog->setData('INVOICE-NUMBER');

$jadlog->makeRequest();

$return = $jadlog->getResult();
```

#### Track with Correios

```php
TODO
```

## Contributing

Please see [CONTRIBUTING](https://github.com/Trixpua/shipping/blob/master/CONTRIBUTING.md) for details.

## Support

###### Security: If you discover any security related issues, please email trix.pua@msn.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para trix.pua@msn.com ao invés de usar o rastreador de problemas.

Thank you

## License

The MIT License (MIT). Please see [License File](https://github.com/Trixpua/shipping/blob/master/LICENSE) for more information.