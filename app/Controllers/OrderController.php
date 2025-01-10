<?php
// src/Controllers/OrderController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaymentProcessor;
use App\Core\Validator;
use Exception;

class OrderController
{
    private $paymentProcessor;

    public function __construct()
    {
        $this->paymentProcessor = new PaymentProcessor();
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
                'currency' => ['required', 'string', 'in:INR,AUD,USD,GBP,CAD,EUR,SGD'],
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

            // Create order in CCAvenue
            $orderData = $this->paymentProcessor->createOrder(
                $input['amount'],
                $input['amount'],
                'INR',
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
            exit;

        } catch (Exception $e) {
            $this->paymentProcessor->logError('Payment Processing Error', $e->getMessage());

            jsonResponse([
                'error' => 'Unable to process payment request'
            ], 400);
            exit;
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
        $timestampStr = str_replace('.', '', (string)$timestamp);

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

    private function renderPaymentForm($orderData)
    {
        // Get the CCAvenue production URL from config
        $ccavenueUrl = $this->paymentProcessor->getUrl();

        $iframeUrl = $ccavenueUrl . '/transaction/transaction.do?command=initiateTransaction&encRequest=' .
            $orderData['encrypted_data'] . '&access_code=' . $orderData['access_code'];

        $iframeHtml = '<iframe src="' . htmlspecialchars($iframeUrl) . '" ' .
            'id="paymentFrame" width="482" height="450" frameborder="0" scrolling="No"></iframe>';

        return $iframeHtml;

        // Output the form
//         echo <<<HTML
//         <!DOCTYPE html>
//         <html>
//         <head>
//             <title>Processing Payment...</title>
//         </head>
//         <body>
//             <center>
//                 <form method="post" name="redirect" action="{$ccavenueUrl}">
//                     <input type="hidden" name="encRequest" value="{$orderData['encrypted_data']}">
//                     <input type="hidden" name="access_code" value="{$orderData['access_code']}">
//                     <script>document.redirect.submit();</script>
//                 </form>
//             </center>
//         </body>
//         </html>
// HTML;
//         exit;
    }
}
