<p align="center">
<img src="https://user-images.githubusercontent.com/2666735/30651452-58ae6c88-9deb-11e7-9e13-6beae3f6c54c.png" alt="Meting">
</p>

 > 在 Typecho 中使用 APlayer 播放在线音乐吧～
 > [发布页面](https://i-meto.com/meting-typecho/)  

## 介绍
  1. 支持国内五大音乐平台（网易云、QQ、虾米、百度、酷狗）的单曲/专辑/歌单播放
  2. 简单快捷，复制音乐详情页面网址，后台自动生成播放代码
  3. 前端 APlayer，后端 Meting 及时更新，保证兼容性及 API 高可用性
  4. 支持 MySql、SQLite 数据库
  5. **支持 Redis, Memcached 缓存**
  6. 支持自定义歌曲播放
  7. **自定义 API 支持**

## 声明
本作品仅供个人学习研究使用，请勿将其用作商业用途。  
**!!切勿使用本插件代码下载版权保护音乐!!**

## 安装
  1. 在本页面右上角点击 Download ZIP 下载压缩包
  2. 上传到 /usr/plugins 目录
  3. **修改文件夹名为 Meting**
  4. 后台启用插件

## 使用
在文章编辑页面，点击编辑器上的 **音乐图标** 按钮，在弹出的窗口中输入音乐地址（见支持列表），最后点击确定即可  

## 支持列表
网易云音乐 http://music.163.com
 - 单曲 http://music.163.com/#/song?id=424474911
 - 专辑 http://music.163.com/#/album?id=34808540
 - 歌手 http://music.163.com/#/artist?id=3681
 - 歌单 http://music.163.com/#/playlist?id=436843836
 - 榜单 http://music.163.com/#/discover/toplist?id=60198

QQ 音乐 https://y.qq.com
 - 单曲 https://y.qq.com/n/yqq/song/000jDQWP4JiB3y.html
 - 专辑 https://y.qq.com/n/yqq/album/003rytri2FHG3V.html
 - 歌手 https://y.qq.com/n/yqq/singer/003Nz2So3XXYek.html
 - 歌单 https://y.qq.com/n/yqq/playlist/1144188779.html

虾米音乐 http://www.xiami.com or http://h.xiami.com
 - 单曲 http://www.xiami.com/song/bf08DNT3035f
 - 专辑 http://www.xiami.com/album/ddOZW6a10eb
 - 歌手 http://www.xiami.com/artist/be6yda0f8
 - 歌单 http://www.xiami.com/collect/254478782

酷狗音乐 http://www.kugou.com
 - 单曲 http://www.kugou.com/song/#hash=09E8DE70A24C97B92A29F6A19F3528A2
 - 专辑 http://www.kugou.com/yy/album/single/1645030.html
 - 歌手 http://www.kugou.com/yy/singer/home/3520.html
 - 歌单 http://www.kugou.com/yy/special/single/119859.html

百度音乐 http://music.baidu.com/
 - 单曲 http://music.baidu.com/song/268275324
 - 专辑 http://music.baidu.com/album/268275533
 - 歌手 http://music.baidu.com/artist/1219
 - 歌单 http://music.baidu.com/songlist/364201689

## FAQ

<details><summary>PJAX 页面切换问题？</summary><br>

需要视情况在主题设置中添加回调函数

### 停止播放

```
if (typeof aplayers !== 'undefined'){
    for (var i = 0; i < aplayers.length; i++) {
        try {aplayers[i].destroy()} catch(e){}
    }
}
```

### 重载播放器

```
loadMeting();
```

</details>


<details><summary>不支持混合歌单？</summary><br>

由于 2.x 版本重写了实现方式，旧的混合歌单将不再支持，建议通过各音乐平台创建歌单的方式添加。

</details>


<details><summary>升级问题？</summary><br>

目前插件支持在设置页面差量升级，但由于某些版本做了较大调整，可能造成插件无法使用，可以禁用插件再启用修复。

</details>


更多问题可以通过 issue 页面提交，或者通过 Telegram、邮件向我反馈

## LICENSE
APlayer-Typecho is under the MIT license.
