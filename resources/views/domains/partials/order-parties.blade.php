{{--
    "Who is this for, and which domain" card, shown on the payment pages (bKash page,
    and the order page Nagad / bank orders land on) so the buyer sees exactly what
    they are paying for before they pay.

    Reads $order (the session order array, or the one rebuilt from the database):
      $order['items'][]   domain, years, order_type (registration | transfer)
      $order['customer']  full_name, email, phone, purchase_for (myself | customer)

    For "Myself" those are the buyer's own details; when buying for a customer the
    order stores THAT customer's details (DomainController::placeOrder), so the card
    always shows the person the domain will belong to.

    Renders nothing when the order has neither (the wallet top-up page reuses the
    bKash view and has no domain).
--}}
@php
    $opCustomer = isset($order['customer']) && is_array($order['customer']) ? $order['customer'] : [];
    $opName = trim((string) ($opCustomer['full_name'] ?? ''));
    $opEmail = trim((string) ($opCustomer['email'] ?? ''));
    $opPhone = trim((string) ($opCustomer['phone'] ?? ''));
    $opFor = $opCustomer['purchase_for'] ?? null;
    $opItems = isset($order['items']) && is_array($order['items']) ? array_values($order['items']) : [];
    $opHasPerson = $opName !== '' || $opEmail !== '' || $opPhone !== '';
    // Hosting orders also pass 'plan' => ['name', 'meta'], 'items_heading' and a per-item 'label'.
    $opPlan = isset($order['plan']) && is_array($order['plan']) ? $order['plan'] : null;
@endphp
@if($opItems || $opHasPerson || $opPlan)
    <section class="ordp" aria-label="Order details">
        @if($opPlan)
            <div class="ordp-col">
                <small>Hosting plan</small>
                <div class="ordp-dom">
                    <strong>{{ $opPlan['name'] ?? '' }}</strong>
                    @if(!empty($opPlan['meta']))<span>{{ $opPlan['meta'] }}</span>@endif
                </div>
            </div>
        @endif
        @if($opItems)
            <div class="ordp-col">
                <small>{{ $order['items_heading'] ?? (count($opItems) > 1 ? 'Domains' : 'Domain') }}</small>
                @foreach($opItems as $opItem)
                    @php($opYears = max(1, (int) ($opItem['years'] ?? 1)))
                    <div class="ordp-dom">
                        <strong>{{ $opItem['domain'] ?? '' }}</strong>
                        <span>{{ $opItem['label'] ?? ((($opItem['order_type'] ?? 'registration') === 'transfer' ? 'Transfer' : 'Registration') . ' · ' . $opYears . ' ' . ($opYears > 1 ? 'years' : 'year')) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
        @if($opHasPerson)
            <div class="ordp-col">
                <small>Buying for @if($opFor === 'customer')<b class="ordp-tag">Customer</b>@elseif($opFor === 'myself')<b class="ordp-tag is-self">Myself</b>@endif</small>
                <div class="ordp-who">
                    @if($opName !== '')<strong>{{ $opName }}</strong>@endif
                    @if($opEmail !== '')<span>{{ $opEmail }}</span>@endif
                    @if($opPhone !== '')<span>{{ $opPhone }}</span>@endif
                </div>
            </div>
        @endif
    </section>
@endif
