<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Infrastructure\UuidV4Generator;
use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TaxRate;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Utils\_Time;

$tenantId = new TenantId('default');
$tenantContext = new TenantContext($tenantId);
adminCoreAssert($tenantContext->getTenantId()->equals($tenantId), 'TenantContext moet exact één tenant leveren.');
adminCoreAssertThrows(InvalidArgumentException::class, function (): void {
    new TenantId('');
}, 'Een lege tenant-id moet worden geweigerd.');

$eur = Currency::euro();
$amount = Money::fromDecimal('12485,00', $eur);
adminCoreAssert($amount->getMinorUnits() === 1248500, 'Decimaal bedrag moet exact naar minor units worden omgerekend.');
adminCoreAssert($amount->format() === '12.485,00', 'Bedrag moet zonder float correct worden geformatteerd.');

$lineTotal = Money::fromDecimal('49,95', $eur)->multiplyScaled(25000);
adminCoreAssert($lineTotal->equals(Money::fromDecimal('124,88', $eur)), 'Regeltotaal moet half-up op minor units afronden.');

$taxRate = TaxRate::fromPercentage('21');
$tax = $taxRate->calculateTax(Money::fromDecimal('0,03', $eur));
adminCoreAssert($tax->equals(Money::fromDecimal('0,01', $eur)), 'Btw moet per regel half-up worden afgerond.');

$negativeTax = $taxRate->calculateTax(Money::fromDecimal('-0,03', $eur));
adminCoreAssert($negativeTax->equals(Money::fromDecimal('-0,01', $eur)), 'Negatieve regels moeten symmetrisch half-up afronden.');

adminCoreAssertThrows(InvalidArgumentException::class, function () use ($amount): void {
    $amount->add(new Money(100, new Currency('USD', 2)));
}, 'Verschillende valuta mogen niet worden opgeteld.');

$uuid = (new UuidV4Generator())->generate();
adminCoreAssert(
    preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $uuid) === 1,
    'Publieke id moet een canonieke lowercase UUIDv4 zijn.'
);

$epoch = new _Time(0);
adminCoreAssert($epoch->getTime() === 0, '_Time moet timestamp 0 behouden.');

echo "AdminCore value object tests passed.\n";
