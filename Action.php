<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Meting_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function execute()
    {
    }

    public function action()
    {
        $this->on($this->request->is('do=update'))->update();
        $this->on($this->request->is('do=parse'))->shortcode();
        $this->on($this->request->isGet())->api();
    }

    private function check($server, $type, $id)
    {
        if (!in_array($server, array('netease','tencent','baidu','xiami','kugou'))) {
            return false;
        }
        if (!in_array($type, array('song','album','search','artist','playlist','lrc','url','pic'))) {
            return false;
        }
        if (empty($id)) {
            return false;
        }
        return true;
    }

    private function api()
    {
        // 参数检查
        $this->filterReferer();

        $server = $this->request->get('server');
        $type = $this->request->get('type');
        $id = $this->request->get('id');

        if (!$this->check($server, $type, $id)) {
            http_response_code(403);
            die();
        }

        // 加载 Meting 模块
        if (!extension_loaded('Meting')) {
            include_once 'include/Meting.php';
        }
        $api = new \Metowolf\Meting($server);
        $api->format(true);
        $cookie = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->cookie;
        if ($server == 'netease' && !empty($cookie)) {
            $api->cookie($cookie);
        }

        // 加载 Meting Cache 模块
        if (!extension_loaded('MetingCache')) {
            $cachetype = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->cachetype;
            if ($cachetype != 'none') {
                $cachehost = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->cachehost;
                $cacheport = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->cacheport;
                include_once 'driver/cache.interface.php';
                include_once 'driver/'.$cachetype.'.class.php';
                $this->cache = new MetingCache(array(
                    'host' => $cachehost,
                    'port' => $cacheport
                ));
            }
        }

        // auth 验证
        $EID = $server.$type.$id;
        $salt = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->salt;

        if (!empty($salt)) {
            $auth1 = md5($salt.$EID.$salt);
            $auth2 = $this->request->get('auth');
            if (strcmp($auth1, $auth2)) {
                http_response_code(403);
                die();
            }
        }

        // 歌词解析
        if ($type == 'lrc') {
            $data = $this->cacheRead($EID);
            if (empty($data)) {
                $data = $api->lyric($id);
                $this->cacheWrite($EID, $data, 86400);
            }
            $data = json_decode($data, true);
            header("Content-Type: application/javascript");
            if (!empty($data['tlyric'])) {
                echo $this->lrctran($data['lyric'], $data['tlyric']);
            } else {
                echo $data['lyric'];
            }
        }

        // 专辑图片解析
        if ($type == 'pic') {
            $data = $this->cacheRead($EID);
            if (empty($data)) {
                $data = $api->pic($id, 90);
                $this->cacheWrite($EID, $data, 86400);
            }
            $data = json_decode($data, true);
            $this->response->redirect($data['url']);
        }

        // 歌曲链接解析
        if ($type == 'url') {
            $data = $this->cacheRead($EID);
            if (empty($data)) {
                $rate = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->bitrate;
                $data = $api->url($id, $rate);
                $this->cacheWrite($EID, $data, 1200);
            }
            $data = json_decode($data, true);
            $url = $data['url'];

            if ($server == 'netease') {
                $url = str_replace('://m7c.', '://m7.', $url);
                $url = str_replace('://m8c.', '://m8.', $url);
                $url = str_replace('http://m8.', 'https://m9.', $url);
                $url = str_replace('http://m7.', 'https://m9.', $url);
                $url = str_replace('http://m10.', 'https://m10.', $url);
            }

            if ($server == 'xiami') {
                $url = str_replace('http://', 'https://', $url);
            }

            if ($server == 'baidu') {
                $url = str_replace('http://zhangmenshiting.qianqian.com', 'https://gss3.baidu.com/y0s1hSulBw92lNKgpU_Z2jR7b2w6buu', $url);
            }

            if (empty($url)) {
                $url = 'https://coding.meting.api.i-meto.com/empty.mp3';
                if ($server == 'netease') {
                    $url = 'https://music.163.com/song/media/outer/url?id='.$id.'.mp3';
                }
            }
            $this->response->redirect($url);
        }

        // 其它类别解析
        if (in_array($type, array('song','album','search','artist','playlist'))) {
            $data = $this->cacheRead($EID);
            if (empty($data)) {
                $data = $api->$type($id);
                $this->cacheWrite($EID, $data, 7200);
            }
            $data = json_decode($data, 1);
            $url = Typecho_Common::url('action/metingapi', Helper::options()->index);

            $music = array();
            foreach ($data as $vo) {
                $music[] = array(
                    'name'   => $vo['name'],
                    'artist' => implode(' / ', $vo['artist']),
                    'url'    => $url.'?server='.$vo['source'].'&type=url&id='.$vo['url_id'].'&auth='.md5($salt.$vo['source'].'url'.$vo['url_id'].$salt),
                    'cover'  => $url.'?server='.$vo['source'].'&type=pic&id='.$vo['pic_id'].'&auth='.md5($salt.$vo['source'].'pic'.$vo['pic_id'].$salt),
                    'lrc'    => $url.'?server='.$vo['source'].'&type=lrc&id='.$vo['lyric_id'].'&auth='.md5($salt.$vo['source'].'lrc'.$vo['lyric_id'].$salt),
                );
            }
            header("Content-Type: application/javascript");
            echo json_encode($music);
        }
    }

    private function shortcode()
    {
        $url = $this->request->get('data');
        $url = trim($url);
        if (empty($url)) {
            return;
        }
        $server = 'netease';
        $id = '';
        $type = '';
        if (strpos($url, '163.com') !== false) {
            $server = 'netease';
            if (preg_match('/playlist\?id=(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/toplist\?id=(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/album\?id=(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'album');
            } elseif (preg_match('/song\?id=(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'song');
            } elseif (preg_match('/artist\?id=(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'artist');
            }
        } elseif (strpos($url, 'qq.com') !== false) {
            $server = 'tencent';
            if (preg_match('/playsquare\/([^\.]*)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/playlist\/([^\.]*)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/album\/([^\.]*)/i', $url, $id)) {
                list($id, $type) = array($id[1],'album');
            } elseif (preg_match('/song\/([^\.]*)/i', $url, $id)) {
                list($id, $type) = array($id[1],'song');
            } elseif (preg_match('/singer\/([^\.]*)/i', $url, $id)) {
                list($id, $type) = array($id[1],'artist');
            }
        } elseif (strpos($url, 'xiami.com') !== false) {
            $server = 'xiami';
            if (preg_match('/collect\/(\w+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/album\/(\w+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'album');
            } elseif (preg_match('/[\/.]\w+\/[songdem]+\/(\w+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'song');
            } elseif (preg_match('/artist\/(\w+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'artist');
            }
            if (!preg_match('/^\d*$/i', $id, $t)) {
                $data = self::curl($url);
                preg_match('/'.$type.'\/(\d+)/i', $data, $id);
                $id = $id[1];
            }
        } elseif (strpos($url, 'kugou.com') !== false) {
            $server = 'kugou';
            if (preg_match('/special\/single\/(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/#hash\=(\w+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'song');
            } elseif (preg_match('/album\/[single\/]*(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'album');
            } elseif (preg_match('/singer\/[home\/]*(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'artist');
            }
        } elseif (strpos($url, 'baidu.com') !== false) {
            $server = 'baidu';
            if (preg_match('/songlist\/(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'playlist');
            } elseif (preg_match('/album\/(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'album');
            } elseif (preg_match('/song\/(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'song');
            } elseif (preg_match('/artist\/(\d+)/i', $url, $id)) {
                list($id, $type) = array($id[1],'artist');
            }
        } else {
            die("[Meting]\n[Music title=\"歌曲名\" author=\"歌手\" url=\"{$url}\" pic=\"图片文件URL\" lrc=\"歌词文件URL\"/]\n[/Meting]\n");
            return;
        }
        if (is_array($id)) {
            $id = '';
        }
        die("[Meting]\n[Music server=\"{$server}\" id=\"{$id}\" type=\"{$type}\"/]\n[/Meting]\n");

    }

    private function lrctrim($lyrics)
    {
        $result = "";
        $lyrics = explode("\n", $lyrics);
        $data = array();
        foreach ($lyrics as $key => $lyric) {
            preg_match('/\[(\d{2}):(\d{2}[\.:]?\d*)]/', $lyric, $lrcTimes);
            $lrcText = preg_replace('/\[(\d{2}):(\d{2}[\.:]?\d*)]/', '', $lyric);
            if (empty($lrcTimes)) {
                continue;
            }
            $lrcTimes = intval($lrcTimes[1]) * 60000 + intval(floatval($lrcTimes[2]) * 1000);
            $lrcText = preg_replace('/\s\s+/', ' ', $lrcText);
            $lrcText = trim($lrcText);
            $data[] = array($lrcTimes, $key, $lrcText);
        }
        sort($data);
        return $data;
    }

    private function lrctran($lyric, $tlyric)
    {
        $lyric = $this->lrctrim($lyric);
        $tlyric = $this->lrctrim($tlyric);
        $len1 = count($lyric);
        $len2 = count($tlyric);
        $result = "";
        for ($i=0,$j=0; $i<$len1&&$j<$len2; $i++) {
            while ($lyric[$i][0]>$tlyric[$j][0]&&$j+1<$len2) {
                $j++;
            }
            if ($lyric[$i][0] == $tlyric[$j][0]) {
                $tlyric[$j][2] = str_replace('/', '', $tlyric[$j][2]);
                if (!empty($tlyric[$j][2])) {
                    $lyric[$i][2] .= " ({$tlyric[$j][2]})";
                }
                $j++;
            }
        }
        for ($i=0; $i<$len1; $i++) {
            $t = $lyric[$i][0];
            $result .= sprintf("[%02d:%02d.%03d]%s\n", $t/60000, $t%60000/1000, $t%1000, $lyric[$i][2]);
        }
        return $result;
    }

    private function update()
    {
        $isAdmin = call_user_func(function () {
            $hasLogin = $this->widget('Widget_User')->hasLogin();
            $isAdmin = false;
            if (!$hasLogin) {
                return false;
            }
            $isAdmin = $this->widget('Widget_User')->pass('administrator', true);
            return $isAdmin;
        }, $this);

        if (!$isAdmin) {
            die('Forbidden!');
        }

        header("Content-Type: text/plain; charset=UTF-8");

        $shasum = $this->curl('https://raw.githubusercontent.com/MoePlayer/APlayer-Typecho/master/shasum.txt');

        echo "获取最新特征库...\n";
        echo $shasum."\n\n";

        $shasum = explode("\n", $shasum);
        array_pop($shasum);

        echo "开始检查本地文件...\n";

        foreach ($shasum as $remote) {
            list($remote_sha256, $filename) = explode('  ', $remote);
            if (!file_exists(__DIR__.'/'.$filename) ||
                !hash_equals(hash('sha256', file_get_contents(__DIR__.'/'.$filename)), $remote_sha256)) {
                echo "下载     ".$filename;
                $url = 'https://raw.githubusercontent.com/MoePlayer/APlayer-Typecho/master'.substr($filename, 1);

                if (file_put_contents(__DIR__.'/'.$filename, $this->curl($url))) {
                    echo " (OK)\n";
                } else {
                    die("\n下载失败，错误信息: $url\n");
                }
            } else {
                echo "无需更新  ".$filename."\n";
            }
        }

        echo "\n\n如果插件出现错误，建议禁用再启用一次插件完成升级。";
        die();
    }

    private function curl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    private function cacheWrite($k, $v, $t)
    {
        if (!isset($this->cache)) {
            return;
        }
        return $this->cache->set($k, $v, $t);
    }

    private function cacheRead($k)
    {
        if (!isset($this->cache)) {
            return false;
        }
        return $this->cache->get($k);
    }

    private function filterReferer()
    {
        $salt = Typecho_Widget::widget('Widget_Options')->plugin('Meting')->salt;
        if (empty($salt)) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie");
            return;
        }
        if (isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            http_response_code(403);
            die('[]');
        }
    }
}
