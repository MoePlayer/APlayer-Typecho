<?php
class MetingCache implements MetingCacheI
{
    private $db = null;
    public function __construct($option)
    {
        $this->db = Typecho_Db::get();
        $dbname = $this->db->getPrefix() . 'metingcache';
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name= '" . $dbname . "'";
        if (count($this->db->fetchAll($sql)) == 0) {
            $this->install();
        } else {
            $this->db->query($this->db->delete('table.metingcache')->where('time <= ?', time()));
        }
    }
    public function install()
    {
        $sql = '
DROP TABLE IF EXISTS `%dbname%`;
CREATE TABLE `%dbname%` (
  `key` varchar(32) NOT NULL PRIMARY KEY,
  `data` text,
  `time` int(20) DEFAULT NULL
);';
        $dbname = $this->db->getPrefix() . 'metingcache';
        $search = array('%dbname%');
        $replace = array($dbname);

        $sql = str_replace($search, $replace, $sql);
        $sqls = explode(';', $sql);
        foreach ($sqls as $sql) {
            $this->db->query($sql);
        }
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
