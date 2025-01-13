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
            info('Transaction Not found in success');
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $this->view('pages.success', $transactionData);
    }

    public function error()
    {
        $rules = [
            'order_id' => ['required', 'string']
        ];
        $validator = new Validator($_GET, $rules);

        if (!$validator->validate()) {
            info('Error Validation failed');
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $input = $validator->sanitized();

        $transactionData = (new PaymentProcessor)->getTransactionDetails($input['order_id']);

        if (!$transactionData) {
            info('Transaction Not found in error');
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $this->view('pages.error', $transactionData);
    }
}
