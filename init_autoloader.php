<?php

// ZCore Framework - Loader
$zcorePath = false;

if (is_dir(__DIR__ . '/library/ZCore')) {
    $zcorePath = __DIR__ . '/library/ZCore';
} elseif (getenv('ZCORE_PATH')) {      // Support for ZCORE_PATH environment variable or git submodule
    $zcorePath = getenv('ZCORE_PATH');
} elseif (get_cfg_var('zcore_path')) { // Support for protocor_path directive value
    $zcorePath = get_cfg_var('zcore_path');
}

if ($zcorePath) {
    if (isset($loader)) {
        $loader->add('ZCore', $zcorePath);
    } else {
        Zend\Loader\AutoloaderFactory::factory(array(
            'Zend\Loader\StandardAutoloader' => array(
                Zend\Loader\StandardAutoloader::LOAD_NS => array(
                    'ZCore' => $zcorePath
                )
            )
        ));
    }
} else {
    throw new RuntimeException('Unable to load Zend Core, define a ZCORE_PATH environment variable.');
}