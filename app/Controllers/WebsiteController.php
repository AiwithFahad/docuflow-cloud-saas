<?php
// DocuFlow Cloud — public marketing website
declare(strict_types=1);

final class WebsiteController
{
    private function plans(): array
    {
        return Database::all('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price_monthly');
    }

    /** Platform statistics computed from real records (never hardcoded). */
    private function platformStats(): array
    {
        try {
            return [
                'companies' => (int) Database::value('SELECT COUNT(*) FROM tenants WHERE status != "deleted"'),
                'documents' => (int) Database::value('SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL'),
                'users'     => (int) Database::value('SELECT COUNT(*) FROM users'),
                'storage'   => human_bytes(Database::value('SELECT COALESCE(SUM(size_bytes),0) FROM documents WHERE deleted_at IS NULL')),
            ];
        } catch (Throwable) {
            return ['companies' => 0, 'documents' => 0, 'users' => 0, 'storage' => '0 B'];
        }
    }

    public function home(): void
    {
        view('website/home', ['plans' => $this->plans(), 'stats' => $this->platformStats()]);
    }

    public function page(string $page): void
    {
        $allowed = ['about', 'features', 'solutions', 'industries', 'integrations', 'security', 'blog', 'blog-details', 'contact', 'privacy', 'terms'];
        if (!in_array($page, $allowed, true)) {
            http_response_code(404);
            view('errors/404');
            return;
        }
        view('website/' . $page, ['plans' => $this->plans(), 'stats' => $this->platformStats()]);
    }

    public function pricing(): void
    {
        view('website/pricing', ['plans' => $this->plans()]);
    }

    public function contactSubmit(): void
    {
        require_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $subject = trim((string) ($_POST['subject'] ?? 'Sales inquiry'));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            stash_old($_POST);
            flash('error', 'Please complete name, a valid email, and your message.');
            redirect('contact');
        }
        Database::run('INSERT INTO support_tickets (tenant_id, user_id, name, email, subject, message, status, priority, created_at, updated_at)
                       VALUES (NULL, NULL, ?, ?, ?, ?, "open", "normal", NOW(), NOW())',
            [$name, $email, $subject, $message]);
        flash('success', 'Thanks! Your message is with our team — we reply within one business day.');
        redirect('contact');
    }

    public function newsletter(): void
    {
        require_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Database::run('INSERT IGNORE INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())', [$email]);
            flash('success', 'Subscribed — welcome to the DocuFlow newsletter.');
        } else {
            flash('error', 'Please enter a valid email address.');
        }
        redirect('');
    }
}
