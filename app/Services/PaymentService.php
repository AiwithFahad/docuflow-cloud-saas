<?php
// DocuFlow Cloud — payment recording with a gateway abstraction.
// RULE: a payment is only ever marked "paid" after verified confirmation.
// Manual recordings start as "pending" until a Super Admin verifies them.
declare(strict_types=1);

interface PaymentGatewayInterface
{
    /** @return array{verified: bool, reference: ?string} */
    public function verify(string $reference, float $amount): array;
}

/** Manual / offline payments: never auto-verified. */
final class ManualGateway implements PaymentGatewayInterface
{
    public function verify(string $reference, float $amount): array
    {
        return ['verified' => false, 'reference' => $reference];
    }
}

/** Placeholder adapter: wire a real provider (Stripe, local PSP…) here. */
final class ConfiguredGateway implements PaymentGatewayInterface
{
    public function verify(string $reference, float $amount): array
    {
        // Intentionally not verified until a real provider SDK + webhook
        // confirmation is implemented for the deployment environment.
        return ['verified' => false, 'reference' => $reference];
    }
}

final class PaymentService
{
    public static function gateway(): PaymentGatewayInterface
    {
        return env('PAYMENT_GATEWAY') ? new ConfiguredGateway() : new ManualGateway();
    }

    /**
     * Create an invoice + a PENDING payment for a subscription purchase.
     * Returns the invoice id. The subscription is activated by
     * confirmPayment() only — never here.
     */
    public static function createInvoiceAndPendingPayment(
        int $tenantId, int $planId, string $cycle, float $amount, float $discount, ?int $couponId
    ): int {
        $plan = Database::one('SELECT * FROM subscription_plans WHERE id = ?', [$planId]);
        $number = 'INV-' . date('Y') . '-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        $invoiceId = Database::insert(
            'INSERT INTO invoices (tenant_id, subscription_id, number, description, amount, discount, total, status, meta_plan_id, meta_cycle, created_at)
             VALUES (?,NULL,?,?,?,?,?,"unpaid",?,?,NOW())',
            [$tenantId, $number, 'DocuFlow Cloud — ' . $plan['name'] . ' plan (' . $cycle . ')',
             $amount + $discount, $discount, $amount, $planId, $cycle]
        );
        Database::run(
            'INSERT INTO payments (tenant_id, invoice_id, amount, currency, method, status, reference, created_at)
             VALUES (?,?,?,"USD","manual","pending",?,NOW())',
            [$tenantId, $invoiceId, $amount, 'MAN-' . strtoupper(bin2hex(random_bytes(4)))]
        );
        if ($couponId) {
            Database::run('UPDATE coupons SET uses = uses + 1 WHERE id = ?', [$couponId]);
        }
        return $invoiceId;
    }

    /**
     * Confirm a payment ONLY with verified confirmation (gateway webhook or
     * Super Admin manual verification). Activates the subscription and marks
     * the invoice paid.
     */
    public static function confirmPayment(int $paymentId, string $verifiedBy): bool
    {
        $payment = Database::one('SELECT * FROM payments WHERE id = ?', [$paymentId]);
        if (!$payment || $payment['status'] === 'paid') return false;

        if ($verifiedBy === 'gateway') {
            $result = self::gateway()->verify((string) $payment['reference'], (float) $payment['amount']);
            if (!$result['verified']) return false; // never mark paid without verification
        }
        // $verifiedBy === 'super_admin' means a human verified the transfer.

        Database::run('UPDATE payments SET status = "paid", paid_at = NOW() WHERE id = ?', [$paymentId]);
        if (!empty($payment['invoice_id'])) {
            Database::run('UPDATE invoices SET status = "paid", paid_at = NOW() WHERE id = ?', [(int) $payment['invoice_id']]);
            $invoice = Database::one('SELECT * FROM invoices WHERE id = ?', [(int) $payment['invoice_id']]);
            if ($invoice && !empty($invoice['meta_plan_id'])) {
                SubscriptionService::activate((int) $payment['tenant_id'], (int) $invoice['meta_plan_id'], (string) $invoice['meta_cycle']);
                Database::run('UPDATE invoices SET subscription_id = (SELECT id FROM subscriptions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1) WHERE id = ?',
                    [(int) $payment['tenant_id'], (int) $invoice['id']]);
            }
        }
        activity_log('payment.confirmed', 'Payment #' . $paymentId . ' verified via ' . $verifiedBy, (int) $payment['tenant_id']);
        return true;
    }
}
