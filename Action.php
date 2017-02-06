<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;
if(!extension_loaded('Meting'))include_once 'include/Meting.php';

class Meting_Action extends Typecho_Widget implements Widget_Interface_Do {

    public function execute(){}

    public function action(){
        $this->on($this->request->is('do=musicjs'))->musicjs();
        $this->on($this->request->is('do=url'))->url();
        $this->on($this->request->is('do=pic'))->pic();
        $this->on($this->request->is('do=lrc'))->lrc();
        $this->on($this->request->is('do=parse'))->shortcode();
    }

    private function shortcode(){
        $urls=$this->request->get('data');
        $urls=explode("\n",$urls);
        echo "[Meting]\n";
        foreach($urls as $url){
            $url=trim($url);
            if($url=="")continue;
            $server='音乐平台';$id='编号';$type='类型';
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
                if(preg_match('/collect\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
                elseif(preg_match('/album\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'album');
                elseif(preg_match('/song\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'song');
                elseif(preg_match('/artist\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'artist');
                if(!preg_match('/^\d*$/i',$id,$t)){
                    $data=self::curl($url);
                    preg_match('/'.$type.'\/(\d+)/i',$data,$id);
                    $id=$id[1];
                }
            }
            elseif(strpos($url,'kugou.com')!==false){
                $server='kugou';
                if(preg_match('/special\/single\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
                elseif(preg_match('/album\/single\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
                elseif(preg_match('/singer\/home\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
            }
            elseif(strpos($url,'baidu.com')!==false){
                $server='baidu';
                if(preg_match('/songlist\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
                elseif(preg_match('/album\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
                elseif(preg_match('/song\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'song');
                elseif(preg_match('/artist\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
            }
            else list($id,$type)=array($url,'search');
            echo '[Music server="'.$server.'" id="'.$id.'" type="'.$type.'"/]'."\n";
        }
        echo "[/Meting]";
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

    private function musicjs(){
        self::filterReferer();
        $PID=$this->request->get('id');
        $data=$this->request->get('d');
        $data=json_decode(base64_decode($data),1);
        $setting=$this->request->get('s');
        $setting=json_decode(base64_decode($setting),1);
        $music=array();
        foreach($data as $vo){
            $EID=md5('js/'.$vo['server'].'/'.$vo['type'].'/'.$vo['id']);
            $t=self::cacheRead($EID,60*60*24*3);
            if(!$t){
                $API=(new Meting($vo['server']))->format(true);
                $t=call_user_func_array(array($API,$vo['type']),array($vo['id']));
                $t=json_decode($t,1);
                self::cacheWrite($EID,$t);
            }
            $music=array_merge($music,$t);
        }
        $player=array(
            'theme'    => $setting['theme']?:Typecho_Widget::widget('Widget_Options')->plugin('Meting')->theme?:'red',
            'preload'  => $setting['preload']?:Typecho_Widget::widget('Widget_Options')->plugin('Meting')->preload?:'auto',
            'autoplay' => $setting['autoplay']?:Typecho_Widget::widget('Widget_Options')->plugin('Meting')->autoplay?:'false',
            'height'   => $setting['height']?:Typecho_Widget::widget('Widget_Options')->plugin('Meting')->height?:'340px',
            'mode'   => $setting['mode']?:Typecho_Widget::widget('Widget_Options')->plugin('Meting')->mode?:'circulation',
            'music'    => array(),
        );
        foreach($music as $vo){
            $URI=Typecho_Common::url('MetingAPI?site='.$vo['source'],Helper::options()->index);
            $player['music'][]=array(
                'title'=>$vo['name'],
                'author'=>implode(' / ',$vo['artist']),
                'url'=>$URI.'&do=url&id='.$vo['url_id'],
                'pic'=>$URI.'&do=pic&id='.$vo['pic_id'],
                'lrc'=>$URI.'&do=lrc&id='.$vo['lyric_id'],
            );
        }
        if(sizeof($player['music'])==1)$player['music']=$player['music'][0];
        $player['music']=json_encode($player['music']);

        header('content-type:application/javascript');
        echo "
            var Meting{$PID} = new APlayer({
                element: document.getElementById('MetingPlayer'+{$PID}),
                autoplay: ".$player['autoplay'].",
                preload: \"".$player['preload']."\",
                showlrc: 3,
                mutex: true,
                mode: '".$player['mode']."',
                theme: \"".$player['theme']."\",
                music: ".$player['music'].",
                listmaxheight: '".$player['height']."',
            });
            ";
    }

    private function url(){
        self::filterReferer();
        $id=$this->request->get('id');
        $site=$this->request->get('site');
        $rate=Typecho_Widget::widget('Widget_Options')->plugin('Meting')->bitrate;

        $cachekey="url/{$site}/{$id}/{$rate}";
        $data=self::cacheRead($cachekey,60*15);
        if(!$data){
            $data=(new Meting($site))->url($id,$rate);
            $data=json_decode($data,1);
            self::cacheWrite($cachekey,$data);
        }
        if(empty($data['url']))$data['url']='https://oc1pe0tot.qnssl.com/copyright.m4a';
        $this->response->redirect($data['url']);
    }

    private function pic(){
        self::filterReferer();
        $id=$this->request->get('id');
        $site=$this->request->get('site');

        $cachekey="pic/{$site}/{$id}";
        $data=self::cacheRead($cachekey,60*60*24*30);
        if(!$data){
            $data=(new Meting($site))->pic($id,90);
            $data=json_decode($data,1);
            self::cacheWrite($cachekey,$data);
        }
        $this->response->redirect($data['url']);
    }

    private function lrc(){
        self::filterReferer();
        $id=$this->request->get('id');
        $site=$this->request->get('site');

        $cachekey="lyric/{$site}/{$id}";
        $data=self::cacheRead($cachekey,60*60*24*10);
        if(!$data){
            $data=(new Meting($site))->format(true)->lyric($id);
            $data=json_decode($data,1);
            self::cacheWrite($cachekey,$data);
        }
        if(!empty($data['tlyric']))$text=$this->lrctran($data['lyric'],$data['tlyric']);
        else $text=$data['lyric'];
        if(strlen($text)==0)$text='[00:00.00]无歌词';
        echo $text;
    }

    private function lrctran($lyric,$tlyric){
        preg_match_all('/\[(\d{2}:\d{2}\.\d+)\]([^\n]+)/i',$lyric,$t1);
        preg_match_all('/\[(\d{2}:\d{2}\.\d+)\]([^\n]+)/i',$tlyric,$t2);
        $from=$to=$t1[0];
        $len=sizeof($t1[0]);
        for($i=0,$j=0;$i<$len;$i++){
            while($t1[1][$i]>$t2[1][$j]&&$j+1<$len)$j++;
            if($t1[1][$i]==$t2[1][$j]){
                $t=trim(str_replace('/','',$t2[2][$j]));
                if($t)$to[$i].=" (".$t2[2][$j].")";
                $j++;
            }
        }
        return str_replace($from,$to,$lyric);
    }

    private function cacheWrite($k,$v){
        if(!is_array($v))return;
        $db=Typecho_Db::get();
        $prefix=$db->getPrefix();
        $insert=$db->insert($prefix.'meting')->rows(array('id'=>sha1($k),'value'=>serialize($v),'date'=>time()));
        return $db->query($insert);
    }

    private function cacheRead($k,$t=60*60){
        $db=Typecho_Db::get();
        $prefix=$db->getPrefix();
        $query= $db->select('value','date')->from($prefix.'meting')->where('id=?',sha1($k));
        $result = $db->fetchAll($query);
        if(sizeof($result)){
            if(time()-$result[0]['date']>$t){
                $delete=$db->delete($prefix.'meting')->where('date<?',time()-$t);
                $db->query($delete);
                return false;
            }
            return unserialize($result[0]['value']);
        }
        else return false;
    }

    private function filterReferer(){
        if(isset($_SERVER['HTTP_REFERER'])&&strpos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST'])===false)die(403);
    }
}
