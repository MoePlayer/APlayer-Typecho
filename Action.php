<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;

class Meting_Action extends Typecho_Widget implements Widget_Interface_Do {

    public function execute(){}

    public function action(){
        $this->on($this->request->is('do=parse'))->shortcode();
        $this->on($this->request->isGet())->api();
    }

    private function check($a,$b){
        if(!in_array($a,array('netease','tencent','baidu','xiami','kugou')))return false;
        if(!in_array($b,array('song','album','search','artist','playlist','lrc','url','pic')))return false;
        return true;
    }

    private function api(){
        $this->filterReferer();
        $server=$this->request->get('server');
        $type=$this->request->get('type');
        $id=$this->request->get('id');

        if(!$this->check($server,$type)||empty($id))die('[]');

        if(!extension_loaded('Meting'))include_once 'include/Meting.php';
        $api=new \Metowolf\Meting($server);
        $api->format(true);

        $EID=md5($server.'/'.$type.'/'.$id);
        $salt=Typecho_Widget::widget('Widget_Options')->plugin('Meting')->salt;

        if(in_array($type,array('lrc','pic','url'))){
            $auth1=md5($salt.$id.$salt);
            $auth2=$this->request->get('auth');
            if(strcmp($auth1,$auth2)){
                http_response_code(403);
                die();
            }
        }

        if($type=='lrc'){
            $data=$this->cacheRead($EID,60*60*24);
            if(empty($data)){
                $data=$api->lyric($id);
                $this->cacheWrite($EID,$data);
            }
            $data=json_decode($data,true);
            header("Content-Type: application/javascript");
            echo $data['lyric'];
        }
        elseif($type=='pic'){
            $data=$this->cacheRead($EID,60*60*24);
            if(empty($data)){
                $data=$api->pic($id,90);
                $this->cacheWrite($EID,$data);
            }
            $data=json_decode($data,true);
            $this->response->redirect($data['url']);
        }
        elseif($type=='url'){
            $data=$this->cacheRead($EID,60*15);
            if(empty($data)){
                $rate=Typecho_Widget::widget('Widget_Options')->plugin('Meting')->bitrate;
                $cookie=Typecho_Widget::widget('Widget_Options')->plugin('Meting')->cookie;
                if($server=='netease'&&!empty($cookie))$api->cookie($cookie);
                $data=$api->url($id,$rate);
                $this->cacheWrite($EID,$data);
            }
            $data=json_decode($data,true);
            $url=$data['url'];

            if($server=='netease'){
                $url=str_replace('://m7c.','://m7.',$url);
                $url=str_replace('://m8c.','://m8.',$url);
                $url=str_replace('http://m8.','https://m9.',$url);
                $url=str_replace('http://m7.','https://m9.',$url);
                $url=str_replace('http://m10.','https://m10.',$url);
            }

            if(empty($url))$url='https://meting.coding.i-meto.com/empty.mp3';
            $this->response->redirect($url);
        }
        else{
            $data=$this->cacheRead($EID,60*60*2);
            if(empty($data)){
                $data=$api->$type($id);
                $this->cacheWrite($EID,$data);
            }
            $data=json_decode($data,1);
            $url=Typecho_Common::url('action/metingapi',Helper::options()->index);

            $music=array();
            foreach($data as $vo){
                $music[]=array(
                    'title'  => $vo['name'],
                    'author' => implode(' / ',$vo['artist']),
                    'url'    => $url.'?server='.$vo['source'].'&type=url&id='.$vo['url_id'].'&auth='.md5($salt.$vo['url_id'].$salt),
                    'pic'    => $url.'?server='.$vo['source'].'&type=pic&id='.$vo['pic_id'].'&auth='.md5($salt.$vo['pic_id'].$salt),
                    'lrc'    => $url.'?server='.$vo['source'].'&type=lrc&id='.$vo['lyric_id'].'&auth='.md5($salt.$vo['lyric_id'].$salt),
                );
            }
            header("Content-Type: application/javascript");
            echo json_encode($music);
        }

    }

    private function shortcode(){
        $url=$this->request->get('data');
        $url=trim($url);
        if(empty($url))return;
        $server='netease';$id='';$type='';
        if(strpos($url,'163.com')!==false){
            $server='netease';
            if(preg_match('/playlist\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/toplist\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/song\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/artist\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        elseif(strpos($url,'qq.com')!==false){
            $server='tencent';
            if(preg_match('/playlist\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/song\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/singer\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        elseif(strpos($url,'xiami.com')!==false){
            $server='xiami';
            if(preg_match('/collect\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/[\/.]\w+\/[songdem]+\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/artist\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
            if(!preg_match('/^\d*$/i',$id,$t)){
                $data=self::curl($url);
                preg_match('/'.$type.'\/(\d+)/i',$data,$id);
                $id=$id[1];
            }
        }
        elseif(strpos($url,'kugou.com')!==false){
            $server='kugou';
            if(preg_match('/special\/single\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/#hash\=(\w+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/album\/[single\/]*(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/singer\/[home\/]*(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        elseif(strpos($url,'baidu.com')!==false){
            $server='baidu';
            if(preg_match('/songlist\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/song\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/artist\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        else{
            echo "[Meting]\n[Music title=\"歌曲名\" author=\"歌手\" url=\"{$url}\" pic=\"图片文件URL\" lrc=\"歌词文件URL\"/]\n[/Meting]\n";
            return;
        }
        echo "[Meting]\n[Music server=\"{$server}\" id=\"{$id}\" type=\"{$type}\"/]\n[/Meting]\n";
    }

    private function curl($url){
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curl,CURLOPT_TIMEOUT,10);
        curl_setopt($curl,CURLOPT_REFERER,$url);
        $result=curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    private function cacheWrite($k,$v){
        if(empty($v)||is_null($v))return;
        $db=Typecho_Db::get();
        $insert=$db->insert('table.metingv1')->rows(array('id'=>md5($k),'value'=>$v,'last'=>time()));
        return $db->query($insert);
    }

    private function cacheRead($k,$t=3600){
        $db=Typecho_Db::get();
        $query=$db->select('value','last')->from('table.metingv1')->where('id=?',md5($k));
        $result=$db->fetchRow($query);

        if(isset($result['value'])){
            if(time()-$result['last']>$t){
                $delete=$db->delete('table.metingv1')->where('last<?',time()-$t);
                $db->query($delete);
                return false;
            }
            header("Meting-Cache: HIT");
            return $result['value'];
        }
        else{
            header("Meting-Cache: MISS");
            return false;
        }
    }

    private function filterReferer(){
        if(isset($_SERVER['HTTP_REFERER'])&&strpos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST'])===false){
            http_response_code(403);
            die('[]');
        }
    }
}
