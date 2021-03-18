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

- TNT Mercúrio (Quote and Tracking - last occurrence)
- Jamef Encomendas Urgentes (Quote and Tracking)
- Correios (Quote and Tracking)
- Tam Cargo (Quote) - Tam Cargo don't provide a official webservice to quote, so this class is unstable and not recommended to production implementation (Tam Cargo não fornece um webservice oficial para cotação, portanto essa classe é instável e não é recomendada para implementação em produção)
- Expresso São Miguel (Quote) - Expresso São Miguel don't provide a official webservice to quote, so this class is unstable and not recommended to production implementation (Expresso São Miguel não fornece um webservice oficial para cotação, portanto essa classe é instável e não é recomendada para implementação em produção)

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

#### Quote with Expresso São Miguel

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\ExpressoSaoMiguel\Quote\ExpressoSaoMiguel;
use Trixpua\Shipping\ShippingInfo;

$expressoSaoMiguel = new ExpressoSaoMiguel('YOUR-ZIP-CODE','yourUser', 'yourPassword');

$shippingInfo = new ShippingInfo('DESTINY-ZIP-CODE', 'WEIGHT', 'COMMODITY-VALUE', 'VOLUME');
$expressoSaoMiguel->setData($shippingInfo);

$expressoSaoMiguel->makeRequest();

$return = $expressoSaoMiguel->getResult();
```

#### Track with TNT

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Tnt\Tracking\Tnt;

$tnt = new Tnt('yourlogin@email.com', 'YOUR-TAX-ID');

$tnt->setData('INVOICE-NUMBER');

$tnt->makeRequest();

$return = $tnt->getResult();
```

#### Track with Jamef

```php
<?php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Jamef\Tracking\Jamef;

$jamef = new Jamef('yourUsername', 'yourPassword', '00.000.000.0000-00');

$jamef->setData('INVOICE-NUMBER');

$jamef->makeRequest();

$return = $jamef->getResult();
```

#### Track with Correios

```php
require __DIR__ . "/vendor/autoload.php";

use Trixpua\Shipping\Correios\Tracking\Correios;

$correios = new Correios();

$correios->setData(['TRACKING-NUMBERS']);

$correios->makeRequest();

$return = $correios->getResult();
```

## Contributing

Please see [CONTRIBUTING](https://github.com/Trixpua/shipping/blob/master/CONTRIBUTING.md) for details.

## Support

###### Security: If you discover any security related issues, please open an issue.

Se você descobrir algum problema relacionado à segurança, por favor abra uma issue.

Thank you

## License

The MIT License (MIT). Please see [License File](https://github.com/Trixpua/shipping/blob/master/LICENSE) for more information.