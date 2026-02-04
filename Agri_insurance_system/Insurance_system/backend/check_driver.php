<?php
if (function_exists('sqlsrv_connect')) {
    echo "✅ SQLSRV driver is ACTIVE.";
} else {
    echo "❌ SQLSRV driver is NOT loading. Check your php.ini and XAMPP version.";
}
phpinfo();
?>