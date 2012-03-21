<?php
date_default_timezone_set('Europe/London');
echo 'Building full phar...',"\n";
$phar = new Phar('../build/libAurora.php.phar');
$phar->buildFromDirectory('../', '/\.(php|txt|md)$/');
$phar->compress(Phar::GZ, '.php.phar.gz');
?>
