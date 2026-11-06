<?php
// DocuFlow Cloud — CLI database installer.
// Usage:  php database/install.php
// Reads DB credentials from .env (or defaults), imports database.sql,
// and writes the demo seed files into storage/private.
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { exit("Run from CLI only.\n"); }
$root = dirname(__DIR__);
require $root . '/config/app.php'; // defines env()

$host = env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 3306);
$name = env('DB_NAME', 'docuflow_cloud');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');

$sql = file_get_contents(__DIR__ . '/database.sql');
if ($sql === false) { exit("database.sql not found.\n"); }

$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
// Split on statement terminators at line ends (schema contains no procedures).
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
$count = 0;
foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        // strip leading comment lines
        $stmt = preg_replace('/^(--[^\n]*\n)+/', '', $stmt) ?? '';
        if (trim($stmt) === '') continue;
    }
    $pdo->exec($stmt);
    $count++;
}
echo "Imported $count statements into '$name'.\n";

$storage = $root . '/storage/private';
if (!is_dir($storage)) mkdir($storage, 0770, true);
$seeds = [
    'seed_contract.txt' => "SUPPLIER CONTRACT 2026\n======================\nMaster services agreement between Demo Company Inc and Helios Legal.\nSigned copy stored in DocuFlow Cloud (demo seed file).\nTerm: 12 months. Governing terms as per attached schedules.\n",
    'seed_policy.txt'   => "REMOTE WORK POLICY\n==================\nDemo Company Inc remote work policy, effective 2026.\nEmployees may work remotely up to 3 days per week with manager approval.\nCore collaboration hours: 10:00-15:00. (Demo seed file.)\n",
    'seed_report.txt'   => "Q3 SALES REPORT\n===============\nQuarterly sales summary (demo seed data).\nPipeline grew 18% quarter over quarter. Enterprise segment led growth.\nPrepared for internal review in DocuFlow Cloud.\n",
];
foreach ($seeds as $file => $content) {
    file_put_contents($storage . '/' . $file, $content);
}
echo "Seed files written to storage/private.\n";
echo "Done. Start the app with: php -S localhost:8000 -t public public/router.php\n";
