<?php
/**
 * Pimcore MinifyBundle
 * Copyright (c) Die Leoparden e.K.
 */

namespace MinifyBundle\EventListener;

class CacheClearListener
{
    public function onClear()
    {
        $path_data = [];
        $path_data = glob(dirname(__DIR__) . '/Resources/public/css/*');
        if (is_array($path_data)) {
            foreach ($path_data as $data) {
                if (is_file($data)) {
                    unlink($data);
                }
            }
        }
        $path_data = [];
        $path_data = glob(dirname(__DIR__) . '/Resources/public/js/*');
        if (is_array($path_data)) {
            foreach ($path_data as $data) {
                if (is_file($data)) {
                    unlink($data);
                }
            }
        }
    }
}
