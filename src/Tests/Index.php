<?php

$pUnitPath = __DIR__ . '/../../ext/pUnit/';

set_include_path(get_include_path() . PATH_SEPARATOR . $pUnitPath . 'src/external/mockery/library/');

require_once($pUnitPath.'src/autoloader.php');
require_once('Mockery/Loader.php');

$loader = new \Mockery\Loader();
$loader->register();

\Mockery::getConfiguration()->allowMockingMethodsUnnecessarily(false);

use pUnit\Assert as Assert;

$pattern = '/.php$/i';
$classExport = function($fileName) {
    return substr($fileName,0 ,-4);
    
};
$provider = new pUnit\FolderTestProvider(__DIR__, $pattern, $classExport);

$runner = new pUnit\TestRunner();
$formatter = new pUnit\TestResultFormatters\CompositeTestResultFormatter(array(
    new pUnit\TestResultFormatters\GrowlTestResultFormatter('127.0.0.1','password'),
    new pUnit\TestResultFormatters\ConsoleTestResultFormatter(false)
    ));
$runner->SetOutput($formatter);
$runner->SetTest($provider);

$runner->Run();
