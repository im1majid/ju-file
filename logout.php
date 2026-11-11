<?php
/** تسجيل الخروج (POST فقط مع CSRF حتى لا يُستغل برابط خارجي). */
require __DIR__ . '/includes/init.php';

if (is_post()) {
    verify_csrf();
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
    flash('success', 'تم تسجيل الخروج.');
}
redirect('index.php');
