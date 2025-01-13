<?php

namespace App\Services;

class CurrencyService
{
    private array $exchangeRates;
    public string $baseCurrency = 'INR';

    public function __construct()
    {
        // Load exchange rates from JSON file
        $jsonPath =  url('/json/currency.json');

        $rates = json_decode(file_get_contents($jsonPath), true);

        // Add INR as base currency with rate 1
        $this->exchangeRates = array_merge(['INR' => 1], $rates);
    }

    /**
     * Get all available currencies
     *
     * @return array
     */
    public function getAvailableCurrencies(): array
    {
        return array_keys($this->exchangeRates);
    }

    /**
     * Get exchange rates for all currencies
     *
     * @return array
     */
    public function getAllExchangeRates(): array
    {
        return $this->exchangeRates;
    }

    /**
     * Get exchange rate for a specific currency
     *
     * @param string $currency
     * @return float|null
     */
    public function getExchangeRate(string $currency): ?float
    {
        return $this->exchangeRates[strtoupper($currency)] ?? null;
    }

    /**
     * Convert amount from one currency to another
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float|null
     */
    public function convert(float $amount, string $toCurrency): ?float
    {
        $fromCurrency = strtoupper($toCurrency);

        if (!isset($this->exchangeRates[$toCurrency])) {
            return null;
        }

        // If converting to same currency, return original amount
        if ($toCurrency === $this->baseCurrency) {
            return $amount;
        }

        // Convert to INR
        return $amount * $this->exchangeRates[$toCurrency];
    }

    /**
     * Validate if a currency is supported
     *
     * @param string $currency
     * @return bool
     */
    public function isValidCurrency(string $currency): bool
    {
        return isset($this->exchangeRates[strtoupper($currency)]);
    }

    /**
     * Get validation rules for currency field
     *
     * @return string
     */
    public function getValidationRulesArray(): string
    {
        return implode(',', $this->getAvailableCurrencies());
    }
}
