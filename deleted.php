<?php

$files = [
// From main to make new upgrade anker point
'/administrator/components/com_dpcalendar/sql/updates',

// From main to remove iframe resizer vendor library
'/media/com_dpcalendar/js/vendor/auto-console-group',
'/media/com_dpcalendar/js/vendor/iframe-resizer',
];

foreach ($files as $file) {
	$fullPath = JPATH_ROOT . $file;

	if (empty($file) || !file_exists($fullPath)) {
		continue;
	}

	if (is_file($fullPath)) {
		unlink($fullPath);
		continue;
	}

	try {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
			$todo($fileinfo->getRealPath());
		}

		rmdir($fullPath);
	} catch (Exception $e) {
	}
}
