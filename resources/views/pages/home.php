<?php

use App\Services\CurrencyService;
$pageTitle = "Payments | Quantum IT Innovation";
include VIEW_PATH . 'layouts/layout.php';
include VIEW_PATH . 'components/input-field.php';
?>

<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
    <h1 class="text-2xl font-bold mb-6 text-center">Payment Form</h1>
    <form id="payment-form" class="space-y-4">
        <!-- Personal Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div>
                <?php inputField('name', 'name', 'Full Name', 'text', true); ?>
            </div>
            <div>
                <?php inputField('email', 'email', 'Email', 'email', true); ?>
            </div>
            <div>
                <?php inputField('tel', 'tel', 'Phone', 'phone', true); ?>
            </div>
        </div>

        <!-- Address Section -->
        <div class="border-t pt-4 mt-4">
            <h2 class="text-lg font-semibold mb-4">Billing Address</h2>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="country" class="block mb-2 text-sm font-medium text-gray-900">Country</label>
                    <select name="country" id="country" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 focus:outline-none focus:ring-1 shadow-sm">
                        <option value="">Select Country</option>
                        <option value="AU">Australia</option>
                        <option value="AT">Austria</option>
                        <option value="BD">Bangladesh</option>
                        <option value="BE">Belgium</option>
                        <option value="KH">Cambodia</option>
                        <option value="CA">Canada</option>
                        <option value="CN">China</option>
                        <option value="DE">Germany</option>
                        <option value="DK">Denmark</option>
                        <option value="FI">Finland</option>
                        <option value="FR">France</option>
                        <option value="IE">Ireland</option>
                        <option value="ID">Indonesia</option>
                        <option value="IN">India</option>
                        <option value="IT">Italy</option>
                        <option value="JP">Japan</option>
                        <option value="MY">Malaysia</option>
                        <option value="MM">Myanmar</option>
                        <option value="NL">Netherlands</option>
                        <option value="NO">Norway</option>
                        <option value="NP">Nepal</option>
                        <option value="PH">Philippines</option>
                        <option value="PL">Poland</option>
                        <option value="PT">Portugal</option>
                        <option value="RU">Russia</option>
                        <option value="SG">Singapore</option>
                        <option value="ES">Spain</option>
                        <option value="KR">South Korea</option>
                        <option value="LK">Sri Lanka</option>
                        <option value="SE">Sweden</option>
                        <option value="CH">Switzerland</option>
                        <option value="TH">Thailand</option>
                        <option value="GB">United Kingdom</option>
                        <option value="US">United States</option>
                        <option value="VN">Vietnam</option>
                    </select>
                </div>
                <div>
                    <?php inputField('zip', 'zip', 'ZIP/Postal Code', 'text', true); ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <?php inputField('state', 'state', 'State/Province', 'text', true); ?>
                </div>
                <div>
                    <?php inputField('city', 'city', 'City', 'text', true); ?>
                </div>
            </div>

            <div class="mb-4">
                <?php inputField('address1', 'address1', 'Address Line 1', 'text', true, 'Street address, apartment, suite'); ?>
            </div>

            <div class="mb-4">
                <?php inputField('address2', 'address2', 'Address Line 2 (Optional)', 'text', false, 'Building, floor, etc.'); ?>
            </div>
            <div class="flex items-center space-x-2 mb-4">
                <input type="checkbox" id="save-address"
                    class="h-4 w-4 border-gray-300 rounded text-indigo-600 focus:ring-indigo-500">
                <label for="save-address" class="text-sm text-gray-600">
                    Save address for future use
                </label>
            </div>

        </div>

        <!-- Payment Details -->
        <div class="border-t pt-4 mt-4">
            <h2 class="text-lg font-semibold mb-4">Payment Details</h2>
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div>
                    <?php inputField('amount', 'amount', 'Amount', 'number', true, 'Enter amount', '', '', ['min' => '1']); ?>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Currency</label>
                    <select name="currency" id="currency"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 focus:outline-none focus:ring-1 shadow-sm">
                        <option value="INR">Indian Rupee (INR)</option>
                        <option value="USD">US Dollar (USD)</option>
                        <option value="AUD">Australian Dollar (AUD)</option>
                        <option value="GBP">British Pound (GBP)</option>
                        <option value="CAD">Canadian Dollar (CAD)</option>
                        <option value="EUR">Euro (EUR)</option>
                        <option value="SGD">Singapore Dollar (SGD)</option>
                    </select>
                </div>
            </div>
            <div>
                <div class="flex items-end space-x-2">
                    <div>
                        <?php inputField('amount_inr', 'amount_inr', 'Amount(' . (new CurrencyService)->baseCurrency . ')', 'text', false, 'Converted amount will be shown here...', '', 'bg-gray-200', ['disabled' => true, 'readonly' => true]); ?>
                    </div>

                    <!-- Info Button with Improved Positioning -->
                    <button id="info-button"
                        class="relative inline-flex items-center justify-center w-6 h-6 text-gray-400 hover:text-blue-700 transition-colors duration-200 rounded-full hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        aria-label="Currency conversion information">
                        <i class="bi bi-info-circle text-xl"></i>
                    </button>
                </div>
                <!-- Enhanced Popover -->
                <div id="popover"
                    class="hidden absolute right-0 w-96 max-w-[calc(100vw-2rem)] md:max-w-fit bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 p-3 z-50">
                    <div class="flex flex-col space-y-4">
                        <div class="border-b pb-3">
                            <h3 class="text-xs font-semibold text-gray-900">Conversion Information</h3>
                            <p class="mt-1 text-xs text-gray-500">Important details about currency conversion</p>
                        </div>

                        <ul class="space-y-1 text-xs text-gray-600">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 text-blue-600 mr-2">•</span>
                                <span>Amounts are automatically converted to INR before processing</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 text-blue-600 mr-2">•</span>
                                <span>Conversion rates are updated regularly and subject to policy changes</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 text-blue-600 mr-2">•</span>
                                <span>Additional fees may apply to currency conversion transactions</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 text-blue-600 mr-2">•</span>
                                <span>Rates fetched are based on the latest available data</span>
                            </li>
                        </ul>

                        <!-- Conversion Chart Container -->
                        <div id="conversion-chart" class="mt-4 border-t pt-4">
                            <!-- Chart will be inserted here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
            Proceed to Payment
        </button>
    </form>
</div>
<script>
const APP_URL = "<?php echo $GLOBALS['config']->get('app')['url']; ?>";
</script>
<?php
$pageScripts = [
    '/assets/js/payment.js',
    '/assets/js/payment-helper.js'
];
include VIEW_PATH . 'layouts/footer.php';
?>
