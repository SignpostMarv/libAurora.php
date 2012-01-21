<?php
date_default_timezone_set('Europe/London');
echo 'Building full phar...',"\n";
$phar = new Phar('../build/Aurora-Sim.php.phar');
$phar->buildFromDirectory('../', '/\.php$/');
$phar->compress(Phar::GZ, '.php.phar.gz');
?>