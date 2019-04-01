<?php

/**
 * An example of a project-specific implementation.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
 * from /path/to/project/src/Baz/Qux.php:
 *
 *      new \Foo\Bar\Baz\Qux;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    $auto_load_class = array(
        'OSS\\WP\\' => '/src/',
        'OSS\\' => '/vendor/aliyun-oss-php-sdk-2.3.0/src/OSS/'
    );
    $auto_load_files = array(
        'Puc_v4_Factory' => '/vendor/plugin-update-checker-4.5.1/plugin-update-checker.php'
    );

    foreach ($auto_load_class as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) == 0) {
            $relative_class = substr($class, $len);
            $file = ALIYUN_OSS_PATH . $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
            if (file_exists($file))
                require $file;
        }
    }

    foreach ($auto_load_files as $target => $file) {
        if ($class == $target) {
            require(ALIYUN_OSS_PATH . $file);
        }
    }
});
