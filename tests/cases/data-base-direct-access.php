<?php
declare(strict_types=1);

use FooPlugins\FooConvert\Tests\Support\Assertions;

require_once dirname( __DIR__ ) . '/support/Assertions.php';

$base_file = dirname( __DIR__, 2 ) . '/includes/Data/Base.php';
$command   = escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg(
    'require ' . var_export( $base_file, true ) . '; echo "loaded";'
);

exec( $command, $output, $exit_code );

Assertions::same( 0, $exit_code, 'Direct access protection should exit cleanly.' );
Assertions::same( array(), $output, 'Direct access should stop before the data base class loads.' );

echo "Data Base direct-access guard test passed.\n";