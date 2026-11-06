<?php
// DocuFlow Cloud — tenant subscription & billing: current plan, usage,
// upgrade/downgrade (creates a PENDING payment + invoice — activation only
// happens after verified payment), renew, cancel, coupons, invoice list.
declare(strict_types=1);

final class SubscriptionController
{
    private array $user;
    private int $tenantId;

    public function __construct()
    {
        $this->user = AuthMiddleware::tenant();
        $this->tenantId = (int) $this->user['tenant_id'];
    }

    public function index(): void
    {
        $sub = Tenant::currentSubscription($this->tenantId);
        $plans = Database::all('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price_monthly');
        $invoices = Database::all('SELECT * FROM invoices WHERE tenant_id = ? ORDER BY id DESC LIMIT 20', [$this->tenantId]);
        $payments = Database::all('SELECT * FROM payments WHERE tenant_id = ? ORDER BY id DESC LIMIT 20', [$this->tenantId]);
        $usage = [
            'storage' => Tenant::storageUsed($this->tenantId),
            'users' => (int) Database::value('SELECT COUNT(*) FROM users WHERE tenant_id = ?', [$this->tenantId]),
            'teams' => (int) Database::value('SELECT COUNT(*) FROM teams WHERE tenant_id = ?', [$this->tenantId]),
            'documents' => Document::countForTenant($this->tenantId),
        ];
        $effective = $sub ? SubscriptionService::effectiveStatus($sub) : 'none';
        view('tenant/subscription', [
            'user' => $this->user, 'tenant' => auth_tenant(), 'sub' => $sub, 'effective' => $effective,
            'plans' => $plans, 'invoices' => $invoices, 'payments' => $payments, 'usage' => $usage,
            'unread' => 0, 'notifs' => [],
        ]);
    }

    /** Free plan activates instantly; paid plans create a pending payment. */
    public function changePlan(): void
    {
        require_csrf();
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $cycle = (string) ($_POST['billing_cycle'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly';
        $plan = Database::one('SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1', [$planId]);
        if (!$plan) { flash('error', 'Plan not found.'); redirect('app/subscription'); }

        $coupon = null; $discount = 0.0;
        $code = trim((string) ($_POST['coupon'] ?? ''));
        if ($code !== '') {
            $coupon = SubscriptionService::validCoupon($code);
            if (!$coupon) { flash('error', 'Coupon code is invalid or expired.'); redirect('app/subscription'); }
        }
        [$amount, $discount] = SubscriptionService::priceWithCoupon($plan, $cycle, $coupon);

        if ($amount <= 0) {
            SubscriptionService::activate($this->tenantId, $planId, $cycle);
            Database::run('INSERT INTO subscription_usage (tenant_id, metric, used, recorded_at)
                           VALUES (?, "plan_change", 1, NOW())', [$this->tenantId]);
            activity_log('subscription.changed', 'Plan changed to ' . $plan['name'] . ' (' . $cycle . ')', $this->tenantId);
            flash('success', 'You are now on the ' . $plan['name'] . ' plan.');
            redirect('app/subscription');
        }

        $invoiceId = PaymentService::createInvoiceAndPendingPayment(
            $this->tenantId, $planId, $cycle, $amount, $discount, $coupon ? (int) $coupon['id'] : null);
        activity_log('subscription.payment_pending', 'Invoice #' . $invoiceId . ' created for ' . $plan['name'], $this->tenantId);
        flash('success', 'Invoice created and payment recorded as PENDING. Your plan activates as soon as the payment is verified by our team — unverified payments are never marked successful.');
        redirect('app/subscription');
    }

    public function cancel(): void
    {
        require_csrf();
        $sub = Tenant::currentSubscription($this->tenantId);
        if ($sub) {
            SubscriptionService::cancel((int) $sub['id'], $this->tenantId);
            activity_log('subscription.cancelled', 'Subscription cancelled', $this->tenantId);
            flash('success', 'Subscription cancelled. Access continues until the end of the current period.');
        }
        redirect('app/subscription');
    }

    public function renew(): void
    {
        require_csrf();
        $sub = Tenant::currentSubscription($this->tenantId);
        if (!$sub) { redirect('app/subscription'); }
        $plan = Database::one('SELECT * FROM subscription_plans WHERE id = ?', [(int) $sub['plan_id']]);
        [$amount] = SubscriptionService::priceWithCoupon($plan, (string) $sub['billing_cycle'], null);
        if ($amount <= 0) {
            SubscriptionService::renew($sub);
            flash('success', 'Subscription renewed.');
        } else {
            PaymentService::createInvoiceAndPendingPayment($this->tenantId, (int) $plan['id'], (string) $sub['billing_cycle'], $amount, 0.0, null);
            flash('success', 'Renewal invoice created — the renewal completes once payment is verified.');
        }
        redirect('app/subscription');
    }

    public function invoicePdf(int $id): void
    {
        $invoice = Database::one('SELECT i.*, t.name AS tenant_name, t.email AS tenant_email
                                  FROM invoices i JOIN tenants t ON t.id = i.tenant_id
                                  WHERE i.id = ? AND i.tenant_id = ?', [$id, $this->tenantId]);
        if (!$invoice) { http_response_code(404); exit('Invoice not found.'); }
        // Printable invoice view (browser "Save as PDF" produces the PDF report).
        view('tenant/invoice', ['invoice' => $invoice, 'tenant' => auth_tenant(), 'user' => $this->user]);
    }
}
