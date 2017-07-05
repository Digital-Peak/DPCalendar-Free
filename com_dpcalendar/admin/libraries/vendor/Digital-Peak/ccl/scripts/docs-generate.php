<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPDocsMD\Console\CLI;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$cli = new CLI();
$cli->setAutoExit(false);

$paths = ['BasicElements' => 'Basic', 'ComponentElements' => 'Component', 'ExtensionElements' => 'Extension'];

foreach ($paths as $docsName => $path) {
	echo PHP_EOL . '### Starting to generate ' . $docsName . PHP_EOL;

	$out  = new BufferedOutput();
	$code = $cli->run(
		new ArrayInput(['command' => 'generate', 'class' => $root . '/src/CCL/Content/Element/' . $path]),
		$out
	);
	file_put_contents($root . '/docs/Content/' . $docsName . '.md', $out->fetch());

	echo '### Finished to generate ' . $docsName . PHP_EOL;
}
