# CCAvenue Integration in Core PHP

## Overview

This is a Core PHP project for integrating the CCAvenue payment gateway into a web application. It utilizes the non-seamless kit provided by CCAvenue and includes robust error handling for every possible scenario, along with decent styling for user interfaces. This project ensures secure payment transactions, proper error logging, and a customizable framework for diverse use cases.

## Features

- **Automatic Address Autofill**: Automatically fills the user's address on the payment page from `localStorage` (if available).
- **Region-Based Currency Selection**: Detects the user's region and selects the appropriate currency for the payment.
- **CCAvenue Payment Integration: Handles order creation, payment processing, and transaction verification securely using the non-seamless kit.
- **Error Handling: Manages payment failures, API errors, and other scenarios with proper logging and user feedback.
- **Customizable and Modular: Easily adaptable for e-commerce, service-based applications, or other use cases using CCAvenue.
- **Logging: Tracks payment events and errors for monitoring and debugging.

## Requirements

- PHP 7.4 or higher
- Composer (for managing dependencies)
- CCAvenue API credentials (Merchant ID, Access Code, and Working Key)
- A web server with PHP support (e.g., Apache, Nginx)

## Setup Instructions

1. **Clone the repository**:
    ```bash
    git clone https://github.com/<your-username>/ccavenue-integration.git
    cd ccavenue-integration
    ```

2. **Install dependencies**:
    ```bash
    composer install
    ```

3. **Configure Razorpay API**:
    - Obtain your CCAvenue Merchant ID, Access Code, and Working Key from the CCAvenue dashboard.
    - Add these credentials to your .env file or directly to the configuration file (config/payment.php)..

4. **Set up the database**:
    - The SQL for required tables can be found in `database/tables.sql`.

## Workflow

### User Flow:

1. The user enters their payment information (name, email, and address).
2. The payment form is dynamically populated based on the user's region (e.g., currency is automatically set based on location).
3. Upon submission, the payment details are processed, and the payment gateway (Razorpay) handles the transaction.
4. After payment completion, the transaction is verified, and relevant details are logged.

### Payment Verification:

- The server verifies the payment using CCAvenue's checksum method to ensure the integrity and success of the transaction before proceeding.

### Logging:

- All payment events (success, failure, etc.) are logged to monitor and debug the payment process.

## Contributing

If you'd like to contribute to this project, please fork the repository and submit a pull request with your changes. All contributions are welcome, but please ensure that your changes are well-tested and documented.

## License

This project is open-source and available under the MIT License.
