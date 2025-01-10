# Razorpay Integration in Core PHP

## Overview

This is a **Core PHP** project for integrating the **Razorpay** payment gateway into a web application. It allows users to securely make payments, process orders, and verify transactions. This project is designed to be easily customizable for a variety of use cases, and it's equipped with features such as automatic address filling, region-based currency selection, and payment verification.

## Features

- **Automatic Address Autofill**: Automatically fills the user's address on the payment page from `localStorage` (if available).
- **Region-Based Currency Selection**: Detects the user's region and selects the appropriate currency for the payment.
- **Razorpay Payment Integration**: Handles creating orders, making payments, and verifying payments securely.
- **Payment Failure Handling**: Logs payment errors and handles failed payments gracefully.
- **Logging**: Tracks payment events and errors for monitoring and debugging purposes.
- **Customizable**: Easy to adapt for any type of service or e-commerce platform using Razorpay.

## Requirements

- PHP 7.4 or higher
- Composer (for managing dependencies)
- Razorpay API credentials (Key and Secret)
- A web server with PHP support (e.g., Apache, Nginx)

## Setup Instructions

1. **Clone the repository**:
    ```bash
    git clone https://github.com/<your-username>/razorpay-integration.git
    cd razorpay-integration
    ```

2. **Install dependencies**:
    ```bash
    composer install
    ```

3. **Configure Razorpay API**:
    - In the project, you will need to set your Razorpay API keys (key and secret). These can be obtained from the Razorpay dashboard.
    - Add your Razorpay credentials to your `.env` file.

4. **Set up the database**:
    - The SQL for required tables can be found in `database/tables.sql`.

## Workflow

### User Flow:

1. The user enters their payment information (name, email, and address).
2. The payment form is dynamically populated based on the user's region (e.g., currency is automatically set based on location).
3. Upon submission, the payment details are processed, and the payment gateway (Razorpay) handles the transaction.
4. After payment completion, the transaction is verified, and relevant details are logged.

### Payment Verification:

- After the payment is made, the server verifies the payment using the Razorpay API. It ensures that the transaction was successful before completing the order.

### Logging:

- All payment events (success, failure, etc.) are logged to monitor and debug the payment process.

## Contributing

If you'd like to contribute to this project, please fork the repository and submit a pull request with your changes. All contributions are welcome, but please ensure that your changes are well-tested and documented.

## License

This project is open-source and available under the MIT License.
