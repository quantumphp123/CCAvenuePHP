<?php
// src/Controllers/OrderController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaymentProcessor;
use App\Core\Validator;
use App\Services\CurrencyService;
use Exception;

class OrderController
{
    private $paymentProcessor;
    private $currencyService;

    public function __construct()
    {
        $this->paymentProcessor = new PaymentProcessor();
        $this->currencyService = new CurrencyService();
    }

    public function create(): void
    {
        try {
            global $session;

            // Validate CSRF
            if (!$session->validateCSRFToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }

            $rules = [
                'amount' => ['required', 'numeric', 'min:1'],
                'currency' => ['required', 'string', 'in:' . $this->currencyService->getValidationRulesArray()],
                'name' => ['required', 'string', 'min:2'],
                'email' => ['required', 'email'],
                'tel' => ['string'],
                'address1' => ['required', 'string'],
                'address2' => ['string'],
                'country' => ['required', 'string'],
                'zip' => ['required', 'string'],
                'state' => ['required', 'string'],
                'city' => ['required', 'string']
            ];

            $validator = new Validator($_POST, $rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $firstError = reset($errors)[0];
                throw new Exception($firstError);
            }

            $input = $validator->sanitized();

            // Generate a unique order ID
            $orderId = $this->generateOrderId();

            $customerData = $this->transformInput($input);

            // Convert amount to INR if needed
            $amountInInr = $this->currencyService->convert(
                (float) $input['amount'],
                $input['currency']
            );

            if ($amountInInr === null) {
                throw new Exception('Currency conversion failed');
            }

            // Create order in CCAvenue
            $orderData = $this->paymentProcessor->createOrder(
                $amountInInr,
                $input['amount'],
                $this->currencyService->baseCurrency,
                $input['currency'],
                $orderId,
                $customerData
            );

            jsonResponse([
                'success' => true,
                'encrypted_data' => $orderData['encrypted_data'],
                'access_code' => $orderData['access_code'],
                'transaction_url' => $this->getTransactionUrl(),
            ]);
        } catch (Exception $e) {
            $this->paymentProcessor->logError('Payment Processing Error', $e->getMessage());
            info('Payment Processing Error: ' . $e->getMessage());
            jsonResponse([
                'error' => 'Unable to process payment request'
            ], 400);
        }
    }

    private function transformInput($input)
    {
        $address = '';
        if (!empty($input['address1'])) {
            $address .= $input['address1'];
        }
        if (!empty($input['address2'])) {
            if (!empty($address)) {
                $address .= ' '; // Add a space if address1 already exists
            }
            $address .= $input['address2'];
        }

        // Create a new $data array with the required keys
        $customerData = [
            'billing_name' => $input['name'],
            'billing_email' => $input['email'],
            'billing_tel' => $input['tel'],
            'billing_address' => $address,
            'billing_state' => $input['state'],
            'billing_country' => $input['country'],
            'billing_zip' => $input['zip'],
            'billing_city' => $input['city'],
        ];

        return $customerData;
    }

    private function generateOrderId()
    {
        // Get current timestamp in microseconds
        $timestamp = microtime(true);
        $timestampStr = str_replace('.', '', (string) $timestamp);

        // Add a random component
        $random = mt_rand(100, 999);

        // Combine timestamp and random number
        $orderId = $timestampStr . $random;

        // Ensure it's not longer than 30 characters (CCAvenue limit)
        return substr($orderId, 0, 30);
    }

    public function getTransactionUrl()
    {
        $ccavenueUrl = $this->paymentProcessor->getUrl();
        return $ccavenueUrl . '/transaction/transaction.do?command=initiateTransaction';
    }
}
