<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Domain checkout'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
</head>
<body class="domain-app">
    <main class="checkout-page">
        <a class="domain-brand checkout-brand" href="{{ route('domains.index') }}">@include('partials.logo', ['gid' => 'c'])</a>
        <div class="checkout-head">
            <div><span class="domain-kicker">SECURE CHECKOUT</span><h1>Complete your domain order.</h1><p>Your domain will be prepared after payment verification.</p></div>
            <a href="{{ route('domains.index') }}">← Back to domain search</a>
        </div>
        <div class="checkout-grid">
            <form class="checkout-form" method="POST" action="{{ route('domains.place-order') }}">
                @csrf
                @if(session('cart_added'))<div class="domain-alert success cart-added"><span>✓ <b>{{ session('cart_added') }}</b> was added to your cart.</span></div>@endif
                @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
                <h2>Who are you buying for?</h2>
                <div class="buyer-options">
                    <label class="buyer-option">
                        <input type="radio" name="purchase_for" value="myself" {{ old('purchase_for', 'myself') === 'myself' ? 'checked' : '' }}>
                        <span><strong>Myself</strong><small>{{ $accountDetails['email'] ?: 'Register the domain for yourself' }}</small></span>
                    </label>
                    <label class="buyer-option">
                        <input type="radio" name="purchase_for" value="customer" {{ old('purchase_for') === 'customer' ? 'checked' : '' }}>
                        <span><strong>Customer</strong><small>Register the domain for someone else</small></span>
                    </label>
                </div>
                <input type="hidden" name="customer_identifier" id="selectedCustomerIdentifier" value="">
                <section class="existing-customer-panel" id="existingCustomerPanel" hidden>
                    <div class="existing-customer-entry" id="existingCustomerEntry">
                        <div class="existing-customer-search">
                            <input id="existingCustomerIdentifier" type="text" value="{{ old('customer_identifier') }}" placeholder="Email or Customer ID" autocomplete="off" aria-label="Email or Customer ID" required>
                            <button class="existing-customer-confirm" id="confirmExistingCustomer" type="button">Confirm</button>
                        </div>
                        <div class="customer-lookup-message" id="customerLookupMessage" role="status" aria-live="polite"></div>
                        <div class="customer-create-divider"><span>OR</span></div>
                        <a class="create-customer-link" href="{{ route('register') }}" target="_blank" rel="noopener">＋ Create New Customer</a>
                    </div>
                    <div class="existing-customer-result" id="existingCustomerResult" hidden>
                        <div><strong>Verified customer found</strong><span id="selectedCustomerIdentifierText"></span><small>The domain order will be assigned to this account after payment.</small></div>
                        <button class="change-customer-button" id="changeCustomer" type="button">Change</button>
                    </div>
                </section>
                <section
                    class="checkout-contact-section"
                    data-account-details-complete="{{ $accountDetailsComplete ? 'true' : 'false' }}"
                >
                    @if(session('customer') && $accountDetailsComplete)
                        <div class="checkout-account-summary" id="checkoutAccountSummary">
                            <strong>{{ $accountDetails['name'] }}</strong>
                            <span>{{ $accountDetails['email'] }}</span>
                            @if($accountDetails['phone'])<span>{{ $accountDetails['phone'] }}</span>@endif
                            <small>Using your account details</small>
                        </div>
                    @else
                        <h2>Contact details</h2>
                        <div class="checkout-fields" id="checkoutContactFields">
                            <label>Full name<input name="full_name" value="{{ old('full_name', $accountDetails['name']) }}" required></label>
                            <label>Email address<input name="email" type="email" value="{{ old('email', $accountDetails['email']) }}" autocomplete="email" required></label>
                            <label>Phone number<input name="phone" type="tel" value="{{ old('phone', $accountDetails['phone']) }}" placeholder="01XXXXXXXXX" required></label>
                        </div>
                    @endif
                </section>
                <h2>Choose payment method</h2>
                <div class="payment-options">
                    @foreach($methods as $key => $method)
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="{{ $key }}" {{ $key === $defaultPaymentMethod ? 'checked' : '' }}>
                            <span><strong>{{ $method['label'] }}</strong><small>{{ $method['description'] }}</small></span>
                            <b>›</b>
                        </label>
                    @endforeach
                </div>
                <button class="checkout-submit" type="submit" {{ old('purchase_for', 'myself') === 'customer' ? 'disabled' : '' }}>Place order · ৳ {{ number_format($total) }} <b>→</b></button>
                @if(isset($gatewayReady) && !$gatewayReady)
                    <small class="checkout-note" style="color:#c9822f">bKash gateway is not configured on this server yet — bKash orders will be saved and processed manually by our billing team.</small>
                @else
                    <small class="checkout-note">bKash orders use the configured live gateway and will charge a real payment.</small>
                @endif
            </form>
            <aside class="checkout-summary">
                <div class="checkout-summary-heading"><span class="domain-kicker">ORDER SUMMARY</span><span class="domain-kicker">YOUR COST</span></div>
                <h2>{{ count($cart) }} domain{{ count($cart) > 1 ? 's' : '' }}</h2>
                @foreach($cart as $item)
                    <div class="summary-item">
                        <span>
                            {{ $item['domain'] }}
                            <small>
                                × {{ $item['years'] }} year{{ $item['years'] > 1 ? 's' : '' }}
                                <br>renews ৳ {{ number_format($item['renewal_price'] ?? $item['price']) }}/year
                            </small>
                        </span>
                        <b>৳ {{ number_format($item['price'] * $item['years']) }}</b>
                    </div>
                @endforeach
                <div class="summary-total"><span>Subtotal</span><strong>৳ {{ number_format($total) }}</strong></div>
                <div class="checkout-summary-tax"><span>Tax</span><span>৳ 0</span></div>
                <div class="checkout-summary-tax"><span>VAT</span><span>৳ 0</span></div>
                <div class="summary-total checkout-grand-total"><span>Your total cost</span><strong>৳ {{ number_format($total) }}</strong></div>
                <p>30-day support included with every registration.</p>
            </aside>
        </div>
    </main>
    @include('partials.chat')
    <script>
        (function () {
            var buyerOptions = document.querySelectorAll('input[name="purchase_for"]');
            var payButton = document.querySelector('.checkout-form .checkout-submit');
            var contactSection = document.querySelector('.checkout-contact-section');
            var accountDetailsComplete = contactSection.getAttribute('data-account-details-complete') === 'true';
            var contactFields = document.getElementById('checkoutContactFields');
            var accountSummary = document.getElementById('checkoutAccountSummary');
            var customerPanel = document.getElementById('existingCustomerPanel');
            var customerEntry = document.getElementById('existingCustomerEntry');
            var customerResult = document.getElementById('existingCustomerResult');
            var selectedCustomerIdentifier = document.getElementById('selectedCustomerIdentifier');
            var customerConfirmed = false;

            function updatePayButton() {
                var selectedBuyer = document.querySelector('input[name="purchase_for"]:checked');
                var myselfSelected = selectedBuyer && selectedBuyer.value === 'myself';
                var customerSelected = selectedBuyer && selectedBuyer.value === 'customer';
                var useAccountDetails = myselfSelected && accountDetailsComplete;
                payButton.disabled = customerSelected && !customerConfirmed;
                payButton.setAttribute('aria-disabled', String(payButton.disabled));
                customerPanel.hidden = !customerSelected;
                contactSection.hidden = customerSelected;
                if (contactFields) {
                    contactFields.hidden = customerSelected || useAccountDetails;
                    contactFields.querySelectorAll('input').forEach(function (input) {
                        input.disabled = customerSelected || useAccountDetails;
                    });
                }
                if (accountSummary) {
                    accountSummary.hidden = customerSelected || !useAccountDetails;
                }
            }

            buyerOptions.forEach(function (option) {
                option.addEventListener('change', updatePayButton);
            });

            var confirmButton = document.getElementById('confirmExistingCustomer');
            if (confirmButton) {
                confirmButton.addEventListener('click', function () {
                    var identifierInput = document.getElementById('existingCustomerIdentifier');
                    var message = document.getElementById('customerLookupMessage');
                    var identifier = identifierInput.value.trim();
                    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    customerConfirmed = false;
                    selectedCustomerIdentifier.value = '';
                    updatePayButton();
                    message.textContent = '';
                    if (!identifierInput.checkValidity()) {
                        identifierInput.reportValidity();
                        return;
                    }

                    confirmButton.disabled = true;
                    confirmButton.textContent = 'Checking...';
                    var formData = new FormData();
                    formData.append('identifier', identifier);

                    fetch(@json(route('domains.customer.lookup')), {
                        method: 'POST',
                        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': token},
                        body: formData,
                        credentials: 'same-origin'
                    }).then(function (response) {
                        return response.json().then(function (body) {
                            if (!response.ok) {
                                throw new Error(body.message || 'Could not verify this customer.');
                            }
                            return body;
                        });
                    }).then(function (customer) {
                        document.getElementById('selectedCustomerIdentifierText').textContent = identifier;
                        selectedCustomerIdentifier.value = identifier;
                        customerConfirmed = true;
                        customerEntry.hidden = true;
                        customerResult.hidden = false;
                        updatePayButton();
                    }).catch(function (error) {
                        message.textContent = error.message;
                    }).finally(function () {
                        confirmButton.disabled = false;
                        confirmButton.textContent = 'Confirm';
                    });
                });
            }

            var changeCustomer = document.getElementById('changeCustomer');
            if (changeCustomer) {
                changeCustomer.addEventListener('click', function () {
                    customerConfirmed = false;
                    selectedCustomerIdentifier.value = '';
                    customerEntry.hidden = false;
                    customerResult.hidden = true;
                    document.getElementById('customerLookupMessage').textContent = '';
                    updatePayButton();
                });
            }

            updatePayButton();
        }());
    </script>
</body>
</html>
