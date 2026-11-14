<?php

namespace App\Services;

use App\Models\CurrencyExchangeRate;

class CurrencyService
{
    /**
     * Default baseline explicit exchange rates to INR.
     */
    protected array $baseInrRates = [
        'INR' => 1.0,
        'USD' => 84.50,
        'EUR' => 91.20,
        'GBP' => 108.30,
        'AED' => 23.00,
        'CAD' => 62.10,
        'JPY' => 0.55,
        'AUD' => 54.80,
    ];

    /**
     * Get the explicit exchange rate from one currency to another.
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return 1.0;
        }

        // Check if database has an explicit direct rate
        $dbRate = CurrencyExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->value('rate');

        if ($dbRate) {
            return (float) $dbRate;
        }

        // Check reverse rate
        $dbReverse = CurrencyExchangeRate::where('from_currency', $to)
            ->where('to_currency', $from)
            ->value('rate');

        if ($dbReverse && (float) $dbReverse > 0) {
            return round(1.0 / (float) $dbReverse, 6);
        }

        // Cross-rate calculation via INR base
        $fromRateToInr = $this->baseInrRates[$from] ?? 1.0;
        $toRateToInr = $this->baseInrRates[$to] ?? 1.0;

        if ($toRateToInr == 0) {
            return 1.0;
        }

        return round($fromRateToInr / $toRateToInr, 6);
    }

    /**
     * Convert an amount between currencies.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->getExchangeRate($from, $to);
        return round($amount * $rate, 2);
    }

    /**
     * Get all available rates relative to a base currency.
     */
    public function getAllRates(string $baseCurrency = 'INR'): array
    {
        $baseCurrency = strtoupper(trim($baseCurrency));
        $rates = [];

        foreach (array_keys($this->baseInrRates) as $curr) {
            $rates[$curr] = $this->getExchangeRate($baseCurrency, $curr);
        }

        return [
            'base' => $baseCurrency,
            'rates' => $rates,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Store or update an explicit exchange rate.
     */
    public function updateRate(string $from, string $to, float $rate, string $source = 'user_defined'): CurrencyExchangeRate
    {
        return CurrencyExchangeRate::updateOrCreate(
            ['from_currency' => strtoupper($from), 'to_currency' => strtoupper($to)],
            ['rate' => $rate, 'source' => $source]
        );
    }
}

