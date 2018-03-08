<?php
interface MetingCacheI
{
    public function install();
    public function set($key, $value, $expire = 86400);
    public function get($key);
    public function flush();
    public function check();
}
