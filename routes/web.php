<?php

use App\Controllers\OrderController;
use App\Controllers\PagesController;
use App\Controllers\PaymentController;
use App\Controllers\ReceiptController;



// Return a closure that registers the routes
return function ($router) {
    $router->get('/', [PagesController::class, 'index']);

    $router->post('/create-order', [OrderController::class, 'create']);
    $router->post('/ccav-response-handler', [PaymentController::class, 'handleResponse']);
    $router->post('/log-payment-event', [PaymentController::class, 'logPaymentEvent']);
    $router->get('/success', [PaymentController::class, 'success']);
    $router->get('/error', [PaymentController::class, 'error']);

    $router->get('/docs/ccav-response', [PagesController::class, 'ccavResponseDoc']);
    $router->get('/policy', [PagesController::class, 'policy']);
    $router->get('/download-receipt/html/{order_id}', [ReceiptController::class, 'downloadHTML']);
    $router->get('/download-receipt/pdf/{order_id}', [ReceiptController::class, 'downloadPDF']);
};
