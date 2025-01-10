<?php

namespace App\Controllers;

use App\Core\Redirect;
use App\Core\Validator;
use App\Services\PaymentProcessor;
use Exception;

class PaymentController extends Controller
{
    private $paymentProcessor;

    public function __construct()
    {
        $this->paymentProcessor = new PaymentProcessor();
    }

    public function processPayment()
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
                'address1' => ['required', 'string'],
                'country' => ['required', 'string'],
                'zipcode' => ['required', 'string'],
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
                'iframe' => $this->renderPaymentForm($orderData)
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

    public function handleResponse()
    {
        try {
            // Get encrypted response from CCAvenue
            $encResponse = $_POST['encResp'];
            if (!$encResponse) {
                throw new Exception('Invalid response data');
            }

            $response = $this->paymentProcessor->handlePaymentResponse($encResponse);

            if (isset($response['order_status'])) {
                switch (strtoupper($response['order_status'])) {
                    case 'SUCCESS':
                        $this->handleSuccessfulPayment($response);
                        break;

                    case 'FAILURE':
                        $this->handleFailedPayment($response);
                        break;

                    case 'ABORTED':
                        $this->handleAbortedPayment($response);
                        break;

                    default:
                        // Invalid or unknown status
                        throw new Exception('Invalid payment status received. Security Error. Illegal access detected');
                }
            } else {
                throw new Exception('Payment status not found in response');
            }

        } catch (Exception $e) {
            $this->paymentProcessor->logError('Payment Response Error', $e->getMessage());
            header('Location: /error?message=' . urlencode('Error processing payment response'));
            exit;
        }
    }

    public function logPaymentEvent()
    {
        try {

            $allowedEvents = [
                'payment_success',
                'payment_error',
                'payment_aborted',
                'payment_failed',
                'modal_closed'
            ];

            $rules = [
                'event_type' => ['required', 'string', 'in:' . implode(',', $allowedEvents)],
                'payment_id' => ['string'],
                'order_id' => ['string'],
                'amount' => ['numeric',],
                'currency' => ['string'],
                'error_code' => ['string'],
                'error_description' => ['string']
            ];

            $validator = new Validator($_POST, $rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $firstError = reset($errors)[0];
                throw new Exception($firstError);
            }

            $input = $validator->sanitized();

            $filteredData = array_filter($input, function ($value) {
                return $value !== null;
            });

            $logId = (new PaymentProcessor)->logPaymentDetails(
                $input['event_type'],
                $filteredData
            );

            jsonResponse([
                'success' => true,
                'log_id' => $logId
            ]);

        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 400);
        }
    }

    private function handleSuccessfulPayment($response)
    {
        $this->paymentProcessor->logPaymentDetails('payment_success', $response);

        // send email notification here

        header('Location: /success?order_id=' . urlencode($response['order_id']));
        exit;
    }

    private function handleFailedPayment($response)
    {
        $this->paymentProcessor->logPaymentDetails('payment_failure', $response);
        header('Location: /error?order_id=' . urlencode($response['order_id']));
        exit;
    }

    private function handleAbortedPayment($response)
    {
        $this->paymentProcessor->logPaymentDetails('payment_aborted', $response);
        header('Location: /error?order_id=' . urlencode($response['order_id']));
        exit;
    }

    public function success()
    {
        $rules = [
            'order_id' => ['required', 'string']
        ];
        $validator = new Validator($_GET, $rules);

        if (!$validator->validate()) {
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $input = $validator->sanitized();

        $transactionData = (new PaymentProcessor)->getTransactionDetails($input['order_id']);

        if (!$transactionData) {
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $this->view('pages.success', $transactionData);
    }

    public function error()
    {
        global $db;

        $rules = [
            'log_id' => ['required', 'string']
        ];
        $validator = new Validator($_GET, $rules);

        if (!$validator->validate()) {
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $input = $validator->sanitized();

        $logId = $input['log_id'];
        $error_code = null;
        $error_description = null;
        $payment_details = null;

        if ($logId) {
            // Fetch the log entry
            $sql = "SELECT data FROM cc_payment_logs WHERE id = :id";
            $log = $db->query($sql, ['id' => $logId])->find();

            if ($log) {
                $logData = json_decode($log['data'], true);
                $error_code = $logData['status_code'] ?? null;
                $error_description = $logData['failure_message'] ?? null;
                $payment_details = $logData['details'] ?? null;
            }
        }
        $this->view('pages.error', [
            'error_code' => $error_code,
            'error_description' => $error_description,
            'payment_details' => $payment_details
        ]);
    }

    protected function prepareTransactionData($payment, $postData)
    {
        return [
            'payment_id' => $postData['payment_id'],
            'order_id' => $postData['order_id'],
            'name' => $postData['name'],
            'email' => $postData['email'],
            'address' => $postData['address1'],
            'address2' => $postData['address2'],
            'city' => $postData['city'],
            'state' => $postData['state'],
            'country' => $postData['country'],
            'zip_code' => $postData['zipcode'],
            'amount' => $postData['amount'],
            'currency_type' => $postData['currency'],
            'status' => 'success',
            'payment_method' => $payment['payment_details']['method'],
            'card_network' => $payment['payment_details']['card']['network'] ?? null,
            'card_last4' => $payment['payment_details']['card']['last4'] ?? null,
            'card_issuer' => $payment['payment_details']['card']['issuer'] ?? null,
            'card_type' => $payment['payment_details']['card']['type'] ?? null,
            'bank_name' => $payment['payment_details']['bank'] ?? null,
            'wallet_type' => $payment['payment_details']['wallet'] ?? null,
            'vpa' => $payment['payment_details']['vpa'] ?? null,
        ];
    }
}
