<?php

namespace App\Services;

use DateTime;
use EmailService;
use Exception;

class PaymentProcessor
{
    private $db;
    private $workingKey;
    private $accessCode;
    private $merchantId;
    private $emailService;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->workingKey = $GLOBALS['config']->get('payment')['working_key'];
        $this->accessCode = $GLOBALS['config']->get('payment')['access_code'];
        $this->merchantId = $GLOBALS['config']->get('payment')['merchant_id'];

        // $this->emailService = new EmailService($config['email']);
    }

    public function getUrl()
    {
        $paymentConfig = $GLOBALS['config']->get('payment');
        $environment = $GLOBALS['config']->get('app')['environment'];

        return $environment === 'production'
            ? $paymentConfig['production_url']
            : $paymentConfig['test_url'];
    }



    public function createOrder($amount, $originalAmount, $currency, $originalCurrency, $orderId, $customerData = [])
    {
        try {
            // Prepare merchant data
            $merchantData = [
                'tid' => time(),
                'merchant_id' => $this->merchantId,
                'order_id' => $orderId,
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'redirect_url' => $this->getRedirectUrl(),
                'cancel_url' => $this->getCancelUrl(),
                'language' => 'EN'
            ];

            // Add customer data if provided
            if (!empty($customerData)) {
                $merchantData = array_merge($merchantData, $customerData);
            }

            // Convert to query string
            $merchantDataString = '';
            foreach ($merchantData as $key => $value) {
                $merchantDataString .= $key . '=' . $value . '&';
            }

            $merchantDataString = rtrim($merchantDataString, '&');

            // Encrypt the data
            $encryptedData = $this->encrypt($merchantDataString);

            // Log order creation
            $this->logOrder($orderId, $amount, $currency, $originalAmount, $originalCurrency);

            return [
                'encrypted_data' => $encryptedData,
                'access_code' => $this->accessCode,
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            $this->logError('Order Creation Error', $e->getMessage());
            throw new Exception("Error creating order: " . $e->getMessage());
        }
    }

    private function getRedirectUrl()
    {
        return url('/ccav-response-handler');
    }

    private function getCancelUrl(){
        return url('/ccav-response-handler');
    }

    public function handlePaymentResponse($encResponse)
    {
        try {
            // Decrypt the response
            $decryptedResponse = $this->decrypt($encResponse);
            $responseData = $this->parseResponseString($decryptedResponse);

            info($responseData);

            // Save transaction details
            $this->saveTransaction($responseData);

            return $responseData;
        } catch (Exception $e) {
            $this->logError('Payment Response Error', $e->getMessage());
            throw new Exception("Error processing payment response: " . $e->getMessage());
        }
    }

    private function parseResponseString($responseString)
    {
        info("Parsing Response String...");
        $responseData = [];
        $pairs = explode('&', $responseString);

        foreach ($pairs as $pair) {
            $keyValue = explode('=', $pair);
            if (count($keyValue) == 2) {
                $responseData[$keyValue[0]] = urldecode($keyValue[1]);
            }
        }

        return $responseData;
    }

    public function saveTransaction($data)
    {
        try {
            // Sanitize and validate data
            $sanitizedData = $this->sanitizeTransactionData($data);

            // Insert main transaction record
            $sql = "INSERT INTO hdfc_payment (
            payment_id, order_id, name, email, tel,
            address, city, state, zip_code, country,
            amount, currency_type, original_amount, original_currency,
            bank_ref_no, status, payment_method, card_network,
            transaction_fee, service_tax, error_message, transaction_time
        ) VALUES (
            :payment_id, :order_id, :name, :email, :tel,
            :address, :city, :state, :zip_code, :country,
            :amount, :currency_type, :original_amount, :original_currency,
            :bank_ref_no, :status, :payment_method, :card_network,
            :transaction_fee, :service_tax, :error_message, :transaction_time
        )";

            $this->db->query($sql, $sanitizedData);

            return $this->db->getLastInsertId();
        } catch (Exception $e) {
            $this->logError('Transaction Save Error', $e->getMessage());
            throw new Exception("Error saving transaction: " . $e->getMessage());
        }
    }

    private function sanitizeTransactionData($data)
    {
        $orderDetails = $this->getOrderDetails($data['order_id']);

        // Map CCAvenue response fields to the database fields
        return [
            'payment_id' => $data['tracking_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'name' => $data['billing_name'] ?? null,
            'email' => $data['billing_email'] ?? null,
            'tel' => $data['billing_tel'] ?? null,
            'address' => $data['billing_address'] ?? null,
            'city' => $data['billing_city'] ?? null,
            'state' => $data['billing_state'] ?? null,
            'zip_code' => $data['billing_zip'] ?? null,
            'country' => $data['billing_country'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'currency_type' => $data['currency'] ?? 'INR',
            'original_amount' => $orderDetails['order']['original_amount'] ?? 0,
            'original_currency' => $orderDetails['order']['original_currency'] ?? 'INR',
            'bank_ref_no' => $data['bank_ref_no'] ?? null,
            'status' => $this->mapPaymentStatus($data['order_status'] ?? ''),
            'payment_method' => $data['payment_mode'],
            'card_network' => $data['card_name'] ?? null,
            'transaction_fee' => $data['trans_fee'] ?? null,
            'service_tax' => $data['service_tax'] ?? null,
            'error_message' => $this->getErrorMessage($data),
            'transaction_time' => DateTime::createFromFormat('m/d/Y H:i:s', $data['trans_date'])->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s')
        ];

    }

    private function mapPaymentStatus($ccavStatus)
    {
        $statusMap = [
            'SUCCESS' => 'completed',
            'FAILURE' => 'failed',
            'ABORTED' => 'cancelled',
            'INVALID' => 'error',
            '' => 'pending'
        ];

        return $statusMap[strtoupper($ccavStatus)] ?? 'unknown';
    }

    private function getErrorMessage($data)
    {
        $messages = array_filter([
            $data['failure_message'] ?? null,
            $data['status_message'] ?? null,
            $data['status_code'] ?? null
        ]);

        return !empty($messages) ? implode(' | ', $messages) : null;
    }

    public function logPaymentDetails($eventType, $data)
    {
        // Log raw response for debugging
        $sql = "INSERT INTO cc_payment_logs (
            log_type, data
        ) VALUES (
            :log_type, :data
        )";

        $this->db->query($sql, [
            'log_type' => $eventType,
            'data' => json_encode($data)
        ]);

        return $this->db->getLastInsertId();
    }

    public function logOrder($orderId, $amount, $currency, $originalAmount, $originalCurrency)
    {
        try {
            $sql = "INSERT INTO cc_order_logs (order_id, amount, currency, original_amount, original_currency)
                VALUES (:order_id, :amount, :currency, :original_amount, :original_currency)";

            $this->db->query($sql, [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency
            ]);

        } catch (Exception $e) {
            // Just log the error but don't throw - this is non-critical
            $this->logError('Order Logging Error', $e->getMessage());
        }
    }

    public function logError($type, $message)
    {
        try {
            $sql = "INSERT INTO cc_error_logs (error_type, error_message)
                VALUES (:error_type, :error_message)";

            $this->db->query($sql, [
                'error_type' => $type,
                'error_message' => $message,
            ]);
        } catch (Exception $e) {
            // If we can't even log the error, write to error_log
            logPaymentError("Payment System Error ($type): $message");
        }
    }

    public function getTransactionDetails($orderId)
    {
        try {
            $sql = "SELECT * FROM hdfc_payment WHERE order_id = :order_id LIMIT 1";
            $this->db->query($sql, ['order_id' => $orderId]);
            $transaction = $this->db->find();

            if (!$transaction) {
                $this->logError('Transaction Details Error', "Transaction with payment_id {$orderId} not found.");
                return false;
            }

            return [
                'transaction' => $transaction,
            ];
        } catch (Exception $e) {
            $this->logError('Transaction Details Error', $e->getMessage());
            return false;
        }
    }

    private function getOrderDetails($orderId)
    {
        try {
            // Fetch the order details by order_id
            $sql = "SELECT * FROM cc_order_logs WHERE order_id = :order_id LIMIT 1";
            $this->db->query($sql, ['order_id' => $orderId]);
            $order = $this->db->find(); // Use find() to get the first result

            if (!$order) {
                // Log the error if transaction is not found
                $this->logError('Order Details Error', "Order with order_id {$orderId} not found.");
                return false;
            }

            return [
                'order' => $order,
            ];
        } catch (Exception $e) {
            $this->logError('Transaction Details Error', $e->getMessage());
            return false;
        }
    }

    public function refundPayment($paymentId, $amount = null)
    {
        try {
            $refund = $this->api->refund->create([
                'payment_id' => $paymentId,
                'amount' => $amount ? $amount * 100 : null // Optional partial refund
            ]);

            // Save refund details
            $this->saveRefund($paymentId, $refund);

            return $refund;
        } catch (Exception $e) {
            $this->logError('Refund Error', $e->getMessage());
            throw new Exception("Error processing refund: " . $e->getMessage());
        }
    }

    private function saveRefund($paymentId, $refundData)
    {
        try {
            $sql = "INSERT INTO payment_refunds (
                payment_id, refund_id, amount, status, created_at
            ) VALUES (
                :payment_id, :refund_id, :amount, :status, NOW()
            )";

            $this->db->query($sql, [
                'payment_id' => $paymentId,
                'refund_id' => $refundData->id,
                'amount' => $refundData->amount / 100,
                'status' => $refundData->status
            ]);

            // Update original transaction status
            $this->updateTransactionStatus($paymentId, 'refunded');
        } catch (Exception $e) {
            $this->logError('Refund Save Error', $e->getMessage());
            throw new Exception("Error saving refund details: " . $e->getMessage());
        }
    }

    private function updateTransactionStatus($paymentId, $status)
    {
        $sql = "UPDATE hdfc_payment
                SET status = :status, updated_at = NOW()
                WHERE payment_id = :payment_id";

        $this->db->query($sql, [
            'status' => $status,
            'payment_id' => $paymentId
        ]);
    }

    public function getPaymentStatistics($startDate = null, $endDate = null)
    {
        try {
            $params = [];
            $dateCondition = "";

            if ($startDate && $endDate) {
                $dateCondition = "WHERE created_at BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }

            $sql = "SELECT
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    currency_type,
                    status,
                    COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_transactions,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
                    AVG(CASE WHEN status = 'success' THEN amount END) as avg_transaction_amount
                FROM hdfc_payment
                $dateCondition
                GROUP BY currency_type, status";

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            $this->logError('Statistics Error', $e->getMessage());
            throw new Exception("Error generating payment statistics: " . $e->getMessage());
        }
    }

    private function encrypt($plainText)
    {
        $key = $this->hextobin(md5($this->workingKey));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return bin2hex($openMode);
    }

    private function decrypt($encryptedText)
    {
        $key = $this->hextobin(md5($this->workingKey));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = $this->hextobin($encryptedText);
        return openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
    }

    private function hextobin($hexString)
    {
        $length = strlen($hexString);
        $binString = "";
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack("H*", $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }
        return $binString;
    }
}
