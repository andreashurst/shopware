<?php

$EM_CONF['t3jet_mockup'] = [
	'title' => 'T3jet Mockup',
	'description' => 'T3jet Version 20231204-201500',
	'category' => 'templates',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'author' => 'T3jet Team',
	'autoload' => [
		'psr-4' => [
			'T3jet\\T3jetMockup\\' => 'Classes'
		],
	]
];
