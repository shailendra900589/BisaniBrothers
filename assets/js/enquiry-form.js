(function () {
    function digits(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function validateForm(form) {
        var name = (form.querySelector('[name="name"]')?.value || '').trim();
        var email = (form.querySelector('[name="email"]')?.value || '').trim();
        var phoneField = form.querySelector('[name="phone"]') || form.querySelector('[name="mobile"]');
        var phone = digits(phoneField ? phoneField.value : '');
        var message = (form.querySelector('[name="message"]')?.value || '').trim();

        if (name.length < 2) {
            return 'Please enter your full name.';
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return 'Please enter a valid email address.';
        }
        if (phone.length < 10) {
            return 'Please enter a valid 10-digit mobile number.';
        }
        if (message.length < 5) {
            return 'Please enter your message (at least 5 characters).';
        }

        return null;
    }

    document.querySelectorAll('form[data-enquiry-form="1"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var error = validateForm(form);
            if (error) {
                event.preventDefault();
                alert(error);
            }
        });
    });
})();
