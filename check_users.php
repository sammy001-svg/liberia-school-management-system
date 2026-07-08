<?php
$pdo = new PDO("mysql:host=localhost;dbname=school_mgmt", "root", "");
$users = $pdo->query("SELECT u.id, u.email, u.password_hash, r.name as role, u.status 
                      FROM users u 
                      JOIN roles r ON u.role_id = r.id")->fetchAll(PDO::FETCH_ASSOC);

echo "--- User Records ---\n";
foreach ($users as $u) {
    echo "ID: {$u['id']} | Email: {$u['email']} | Role: {$u['role']} | Status: {$u['status']}\n";
    // Check if 'password123' matches
    if (password_verify('password123', $u['password_hash'])) {
        echo "  [OK] password123 matches\n";
    } elseif (password_verify('admin123', $u['password_hash'])) {
        echo "  [OK] admin123 matches\n";
    } else {
        echo "  [!!] PASSWORD DOES NOT MATCH password123 OR admin123\n";
    }
}
