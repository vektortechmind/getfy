<?php

namespace Tests\Unit;

use App\Support\CheckoutCurrencyCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutCurrencyCatalogTest extends TestCase
{
    public function test_currency_for_country_mapping(): void
    {
        $this->assertSame('BRL', CheckoutCurrencyCatalog::currencyForCountry('BR'));
        $this->assertSame('EUR', CheckoutCurrencyCatalog::currencyForCountry('DE'));
        $this->assertSame('MXN', CheckoutCurrencyCatalog::currencyForCountry('MX'));
        $this->assertSame('GBP', CheckoutCurrencyCatalog::currencyForCountry('GB'));
    }

    public function test_unknown_country_falls_back_to_usd(): void
    {
        $this->assertSame('USD', CheckoutCurrencyCatalog::currencyForCountry('ZZ'));
    }

    public function test_brl_from_foreign_amount_uses_rate_to_brl(): void
    {
        $tenant = [
            ['code' => 'BRL', 'rate_to_brl' => 1],
            ['code' => 'USD', 'rate_to_brl' => 0.2],
            ['code' => 'EUR', 'rate_to_brl' => 0.16],
        ];

        $this->assertSame(50.0, CheckoutCurrencyCatalog::brlFromForeignAmount(10.0, 'USD', $tenant));
        $this->assertSame(62.5, CheckoutCurrencyCatalog::brlFromForeignAmount(10.0, 'EUR', $tenant));
        $this->assertSame(10.0, CheckoutCurrencyCatalog::brlFromForeignAmount(10.0, 'BRL', $tenant));
    }

    public function test_foreign_from_brl_amount_uses_rate_to_brl(): void
    {
        $tenant = [
            ['code' => 'BRL', 'rate_to_brl' => 1],
            ['code' => 'USD', 'rate_to_brl' => 0.2],
        ];

        $this->assertSame(20.0, CheckoutCurrencyCatalog::foreignFromBrlAmount(100.0, 'USD', $tenant));
    }

    #[DataProvider('featuredOrderProvider')]
    public function test_merge_tenant_currencies_puts_featured_first(string $firstCode): void
    {
        $merged = CheckoutCurrencyCatalog::mergeTenantCurrencies([
            ['code' => 'MXN', 'symbol' => '$', 'label' => 'Peso', 'rate_to_brl' => 3.5],
            ['code' => 'BRL', 'symbol' => 'R$', 'label' => 'Real', 'rate_to_brl' => 1],
            ['code' => 'USD', 'symbol' => '$', 'label' => 'Dólar', 'rate_to_brl' => 0.18],
        ]);

        $this->assertSame($firstCode, $merged[0]['code']);
    }

    public static function featuredOrderProvider(): array
    {
        return [['BRL']];
    }

    public function test_is_supported_for_catalog_codes(): void
    {
        $this->assertTrue(CheckoutCurrencyCatalog::isSupported('MXN'));
        $this->assertFalse(CheckoutCurrencyCatalog::isSupported('HRK'));
    }
}
