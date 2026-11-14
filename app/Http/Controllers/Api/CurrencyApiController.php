<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyApiController extends Controller
{
    public function __construct(protected CurrencyService $currencyService) {}

    /**
     * Get all explicit currency exchange rates.
     */
    public function rates(Request $request): JsonResponse
    {
        $base = $request->query('base', $request->user()?->currency ?? 'INR');
        $rates = $this->currencyService->getAllRates($base);

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Convert an amount between two currencies using explicit rates.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
        ]);

        $from = strtoupper($validated['from']);
        $to = strtoupper($validated['to']);
        $amount = (float) $validated['amount'];

        $rate = $this->currencyService->getExchangeRate($from, $to);
        $converted = $this->currencyService->convert($amount, $from, $to);

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $amount,
                'from_currency' => $from,
                'to_currency' => $to,
                'exchange_rate' => $rate,
                'converted_amount' => $converted,
            ],
        ]);
    }
}

