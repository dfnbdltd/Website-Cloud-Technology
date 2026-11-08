            @php
                $activeNameservers = array_values(array_filter(array_map('trim', (array) $nameservers), function ($value) {
                    return $value !== '';
                }));
                $savedNameservers = $activeNameservers ?: ['ns1.example.com', 'ns2.example.com'];
                $draftNameservers = $savedNameservers;
                $draftNameserverInput = old('nameservers');
                if (is_string($draftNameserverInput) && trim($draftNameserverInput) !== '') {
                    $draftNameserverRows = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $draftNameserverInput)), function ($value) {
                        return $value !== '';
                    }));
                    if ($draftNameserverRows) {
                        $draftNameservers = $draftNameserverRows;
                    }
                }
            @endphp

            <section class="store-detail-panel store-ns-panel" data-domain="{{ $item->domain }}">
                <header class="store-ns-head">
                    <span class="store-ns-badge" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.6"/><path d="M3.4 12h17.2"/><path d="M12 3.4c2.6 2.5 4 5.4 4 8.6s-1.4 6.1-4 8.6c-2.6-2.5-4-5.4-4-8.6s1.4-6.1 4-8.6z"/></svg>
                    </span>
                    <div class="store-ns-heading">
                        <span class="domain-kicker">DNS MANAGEMENT</span>
                        <h2>Name servers &amp; DNS</h2>
                        <p class="store-ns-subtitle">Choose where this domain points on the internet.</p>
                    </div>
                    @if($activeNameservers)
                        <span class="store-ns-state is-live"><i aria-hidden="true"></i>{{ count($activeNameservers) }} active</span>
                    @else
                        <span class="store-ns-state is-empty"><i aria-hidden="true"></i>Not configured</span>
                    @endif
                </header>

                <div class="store-ns-alert" role="status" aria-live="polite" hidden></div>

                <div class="store-ns-records">
                    @forelse($activeNameservers as $index => $nameserver)
                        <div class="store-ns-record">
                            <small>NS{{ $index + 1 }}</small>
                            <span>{{ $nameserver }}</span>
                            <svg class="store-ns-record-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="1.5" opacity=".4"/><path d="m8.3 12.4 2.4 2.4 4.9-5.2"/></svg>
                        </div>
                    @empty
                        <div class="store-ns-blank">
                            <span class="store-ns-blank-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.2" y="4.4" width="17.6" height="5.8" rx="1.9"/><rect x="3.2" y="13.8" width="17.6" height="5.8" rx="1.9"/><path d="M6.8 7.3h.01"/><path d="M6.8 16.7h.01"/></svg>
                            </span>
                            <div>
                                <strong>No name servers configured yet</strong>
                                <p>Add the two name servers your hosting provider gave you so {{ $item->domain }} can point to your website and email.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="store-ns-actions">
                    <details class="store-ns-edit @if($errors->any() || old('nameservers')) is-editing @endif" @if($errors->any() || old('nameservers')) open @endif>
                        <summary>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16.6 3.9a2.1 2.1 0 0 1 3 3L8 18.5l-4.2 1.1 1.1-4.2z"/><path d="M5.5 21.4h10"/></svg>
                            <span>Edit name servers</span>
                        </summary>
                        <div class="store-ns-modal">
                            <div class="store-ns-modal-backdrop"></div>
                            <div class="store-ns-modal-card" role="dialog" aria-modal="true" aria-labelledby="ns-modal-title">
                                <button type="button" class="store-ns-modal-close" aria-label="Close">×</button>
                                <div class="store-ns-modal-head">
                                    <span class="store-ns-modal-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.6"/><path d="M3.4 12h17.2"/><path d="M12 3.4c2.6 2.5 4 5.4 4 8.6s-1.4 6.1-4 8.6c-2.6-2.5-4-5.4-4-8.6s1.4-6.1 4-8.6z"/></svg>
                                    </span>
                                    <div>
                                        <h3 id="ns-modal-title">Name servers &amp; DNS</h3>
                                        <p>For <strong>{{ $item->domain }}</strong></p>
                                    </div>
                                </div>
                                <div class="store-ns-modal-main">
                                    <div class="store-ns-feedback" role="status" aria-live="polite" hidden></div>
                                    <div class="store-ns-view">
                                        @if($activeNameservers)
                                            <p class="store-ns-view-label">Currently active</p>
                                            @foreach($activeNameservers as $index => $nameserver)
                                                <div class="store-ns-current-row">
                                                    <small>NS{{ $index + 1 }}</small>
                                                    <span>{{ $nameserver }}</span>
                                                    <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index + 1 }}">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16.6 3.9a2.1 2.1 0 0 1 3 3L8 18.5l-4.2 1.1 1.1-4.2z"/><path d="M5.5 21.4h10"/></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="store-ns-view-empty">No name servers are configured yet. Add them below to connect this domain to your hosting.</p>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('mystore.domains.nameservers', $item->id) }}" class="store-nameserver-form">
                                        @csrf
                                        <textarea name="nameservers" class="store-ns-submit-value" hidden>{{ old('nameservers', implode("\n", $savedNameservers)) }}</textarea>
                                        <p class="store-ns-recommendation">We recommend keeping at least two name servers.</p>
                                        <div class="store-ns-input-list">
                                            @foreach($draftNameservers as $index => $nameserver)
                                                <div class="store-ns-input-row">
                                                    <small>NS{{ $index + 1 }}</small>
                                                    <input type="text" value="{{ $nameserver }}" placeholder="ns{{ $index + 1 }}.example.com" aria-label="Name server {{ $index + 1 }}" required>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="store-ns-add">+ Add name server</button>
                                        <div class="store-ns-form-actions">
                                            <button class="store-primary store-ns-submit" type="submit" disabled>Update name servers</button>
                                            <button type="button" class="store-ns-cancel">Cancel changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </details>

                    <div class="store-child-ns-option">
                        <button type="button" class="store-child-ns-trigger" aria-haspopup="dialog" aria-controls="child-ns-modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5.6v12.8"/><path d="M5.6 12h12.8"/></svg>
                            <span>Child name servers</span>
                        </button>
                        <button type="button" class="store-child-ns-details-trigger" aria-haspopup="dialog" aria-controls="child-ns-details-modal" aria-label="What are child name servers?">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
                        </button>
                    </div>

                    <button type="button" class="store-ns-details-trigger" aria-haspopup="dialog" aria-controls="nameserver-help-modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
                        <span>How name servers work</span>
                    </button>
                </div>

                <div id="child-ns-modal" class="store-child-ns-modal" hidden>
                    <div class="store-child-ns-backdrop" data-child-ns-close></div>
                    <div class="store-child-ns-card" role="dialog" aria-modal="true" aria-labelledby="child-ns-title">
                        <button type="button" class="store-child-ns-close" aria-label="Close child name servers">×</button>
                        <div class="store-child-ns-card-head">
                            <span class="store-child-ns-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.2" y="4.4" width="17.6" height="5.8" rx="1.9"/><rect x="3.2" y="13.8" width="17.6" height="5.8" rx="1.9"/><path d="M6.8 7.3h.01"/><path d="M6.8 16.7h.01"/></svg>
                            </span>
                            <div>
                                <span class="domain-kicker">DOMAIN MANAGEMENT</span>
                                <h3 id="child-ns-title">Manage child name servers</h3>
                                <p>For <strong>{{ $item->domain }}</strong></p>
                            </div>
                        </div>
                        <div class="store-child-ns-form">
                            <h4>Add child name servers</h4>
                            <div class="store-child-ns-labels"><span>Host name</span><span>IP address</span></div>
                            <div class="store-child-ns-rows">
                                <div class="store-child-ns-row">
                                    <input type="text" placeholder="Ex. ns1.{{ $item->domain }}" aria-label="Child nameserver host name">
                                    <input type="text" placeholder="IP address" aria-label="Child nameserver IP address">
                                    <button type="button" class="store-child-ns-remove" aria-label="Remove child nameserver" hidden>×</button>
                                </div>
                            </div>
                            <button type="button" class="store-child-ns-add">+ Add more</button>
                            <div class="store-child-ns-actions">
                                <button type="button" class="store-primary store-child-ns-update" disabled>Update child name servers</button>
                                <button type="button" class="store-child-ns-cancel">Cancel</button>
                            </div>
                            <p class="store-child-ns-note">Child name servers are currently available as a preview. Contact support when you are ready to register them.</p>
                        </div>
                    </div>
                </div>

                <div id="child-ns-details-modal" class="store-child-ns-modal" hidden>
                    <div class="store-child-ns-backdrop" data-child-ns-close></div>
                    <div class="store-child-ns-details-card" role="dialog" aria-modal="true" aria-labelledby="child-ns-details-title">
                        <button type="button" class="store-child-ns-close" aria-label="Close child name server details">×</button>
                        <div class="store-child-ns-details-body">
                            <span class="store-child-ns-details-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
                            </span>
                            <h3 id="child-ns-details-title">Child name servers</h3>
                            <p>Your child name servers can be:</p>
                            <div class="store-child-ns-example"><span>ns1.{{ $item->domain }}</span><span>ns2.{{ $item->domain }}</span></div>
                            <p>Create child name servers when you want to use these as the name servers for your domains.</p>
                            <p>Once created, child name servers get registered with the registry under this domain name.</p>
                            <p>When you associate these name servers with your domains, these details will be visible in the WHOIS results.</p>
                            <a href="https://www.icann.org/resources/pages/glossary-2018-02-13-en#n" target="_blank" rel="noopener">Learn more about child name servers</a>
                        </div>
                    </div>
                </div>

                <div id="nameserver-help-modal" class="store-ns-help" hidden>
                    <div class="store-ns-help-backdrop" data-ns-help-close></div>
                    <div class="store-ns-help-card" role="dialog" aria-modal="true" aria-label="How to configure name servers">
                        <button type="button" class="store-ns-help-close" aria-label="Close">×</button>
                        <div class="store-ns-help-head">
                            <span class="store-ns-help-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.6 2.4 4 5.6 4 9s-1.4 6.6-4 9c-2.6-2.4-4-5.6-4-9s1.4-6.6 4-9z"/></svg>
                            </span>
                            <div>
                                <h3>Use the DNS service to point this domain to a hosting or email package.</h3>
                            </div>
                        </div>
                        <div class="store-ns-help-body">
                            <p>To do this,</p>
                            <ul>
                                <li>Specify at least 2 name servers provided with your hosting package here. Your domain will start resolving to your hosting in 24-72 hours.</li>
                            </ul>
                            <p class="store-ns-help-or">OR</p>
                            <ul>
                                <li>Use our default name servers and add the DNS records provided with your hosting package by clicking on 'Manage DNS Records'. Your domain will start resolving to your hosting in 4-6 hours.</li>
                            </ul>
                            <div class="store-ns-help-defaults">
                                <span>Our default name servers are:</span>
                                <ul>
                                    @forelse($activeNameservers as $nameserver)<li>{{ $nameserver }}</li>@empty<li>Not configured</li>@endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
