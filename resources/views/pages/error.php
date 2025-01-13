<?php
$pageTitle = "Payment Failed";
include VIEW_PATH . 'layouts/layout.php';

$status = ucfirst($data['transaction']['status']);

// Determine the badge color based on the status
$badgeColor = '';
switch ($status) {
    case 'Failed':
        $badgeColor = 'bg-red-400 border-red-500'; // Red for failed
        break;
    case 'Cancelled':
        $badgeColor = 'bg-yellow-400 border-yellow-500'; // Yellow for cancelled
        break;
    case 'Success':
        $badgeColor = 'bg-green-400 border-green-500'; // Green for success
        break;
    default:
        $badgeColor = 'bg-gray-400 border-gray-500'; // Default gray if status is unknown
        break;
}
?>


<div class="max-w-2xl mx-auto rounded-2xl shadow-lg overflow-hidden">
    <!-- Header with gradient -->
    <div class="bg-gradient-to-r from-red-300 to-red-700 p-6">
        <div class="flex justify-center mt-2">
            <div class="flex justify-center items-center w-24 h-24 bg-gray-100 rounded-full animate-bounce">
                <div
                    class="relative w-20 h-20 bg-white text-red-400 flex justify-center items-center rounded-full shadow-lg border-red-600 border-2">
                    <!-- Credit Card Icon -->
                    <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h.01M11 15h2M7 15a2 2 0 100-4 2 2 0 000 4z M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" />
                    </svg>
                    <!-- X Mark Overlay -->
                    <div class="absolute -top-2 -right-2 bg-red-600 rounded-full p-1">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <h1 class="text-2xl font-bold text-white text-center mt-2 animate-fade-in">
            Payment Failed
        </h1>
    </div>
    <div class="p-8">
        <div class="space-y-6">
            <!-- Primary Details Card -->
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Order ID</p>
                        <p class="font-mono text-sm">
                            <?php echo htmlspecialchars($data['transaction']['order_id']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Transaction ID</p>
                        <p class="font-mono text-sm">
                            <?php echo htmlspecialchars($data['transaction']['payment_id']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-mono text-sm">
                            <span
                                class="inline-block px-3 py-1 text-xs text-white rounded-md border <?php echo $badgeColor; ?>">
                                <?php echo $status; ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Datetime</p>
                        <p class="font-mono text-sm">
                            <?php echo date('d M Y, h:i A', strtotime($data['transaction']['transaction_time'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php if ($data['transaction']['error_message']) { ?>
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <div>
                    <p class="text-sm text-gray-500">Message</p>
                    <p class="font-mono text-sm">
                        <?php echo htmlspecialchars($data['transaction']['error_message']); ?>
                    </p>
                </div>
            </div>
            <?php } ?>
            <!-- Action Buttons -->
            <div class="flex items-center justify-center space-x-4">
                <a href="<?php echo $GLOBALS['config']->get('app')['url']; ?>"
                    class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-300 hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Try Again
                </a>
                <a href="mailto:<?php echo $GLOBALS['config']->get('app')['support']; ?>"
                    class="inline-flex items-center px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Contact Support
                </a>
            </div>
            <?php if ($data['transaction']['status'] !== 'cancelled') { ?>
            <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 animate-fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>If you're unsure whether your payment went through or if you see a charge on
                                your account, please:</p>
                            <ul class="list-disc ml-5 mt-2">
                                <li>Check your email for a payment confirmation</li>
                                <li>Wait a few minutes and check your bank statement</li>
                                <li>Do not attempt to pay again immediately</li>
                                <li>Contact our support team with your order reference for assistance</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php include VIEW_PATH . 'layouts/footer.php'; ?>
