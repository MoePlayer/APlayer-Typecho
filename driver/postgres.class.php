<?php
class MetingCache implements MetingCacheI
{
    private $db = null;
    public function __construct($option)
    {
        $this->db = Typecho_Db::get();
        $dbname = $this->db->getPrefix() . 'metingcache';
        $this->install();
        $this->db->query($this->db->delete('table.metingcache')->where('time <= ?', time()));
    }
    public function install()
    {
        $sql = '
CREATE TABLE IF NOT EXISTS "' . $this->db->getPrefix() . 'metingcache' . '" (
  "key" varchar(32) NOT NULL PRIMARY KEY,
  "data" text,
  "time" NUMERIC(20) DEFAULT NULL
);';
        $this->db->query($sql);
    }
    public function set($key, $value, $expire = 86400)
    {
        $this->db->query($this->db->insert('table.metingcache')->rows(array(
            'key' => md5($key),
            'data' => $value,
            'time' => time() + $expire
        )));
    }
    public function get($key)
    {
        $rs = $this->db->fetchRow($this->db->select('data')->from('table.metingcache')->where('key = ?', md5($key)));
        if (count($rs) == 0) {
            return false;
        } else {
            return $rs['data'];
        }
    }
    public function flush()
    {
        return $this->db->query($this->db->delete('table.metingcache'));
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
