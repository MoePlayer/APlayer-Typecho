# Meting for Typecho
在 Typecho 中使用 APlayer 播放在线音乐吧～  
[在线演示](http://demo.i-meto.com)

## 介绍
 1. 支持国内五大音乐平台（网易云、QQ、虾米、百度、酷狗）的单曲/专辑/歌单播放
 2. 简单快捷，复制音乐详情页面网址，后台自动生成播放代码
 3. **支持不同音乐平台歌曲混合播放**
 4. 前端 Aplayer，后端 Meting 及时更新，保证兼容性及 API 高可用性

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
**歌曲混合特性:** 允许添加多个音乐地址，每行一个，插件会自动将所有歌曲合并在同一个歌单进行播放。

## 支持列表
网易云音乐 http://music.163.com
 - 单曲 http://music.163.com/#/song?id=424474911
 - 专辑 http://music.163.com/#/album?id=34808540
 - 歌手 http://music.163.com/#/artist?id=3681
 - 歌单 http://music.163.com/#/playlist?id=436843836
 - 榜单 http://music.163.com/#/discover/toplist?id=60198

QQ 音乐 http://y.qq.com
- 单曲 https://y.qq.com/portal/song/000jDQWP4JiB3y.html
- 专辑 https://y.qq.com/portal/album/003rytri2FHG3V.html
- 歌手 https://y.qq.com/portal/singer/003Nz2So3XXYek.html
- 歌单 https://y.qq.com/portal/playlist/1144188779.html

虾米音乐 http://www.xiami.com or http://h.xiami.com
- 单曲 http://www.xiami.com/song/bf08DNT3035f
- 专辑 http://www.xiami.com/album/ddOZW6a10eb
- 歌手 http://www.xiami.com/artist/be6yda0f8
- 歌单 http://www.xiami.com/collect/254478782

酷狗音乐 http://www.kugou.com
- 单曲 暂不支持直接解析，可直接修改短代码实现
- 专辑 http://www.kugou.com/yy/album/single/1645030.html
- 歌手 http://www.kugou.com/yy/singer/home/3520.html
- 歌单 http://www.kugou.com/yy/special/single/119859.html

百度音乐 http://music.baidu.com/
- 单曲 http://music.baidu.com/song/268275324
- 专辑 http://music.baidu.com/album/268275533
- 歌手 http://music.baidu.com/artist/1219
- 歌单 http://music.baidu.com/songlist/364201689

## FAQ
Q: 如何清除歌单、歌词缓存？  
A: 为了减少服务器压力，插件设置对歌单、歌词数据进行缓存，缓存会根据时间周期自动更新管理，无需人工干预。**如果需要强制清除，可以通过禁用再启用插件实现，不影响文章中歌曲信息**  
...

更多问题可以通过 issue 页面提交，或者通过 Telegram、邮件向我反馈

## LICENSE
Meting-Typecho-Plugin is under the MIT license.
