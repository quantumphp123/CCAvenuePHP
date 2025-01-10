$(document).ready(function () {
    const zipInput = document.getElementById('zip');
    const countrySelect = document.getElementById('country');
    const stateInput = document.getElementById('state');
    const cityInput = document.getElementById('city');

    // Listen for country changes to format ZIP code expectations
    const zipPatterns = {
        US: { pattern: '\\d{5}(-\\d{4})?', placeholder: '12345 or 12345-6789' },
        IN: { pattern: '\\d{6}', placeholder: '123456' },
        CA: { pattern: '[A-Za-z]\\d[A-Za-z] \\d[A-Za-z]\\d', placeholder: 'A0A 0A0' },
        GB: { pattern: '^[A-Z]{1,2}[0-9]{1,2}[A-Z]?\s?[0-9][A-Z]{2}$', placeholder: 'W1A 1AA' },
        DE: { pattern: '\\d{5}', placeholder: '12345' },
        FR: { pattern: '\\d{5}', placeholder: '75000' },
        IT: { pattern: '\\d{5}', placeholder: '00100' },
        ES: { pattern: '\\d{5}', placeholder: '28001' },
        RU: { pattern: '\\d{6}', placeholder: '123456' },
        NL: { pattern: '\\d{4}[A-Za-z]{2}', placeholder: '1234 AB' },
        BE: { pattern: '\\d{4}', placeholder: '1000' },
        SE: { pattern: '\\d{3} \\d{2}', placeholder: '123 45' },
        AT: { pattern: '\\d{4}', placeholder: '1010' },
        PL: { pattern: '\\d{2}-\\d{3}', placeholder: '12-345' },
        CH: { pattern: '\\d{4}', placeholder: '8000' },
        DK: { pattern: '\\d{4}', placeholder: '1000' },
        FI: { pattern: '\\d{5}', placeholder: '00100' },
        NO: { pattern: '\\d{4}', placeholder: '0123' },
        PT: { pattern: '\\d{4}-\\d{3}', placeholder: '1234-567' },
        IE: { pattern: '[A-Z0-9]{3,4}[A-Z0-9]{0,3}', placeholder: 'D01 F5P2' },
        AU: { pattern: '\\d{4}', placeholder: '2000' },
        SG: { pattern: '\\d{6}', placeholder: '123456' },
        MY: { pattern: '\\d{5}', placeholder: '50000' },
        ID: { pattern: '\\d{5}', placeholder: '40100' },
        TH: { pattern: '\\d{5}', placeholder: '10100' },
        PH: { pattern: '\\d{4}', placeholder: '1000' },
        VN: { pattern: '\\d{6}', placeholder: '700000' },
        CN: { pattern: '\\d{6}', placeholder: '100000' },
        KR: { pattern: '\\d{5}', placeholder: '04500' },
        JP: { pattern: '\\d{3}-\\d{4}', placeholder: '123-4567' },
        BD: { pattern: '\\d{4}', placeholder: '1212' },
        MM: { pattern: '\\d{5}', placeholder: '11111' },
        KH: { pattern: '\\d{5}', placeholder: '12000' },
        LK: { pattern: '\\d{5}', placeholder: '10000' },
        NP: { pattern: '\\d{5}', placeholder: '44600' }
    };

    countrySelect.addEventListener('change', function () {
        const country = this.value;
        zipInput.value = '';

        if (zipPatterns[country]) {
            zipInput.setAttribute('pattern', zipPatterns[country].pattern);
            zipInput.setAttribute('placeholder', zipPatterns[country].placeholder);
        } else {
            zipInput.removeAttribute('pattern');
            zipInput.setAttribute('placeholder', 'Enter postal code');
        }
    });


    // ZIP code lookup functionality
    zipInput.addEventListener('change', async function () {
        const zip = this.value;
        const country = countrySelect.value;

        if (!zip || !country) return;

        try {
            // Example using zippopotam.us API (free, no API key required)
            const response = await fetch(`https://api.zippopotam.us/${country}/${zip}`);
            if (!response.ok) throw new Error('Invalid ZIP code');

            const data = await response.json();


            stateInput.value = data.places[0]?.state || '';
            cityInput.value = data.places[0]?.['place name'] || '';

        } catch (error) {
            console.error('Error looking up ZIP code:', error);
            // Optionally show error to user
            alert('Could not find location for this ZIP code. Please fill in the details manually.');
        }
    });
});
