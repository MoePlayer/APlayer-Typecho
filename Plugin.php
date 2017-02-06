<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;

/**
 * Meting for Typecho | 在 Typecho 中使用 APlayer 播放在线音乐吧～
 *
 * @package Meting
 * @author METO
 * @version 1.0.3
 * @dependence 13.12.12-*
 * @link https://github.com/metowolf/Meting
 *
 */

define('METING_VERSION','1.0.3');

class Meting_Plugin extends Typecho_Widget implements Typecho_Plugin_Interface
{
    protected static $PID = 0;
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Meting_Plugin::install();
        Helper::addRoute("Meting_Route","/MetingAPI","Meting_Action",'action');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter   =array('Meting_Plugin','playerFilter');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx=array('Meting_Plugin','playerReplace');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx=array('Meting_Plugin','playerReplace');
        Typecho_Plugin::factory('Widget_Archive')->header=array('Meting_Plugin','header');
        Typecho_Plugin::factory('Widget_Archive')->footer=array('Meting_Plugin','footer');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('Meting_Plugin', 'addButton');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('Meting_Plugin', 'addButton');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Meting_Plugin::uninstall();
        Helper::removeRoute("Meting_Route");
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'theme', null, '#ad7a86',
            _t('播放器颜色'),
            _t('播放器默认的主题颜色，支持如 #372e21、#75c、red，该设定会被[Meting]标签中的theme属性覆盖，默认为 #ad7a86'));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'height', null, '340px',
            _t('播放器列表最大高度'),
            _t(''));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Radio(
            'autoplay', array('true'=>_t('是'),'false'=>_t('否')),'false',
            _t('全局自动播放'),
            _t(''));
        $form->addInput($t);
        $t = new Typecho_Widget_Helper_Form_Element_Radio(
            'mode', array('circulation'=>_t('循环'),'single'=>_t('单曲'),'order'=>_t('列表'),'random'=>_t('随机')),'circulation',
            _t('全局播放模式'),
            _t(''));
        $form->addInput($t);
        $t= new Typecho_Widget_Helper_Form_Element_Radio(
            'preload', array('auto'=>_t('自动'),'none'=>_t('不加载'),'metadata'=>_t('加载元数据')), 'auto',
            _t('预加载属性'),
            _t(''));
        $form->addInput($t);
        $t= new Typecho_Widget_Helper_Form_Element_Radio(
            'bitrate', array('128'=>_t('流畅品质'),'192'=>_t('清晰品质'),'320'=>_t('高品质')), '192',
            _t('默认音质'),
            _t(''));
        $form->addInput($t);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function render(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function header(){
        $dir=Helper::options()->pluginUrl.'/Meting/assets/';
        $ver=METING_VERSION;
        echo "<!-- Meting Start -->
                <script type=\"text/javascript\" src=\"{$dir}APlayer.min.js?v={$ver}\"></script>
            <!-- Meting End -->";
    }

    public static function footer(){}

    public static function getPID(){
        return ++self::$PID;
    }

    public static function playerFilter($data){
        return $data;
    }

    public static function playerReplace($data,$widget,$last){
        $text=$last?:$data;
        if($widget instanceof Widget_Archive){
            $data=$text;
            $pattern=self::get_shortcode_regex(array('Meting'));
            $text=preg_replace_callback("/$pattern/",array('Meting_Plugin','parseCallback'),$data);
        }
        return $text;
    }

    public static function parseCallback($matches){
        $t=self::shortcode_parse_atts(htmlspecialchars_decode($matches[3]));
        $setting=base64_encode(json_encode($t));

        $matches[5]=htmlspecialchars_decode($matches[5]);
        $pattern=self::get_shortcode_regex(array('Music'));
        preg_match_all("/$pattern/",$matches[5],$all);
        if(sizeof($all[3]))return Meting_Plugin::parseMusic($all[3],$setting);
    }

    public static function parseMusic($matches,$setting){
        $data=array();
        foreach($matches as $vo){
            $t=self::shortcode_parse_atts(htmlspecialchars_decode($vo));
            if(!in_array($t['server'],array('netease','tencent','xiami','baidu','kugou')))continue;
            if(!in_array($t['type'],array('search','album','playlist','artist','song')))continue;
            $data[]=$t;
        }
        $id=self::getPID();
        $dir=Typecho_Common::url('MetingAPI',Helper::options()->index);
        $data=base64_encode(json_encode($data));
        return "<div id=\"MetingPlayer{$id}\" class=\"aplayer\" /></div>
                <script type=\"text/javascript\" src=\"{$dir}?do=musicjs&s={$setting}&d={$data}&id={$id}\" async defer></script>";
    }

    public static function addButton(){
        $url=Typecho_Common::url('MetingAPI?do=parse',Helper::options()->index);
        $dir=Helper::options()->pluginUrl.'/Meting/assets/editer.js?v='.METING_VERSION;
        echo "<script type=\"text/javascript\">var murl='{$url}';</script>
                <script type=\"text/javascript\" src=\"{$dir}\"></script>";
    }

    # https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L508
    private static function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                $atts[] = stripcslashes($m[8]);
            }
            foreach( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    # https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L254
    private static function get_shortcode_regex( $tagnames = null ) {
        $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
        return '\[(\[?)('.$tagregexp.')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)';
    }

    public static function install(){
        $db=Typecho_Db::get();
        $prefix=$db->getPrefix();
        $scripts=file_get_contents(__DIR__.'/include/install.sql');
		$scripts=str_replace('typecho_',$prefix,$scripts);
		$scripts=explode(';', $scripts);
        try{
            foreach($scripts as $script){
				$script=trim($script);
				if($script){
					$db->query($script,Typecho_Db::WRITE);
				}
			}
        }catch(Typecho_Db_Exception $e){
            $code=$e->getCode();
            if($code=='42S01')return;
            throw new Typecho_Plugin_Exception('数据表建立失败，插件启用失败。错误号: '.$code);
        }
    }

    public static function uninstall(){
        $db=Typecho_Db::get();
        $prefix=$db->getPrefix();
        $scripts=file_get_contents(__DIR__.'/include/uninstall.sql');
		$scripts=str_replace('typecho_',$prefix,$scripts);
		$scripts=explode(';', $scripts);
        try{
            foreach($scripts as $script){
				$script=trim($script);
				if($script){
					$db->query($script,Typecho_Db::WRITE);
				}
			}
        }catch(Typecho_Db_Exception $e){
            $code=$e->getCode();
            throw new Typecho_Plugin_Exception('数据表清空失败，插件禁用失败。错误号: '.$code);
        }
    }
}
