<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrderItem extends Model
{
    /** Item status of a registration ResellerClub has completed. */
    const STATUS_REGISTERED = 'registered';
    /** Item status of a transfer-in ResellerClub has completed. */
    const STATUS_TRANSFERRED = 'transferred';

    protected $fillable = ['domain_order_id', 'domain', 'tld', 'years', 'price', 'renewal_price', 'purchase_price', 'registration_status', 'order_type', 'transfer_auth_code', 'reseller_order_id', 'expires_at', 'transfer_completed_at', 'org_consent_choice', 'org_consent_responded_at'];
    protected $casts = ['price' => 'float', 'renewal_price' => 'float', 'purchase_price' => 'float', 'expires_at' => 'datetime', 'transfer_completed_at' => 'datetime', 'org_consent_responded_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(DomainOrder::class, 'domain_order_id');
    }

    /**
     * The customer already answered the Organization Name Consent question
     * ("Yes, I agree" or "Cancel") and ResellerClub accepted it. The question is
     * asked once, so the consent box is not shown again after this.
     */
    public function hasAnsweredOrganizationConsent()
    {
        return $this->org_consent_responded_at !== null;
    }

    public function isTransfer()
    {
        return $this->order_type === 'transfer';
    }

    /**
     * A domain the customer can manage (nameservers, consent, activity):
     * a finished registration on an active order, or a finished transfer.
     * Registrations and transfers share every manage feature.
     *
     * A transfer is manageable as soon as ITS OWN item is transferred, even if
     * a sibling transfer on the same order is still in progress (the order only
     * turns active once every item is done).
     */
    public function scopeManageable($query)
    {
        return $query
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('order_type', 'registration')
                        ->where('registration_status', self::STATUS_REGISTERED)
                        ->whereHas('order', function ($order) {
                            $order->where('status', 'active');
                        });
                })->orWhere(function ($query) {
                    $query->where('order_type', 'transfer')
                        ->where('registration_status', self::STATUS_TRANSFERRED);
                });
            })
            ->whereHas('order', function ($order) {
                $order->where('payment_status', 'paid');
            });
    }
}
