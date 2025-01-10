// When the payment page is loaded, auto-fill the address if available in localStorage
window.addEventListener('load', function () {
    const savedAddress = localStorage.getItem('userAddress');

    if (savedAddress) {
        const address = JSON.parse(savedAddress);

        document.getElementById('address1').value = address.address1 || '';
        document.getElementById('address2').value = address.address2 || '';
        document.getElementById('city').value = address.city || '';
        document.getElementById('state').value = address.state || '';
        document.getElementById('zip').value = address.zip || '';
        document.getElementById('country').value = address.country || '';
    }
});

$(document).ready(function () {
    setCurrency();

    $('#payment-form').on('submit', function (e) {
        e.preventDefault();

        // Disable submit button to prevent double submission
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);

        if (!validateInput()) {
            submitButton.prop('disabled', false);
            return;
        }

        // Save user address in localStorage for future
        saveAddress();

        // Show loading state
        const loadingOverlay = showLoadingOverlay();

        $.ajax({
            url: '/create-order',
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.error) {
                    handleError(response.error);
                    submitButton.prop('disabled', false);
                    hideLoadingOverlay(loadingOverlay);
                    return;
                }

                // Create and submit the CCAvenue form
                const form = $('<form>', {
                    method: 'POST',
                    action: response.transaction_url,
                    name: 'redirect',
                    style: 'display: none'
                });

                // Add the encrypted data and access code as hidden fields
                $('<input>').attr({
                    type: 'hidden',
                    name: 'encRequest',
                    value: response.encrypted_data
                }).appendTo(form);

                $('<input>').attr({
                    type: 'hidden',
                    name: 'access_code',
                    value: response.access_code
                }).appendTo(form);

                // Append the form to the body
                $('body').append(form);

                // Submit the form
                form.submit();

                // Clean up by removing the form after submission
                setTimeout(() => {
                    form.remove();
                    hideLoadingOverlay(loadingOverlay);
                }, 1000);

                // // Create modal/container for iframe
                // const modal = $('<div>').addClass('payment-modal')
                //     .append($('<div>').addClass('payment-iframe-container')
                //         .append(response.iframe));

                // // Add modal to body
                // $('body').append(modal);

                // // Setup iframe height listener
                // window.addEventListener('message', function (e) {
                //     $("#paymentFrame").css("height", e.data['newHeight'] + 'px');
                // }, false);

                // hideLoadingOverlay(loadingOverlay);
            },
            error: function (xhr, status, error) {
                handleError('Error creating order: ' + error);
                submitButton.prop('disabled', false);
                hideLoadingOverlay(loadingOverlay);
            }
        });
    });

    function setCurrency() {
        // Detect user's region and set currency
        fetch('https://ipapi.co/json/')
            .then(response => response.json())
            .then(data => {
                const currencyMap = {
                    'IN': 'INR',
                    'US': 'USD',
                    'GB': 'GBP',
                    'EU': 'EUR',
                    'AU': 'AUD',
                    'CA': 'CAD',
                    'SG': 'SGD',
                };
                const currency = currencyMap[data.country] || 'INR';
                $('#currency').val(currency);
            });
    }

    function validateInput() {
        // Clear previous errors
        $('.error-message').remove();
        $('.border-red-500').removeClass('border-red-500');

        const requiredFields = document.getElementById('payment-form').querySelectorAll('[required]');
        let isValid = true;
        let errors = [];

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                $(field).after(`<div class="error-message text-red-500 text-sm mt-1">This field is required</div>`);
                errors.push(`${field.name} is required`);
            } else {
                // Additional validation based on field type
                if (field.type === 'email' && !isValidEmail(field.value)) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    $(field).after(`<div class="error-message text-red-500 text-sm mt-1">Invalid email format</div>`);
                    errors.push('Invalid email format');
                }
                if (field.name === 'amount' && (!isValidAmount(field.value))) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    $(field).after(`<div class="error-message text-red-500 text-sm mt-1">Invalid amount</div>`);
                    errors.push('Invalid amount');
                }
            }
        });

        return isValid;
    }

    // Helper functions
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidAmount(amount) {
        return !isNaN(amount) && parseFloat(amount) > 0;
    }

    function showLoadingOverlay() {
        const overlay = $('<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"><div class="bg-white p-4 rounded-lg">Processing...</div></div>');
        $('body').append(overlay);
        return overlay;
    }

    function hideLoadingOverlay(overlay) {
        overlay.remove();
    }

    function handleError(message, data = null) {
        // Prepare error data object
        const errorData = {
            error_code: data?.error_code || 'ERROR',
            error_description: message,
            details: {
                order_id: data?.order_id,
                amount: data?.amount,
                currency: data?.currency,
                timestamp: new Date().toLocaleString(),
                ...data // Spread any additional data
            }
        };

        // Clean up undefined/null values
        Object.keys(errorData.details).forEach(key => {
            if (errorData.details[key] === undefined || errorData.details[key] === null) {
                delete errorData.details[key];
            }
        });

        // Log the payment event and wait for the log ID
        logPaymentEvent('payment_error', errorData).then(logId => {
            // Create and show error message
            const errorDiv = $('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"></div>')
                .text(message);

            $('#payment-form').prepend(errorDiv);

            $('html, body').animate({
                scrollTop: errorDiv.offset().top - 20
            }, 500);

            setTimeout(() => {
                errorDiv.fadeOut('slow', function () {
                    $(this).remove();
                });

                // Redirect to error page with log ID
                window.location.href = `/error?log_id=${logId}`;
            }, 3000);
        });
    }

    function logPaymentEvent(event_type, data) {
        $.ajax({
            url: '/log-payment-event',
            method: 'POST',
            data: {
                event_type: event_type,
                csrf_token: $('input[name="csrf_token"]').val(),
                ...data
            }
        }).then(response => response.log_id);
    }

    function saveAddress() {
        const saveAddressCheckbox = document.getElementById('save-address'); // Save address checkbox

        const address = {
            address1: document.getElementById('address1').value,
            address2: document.getElementById('address2').value,
            city: document.getElementById('city').value,
            state: document.getElementById('state').value,
            zip: document.getElementById('zip').value,
            country: document.getElementById('country').value
        };

        // Save to localStorage
        if (saveAddressCheckbox.checked) {
            const existingAddress = localStorage.getItem('userAddress');

            // Compare the current address with the existing one
            if (existingAddress) {
                const parsedExistingAddress = JSON.parse(existingAddress);

                // Only save if the address is different
                if (JSON.stringify(parsedExistingAddress) !== JSON.stringify(address)) {
                    localStorage.setItem('userAddress', JSON.stringify(address));
                    console.log('Address saved locally.');
                }
            } else {
                // If no address is saved, store it
                localStorage.setItem('userAddress', JSON.stringify(address));
                console.log('Address saved locally.');
            }
        }

    }
});
