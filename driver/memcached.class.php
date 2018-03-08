<?php
class MetingCache implements MetingCacheI
{
    private $memcached = null;
    public function __construct($option)
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer($option['host'], $option['port']);
        assert($this->memcached->getVersion()!==false);
    }
    public function install()
    {
    }
    public function set($key, $value, $expire = 86400)
    {
        return $this->memcached->set($key, $value, $expire);
    }
    public function get($key)
    {
        return $this->memcached->get($key);
    }
    public function flush()
    {
        return $this->memcached->flush();
    }
    public function check()
    {
        $number = uniqid();
        $this->set('check', $number, 60);
        $cache = $this->get('check');
        if ($number != $cache) {
            throw new Exception('Cache Test Fall!');
        }
    }
}
