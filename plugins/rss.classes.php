<?php
if (!(defined('DATALIFEENGINE'))) {
    exit('Hacking attempt!');
}
if (!function_exists('array_merge')) {
    function array_merge($array1, $array2)
    {
        $aray = array();
        foreach ($array1 as $value1)
            $aray[] = $value1;
        foreach ($array2 as $value2)
            $aray[] = $value2;
        return;
    }
}
@require_once ENGINE_DIR . '/data/rss_config.php';
if ($config['version_id'] > '10.1')
    date_default_timezone_set($config['date_adjust']);
if ($config_rss['DOCUMENT_ROOT'] != '' and $config_rss['http_url'] != '')
    define('ROOTS_DIR', $config_rss['DOCUMENT_ROOT']);
else
    define('ROOTS_DIR', ROOT_DIR);

class rss_parser
{
    var $default_cp = '';
    var $CDATA = 'strip';
    var $cp = '';
    var $items_limit = 0;
    var $stripHTML = False;
    var $date_format = '';
    var $channeltags = array(0 => 'title', 1 => 'link', 2 => 'description', 3 => 'language', 4 => 'copyright', 5 => 'managingEditor', 6 => 'webMaster', 7 => 'lastBuildDate', 8 => 'rating', 9 => 'docs');
    var $itemtags = array(0 => 'title', 1 => 'link', 2 => 'description', 3 => 'author', 4 => 'category', 5 => 'comments', 6 => 'enclosure', 7 => 'guid', 8 => 'pubDate', 9 => 'source', 10 => 'full-text', 11 => 'content');
    var $imagetags = array(0 => 'title', 1 => 'url', 2 => 'link', 3 => 'width', 4 => 'height');
    var $textinputtags = array(0 => 'title', 1 => 'description', 2 => 'name', 3 => 'link');
    function get($rss_url, $proxy)
    {
        $result = $this->Parse($rss_url, $proxy);
        return $result;
    }
    function my_preg_match($pattern, $subject)
    {
        preg_match($pattern, $subject, $out);
        if (isset($out[1])) {
            $out[1] = strtr($out[1], array(
                '<![CDATA[' => '',
                ']]>' => ''
            ));
            return trim($out[1]);
        }
        return '';
    }
    function unhtmlentities($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
        $trans_tbl = array_flip($trans_tbl);
        $trans_tbl += array(
            '&apos;' => '\''
        );
        return strtr($string, $trans_tbl);
    }
    function parse($rss_url, $proxy)
    {
        global $row, $config_rss, $config;
        $cookies     = '';
        $link        = get_urls($rss_url);
        $rss_content = get_full($link[scheme], $link['host'], $link['path'], $link['query'], $cookies, $proxy);
        $rss_content = str_replace("content:encoded>", "content>", $rss_content);
        $rss_content = preg_replace("#content:encoded(.+?)>#i", "content>", $rss_content);
        $rss_content = str_replace("yandex:full-text>", "full-text>", $rss_content);
        if ($rss_content != '') {
            if ($this->default_cp != '' or $this->default_cp != '0') {
                $this->default_cp = explode("/", $this->default_cp);
                $this->default_cp = reset($this->default_cp);
            }
            if ($this->default_cp == '' or $this->default_cp == '0') {
                preg_match('#<.*?encoding="(.*?)".*?>#i', $rss_content, $charset);
                if ($charset[1] == '')
                    preg_match('#<.*?encoding=\'(.*?)\'.*?>#i', $rss_content, $charset);
                if ($charset[1] == '')
                    $charset[1] = charset($rss_content);
            } else {
                $charset[1] = $this->default_cp;
            }
            $result['html_title'] = $this->my_preg_match('#<title>(.*?)</title>#is', $rss_content);
            if (strtolower($charset[1]) != strtolower($config['charset'])) {
                $result['html_title'] = convert(strtolower($charset[1]), strtolower($config['charset']), trim($result['html_title']));
            }
            preg_match('\'<channel.*?>(.*?)<item.*?>\'si', $rss_content, $out_channel);
            foreach ($this->channeltags as $channeltag) {
                $temp = $this->my_preg_match('\'<' . $channeltag . '.*?>(.*?)</' . $channeltag . '>\'si', $out_channel[1]);
                if ($temp != '') {
                    if (strtolower($charset[1]) != strtolower($config['charset'])) {
                        $temp = convert(strtolower($charset[1]), strtolower($config['charset']), trim($temp));
                    }
                    $result[$channeltag] = $temp;
                    continue;
                }
            }
            if ($this->date_format != '') {
                if ($timestamp = strtotime($result['lastBuildDate']) !== -1) {
                    $result['lastBuildDate'] = date($this->date_format, $timestamp);
                }
            }
            preg_match('\'<textinput(|[^>]*[^/])>(.*?)</textinput>\'si', $rss_content, $out_textinfo);
            if (isset($out_textinfo[2])) {
                foreach ($this->textinputtags as $textinputtag) {
                    $temp = $this->my_preg_match('\'<' . $textinputtag . '.*?>(.*?)</' . $textinputtag . '>\'si', $out_textinfo[2]);
                    if ($temp != '') {
                        if (strtolower($charset[1]) != strtolower($config['charset'])) {
                            $temp = convert(strtolower($charset[1]), strtolower($config['charset']), trim($temp));
                        }
                        $result['textinput_' . $textinputtag] = $temp;
                        continue;
                    }
                }
            }
            preg_match('\'<image.*?>(.*?)</image>\'si', $rss_content, $out_imageinfo);
            if (isset($out_imageinfo[1])) {
                foreach ($this->imagetags as $imagetag) {
                    $temp = $this->my_preg_match('\'<' . $imagetag . '.*?>(.*?)</' . $imagetag . '>\'si', $out_imageinfo[1]);
                    if ($temp != '') {
                        if (strtolower($charset[1]) != strtolower($config['charset'])) {
                            $temp = convert(strtolower($charset[1]), strtolower($config['charset']), trim($temp));
                        }
                        $result['image_' . $imagetag] = $temp;
                        continue;
                    }
                }
            }
            preg_match_all("#<item(.*?)>(.*?)<\/item>#is", $rss_content, $items);
            $rss_items       = $items[2];
            $i               = 0;
            $result['items'] = array();
            foreach ($rss_items as $rss_item) {
                if (!((!($i < $this->items_limit) AND !($this->items_limit == 0)))) {
                    foreach ($this->itemtags as $itemtag) {
                        $temp = $this->my_preg_match('\'<' . $itemtag . '.*?>(.*?)</' . $itemtag . '>\'si', $rss_item);
                        if ($temp != '') {
                            if (strtolower($charset[1]) != strtolower($config['charset'])) {
                                $temp = convert(strtolower($charset[1]), strtolower($config['charset']), trim($temp));
                            }
                            $result['items'][$i][$itemtag] = $temp;
                            continue;
                        }
                    }
                    foreach ($this->itemtags as $itemtag) {
                        preg_match_all('\'<enclosure url="(.*?)" type="(.*?)" />\'si', $rss_item, $temp);
                        if (!empty($temp[1])) {
                            $temp[1] = array_unique($temp[1]);
                            foreach ($temp[1] as $kk => $rez) {
                                $temp_enclosure .= "[img]{$rez}[/img]";
                                $rss_item = str_replace($temp[0][$kk], '', $rss_item);
                            }
                            $result['items'][$i]['enclosure'] = $temp_enclosure;
                            continue;
                        }
                    }
                    $result['items'][$i]['full-text'] = $result['items'][$i]['full-text'] . $result['items'][$i]['enclosure'];
                    if (empty($result['items'][$i]['full-text']))
                        $result['items'][$i]['full-text'] = $result['items'][$i]['content'] . $result['items'][$i]['enclosure'];
                    if ($this->stripHTML) {
                        if ($result['items'][$i]['description']) {
                            $result['items'][$i]['description'] = strip_tags_smart($this->unhtmlentities(strip_tags_smart($result['items'][$i]['description'])));
                        }
                    }
                    if ($this->stripHTML) {
                        if ($result['items'][$i]['title']) {
                            $result['items'][$i]['title'] = strip_tags_smart($this->unhtmlentities(strip_tags_smart($result['items'][$i]['title'])));
                        }
                    }
                    if ($this->date_format != '') {
                        if ($timestamp = strtotime($result['items'][$i]['pubDate']) !== -1) {
                            $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                        }
                    }
                    ++$i;
                    continue;
                }
            }
            $result['charset']     = $charset[1];
            $result['items_count'] = $i;
            return $result;
        }
        return False;
    }
}
class image_controller
{
    var $img;
    var $img_orig = '';
    var $img_thumb = '';
    var $img_medium = '';
    var $short_story = '';
    var $full_story = '';
    var $allow_watermark = false;
    var $images = array();
    var $short_images = array();
    var $full_images = array();
    var $thumbs = array();
    var $prefix = '';
    var $upload_images = array();
    var $upload_image = array();
    var $image = array();
    var $image_url = array();
    var $dim_week = '';
    var $post = '';
    var $posts = '';
    var $max_up_side = 0;
    var $radikal = false;
    function image_host($url)
    {
        if (empty($this->dubl)) {
            $image_host = @file(ENGINE_DIR . '/inc/plugins/files/image_host.txt');
            foreach ($image_host as $it) {
                $it = addcslashes(stripslashes(trim($it)), '"[]!-.?*\\()|/');
                if (preg_match('#' . $it . '#i', $url)) {
                    return false;
                }
            }
        }
        return true;
    }
    function rewrite_im($images, $image_new)
    {
        $image_new = explode('.', $image_new);
        $image_new = current($image_new);
        if ($this->rewrite == 1 and count($images) != 0) {
            foreach ($images as $image_old) {
                $image_news = explode('/', $image_old);
                $image_news = end($image_news);
                if (preg_match('#' . trim($image_new) . '#i', trim($image_old)))
                    return $image_news;
            }
        }
        return false;
    }
    function get_url($url)
    {
        $value = str_replace('http://', '', $url);
        $value = str_replace('https://', '', $value);
        $value = explode('/', $value);
        return reset($value);
    }
    function full_get_images($content)
    {
        preg_match_all('#\[thumb.*?\](.+?)\[\/thumb\]#i', $content, $preg_array);
        if (count($preg_array[1]) != 0) {
            foreach ($preg_array[1] as $item) {
                if (!(in_array($item, $this->full_images)) and !(in_array($item, $this->short_images))) {
                    $this->full_images[] = $item;
                    continue;
                }
            }
        }
        preg_match_all('#\[img.*?\](.+?)\[\/img\]#i', $content, $preg_array);
        if (count($preg_array[1]) != 0) {
            foreach ($preg_array[1] as $item) {
                if (!(in_array($item, $this->full_images)) and !(in_array($item, $this->short_images))) {
                    $this->full_images[] = $item;
                    continue;
                }
            }
        }
        preg_match_all('#\[medium.*?\](.+?)\[\/medium\]#i', $content, $preg_array);
        if (count($preg_array[1]) != 0) {
            foreach ($preg_array[1] as $item) {
                if (!(in_array($item, $this->full_images)) and !(in_array($item, $this->short_images))) {
                    $this->full_images[] = $item;
                    continue;
                }
            }
        }
    }
    function short_get_images($content)
    {
        preg_match_all('#\[thumb.*?\](.+?)\[\/thumb\]#i', $content, $preg_array);
        if (count($preg_array[1]) != 0) {
            foreach ($preg_array[1] as $item) {
                if (!(in_array($item, $this->short_images))) {
                    $this->short_images[] = $item;
                    continue;
                }
            }
        }
        preg_match_all('#\[img.*?\](.+?)\[\/img\]#i', $content, $preg_array);
        if (count($preg_array[1]) != 0) {
            foreach ($preg_array[1] as $item) {
                if (!(in_array($item, $this->short_images))) {
                    $this->short_images[] = $item;
                    continue;
                }
            }
        }
        preg_match_all('#\[medium.*?\](.+?)\[\/medium\]#i', $content, $preg_array);
        if (count($preg_array[1]) != 0) {
            foreach ($preg_array[1] as $item) {
                if (!(in_array($item, $this->short_images))) {
                    $this->short_images[] = $item;
                    continue;
                }
            }
        }
    }
    function serv($image_url, $i)
    {
        global $config, $config_rss, $most;
		
		if ( parse_url($image_url, PHP_URL_HOST) == parse_url($config['http_home_url'], PHP_URL_HOST) )	{
			$image_name = str_replace ( $config['http_home_url'] . 'uploads/posts/', "@@@", $image_url);
                if (!(in_array($image_name, $this->upload_images))) {
                    $this->upload_images[]          = $image_name;
                }
        return true;
		}
		
        if ($this->dim_week != '')
            $this->prefix = $this->dim_week . '-';
        else
            $this->prefix = time() . '-';
        if (!is_dir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data)) {
            @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data, 0777);
            chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data);
            @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/thumbs', 0777);
            chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/thumbs');
            if ($config['version_id'] >= '10.3') {
                @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/medium', 0777);
                chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/medium');
            }
        }
        $image_news = basename($image_url);
        $image_news = explode('/', $image_news);
        $image_news = end($image_news);
        $image_arr  = explode('_', $image_news);
        if (count($image_arr) != 0)
            $imag_new = end($image_arr);
        else
            $imag_new = $image_arr;
        $imag_new_explode = explode('.', $imag_new);
        $end_imag         = end($imag_new_explode);
        $end_imag         = e_sub($end_imag, 0, 5);
        if ($imag_new != '')
            $imag_new = totranslit(urldecode(str_replace("." . $end_imag, "", $imag_new))) . '.' . $end_imag;
        if ($this->dim_sait == 1 or $this->dim_cat == 1 or $this->dim_week != '') {
            $pref = '';
            if ($this->dim_sait == 1)
                $pref .= $this->get_url(($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url'])) . '_';
            if ($this->dim_cat == 1 and $this->cat != '')
                $pref .= $this->cat . '_';
            if (@file_exists($this->img_orig . $pref . $this->prefix . $i . '.' . $end_imag) and $this->rewrite != 1)
                $image_new = $pref . $this->prefix . mt_rand(10, 99) . $i . '.' . $end_imag;
            else
                $image_new = $pref . $this->prefix . $i . '.' . $end_imag;
        } else {
            if (@file_exists($this->img_orig . $this->prefix . $i . $imag_new) and $this->rewrite != 1)
                $image_new = $this->prefix . mt_rand(10, 99) . $i . $imag_new;
            else
                $image_new = $this->prefix . $i . $imag_new;
        }
        $image_new = str_replace('%27', '', $image_new);
        if ($config['charset'] != 'utf-8')
            $image_urls = @iconv($config['charset'], 'utf-8//TRANSLIT//IGNORE', $image_url);
        if ($image_urls == '')
            $image_urls = $image_url;
        
        if ($GLOBALS['full_news_link'] == '')
            $news_link = $image_urls;
        else
            $news_link = $GLOBALS['full_news_link'];
        
        
        
        
        if ($most and parse_url($news_link, PHP_URL_HOST) == parse_url($image_urls, PHP_URL_HOST)) {
            list($ad_most, $gq_most) = explode("?", $most);
            $image_urls = $ad_most . "?url=" . $image_urls;
            if ($gq_most)
                $image_urls .= "&" . str_replace(":", "=", $gq_most);
        }
        
        if (chmod_pap($this->img_orig)) {
            if (function_exists('curl_init')) {
                sleep(1);
                $prov_url = reset_url($news_link);
                $info     = info_host($image_urls);
                $image_u  = $info['url'];
                if ('http://' . $prov_url == trim($image_u, '/') or 'http://www.' . $prov_url == trim($image_u, '/')) {
                    $image_u           = $image_urls;
                    $info['http_code'] = '200';
                }
                if ($info['http_code'] == '404' or $info['http_code'] == '500' or $info['http_code'] == '502')
                    return false;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $image_u);
                $fp = fopen($this->img_orig . $image_new, 'w+b');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_USERAGENT, get_random_agent());
                curl_setopt($ch, CURLOPT_REFERER, $news_link);
                curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
                curl_setopt($ch, CURLOPT_ENCODING, '');
                $cookie_file = ENGINE_DIR . '/cache/system/' . $prov_url . '.txt';
                if (!file_exists($cookie_file))
                    $cookie_file = ENGINE_DIR . '/cache/system/www.' . $prov_url . '.txt';
                if (@file_exists($cookie_file))
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
                @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                if ($GLOBALS['proxy'] == 1) {
                    if ($config_rss['proxy_file'] == 'yes' or $config_rss['proxy'] == '') {
                        $proxy_url = @file(ENGINE_DIR . '/inc/plugins/files/proxy.txt');
                        $proxy_url = $proxy_url[array_rand($proxy_url)];
                    } else {
                        $proxy_url = $config_rss['proxy'];
                    }
                    if (trim($proxy_url) != '') {
                        $data_proxy = explode("@", trim($proxy_url));
                        if (count($data_proxy) == 3) {
                            curl_setopt($ch, CURLOPT_PROXY, $data_proxy[1]);
                            if (!empty($data_proxy[1]))
                                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data_proxy[0]);
                            if (!empty($data_proxy[2]))
                                curl_setopt($ch, CURLOPT_PROXYTYPE, $data_proxy[2]);
                        } else {
                            curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
                        }
                    }
                }
                if (preg_match('#(https)#i', $image_u)) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }
                curl_exec($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);
                fclose($fp);
            }
            if (chmod_file($this->img_orig . $image_new) == false or !function_exists('curl_init') or $info['http_code'] != '200') {
                @copy($image_urls, $this->img_orig . $image_new);
            }
            if (chmod_file($this->img_orig . $image_new) == false) {
                $image_new = $image_news;
                return false;
            }
        } else {
            return false;
        }
        if (!(in_array($image_url, $this->image))) {
            $this->image[$image_new] = $image_url;
        }
        return true;
    }
    function process($download)
    {
        global $config, $config_rss, $options_host;
        $eror = array();
        if ($download == 'donor')
            $download = '0';
        if ($download == '')
            $download = 'serv';
        if ($download != 'serv' and $download != '0')
            $download = $this->chek_serv($download);
        if (!is_dir(ROOTS_DIR . '/uploads/posts' . $this->post)) {
            @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post, 0777);
            chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post);
        }
        
        if ($config_rss['PAP_PREF'] != 'no'){
        if (empty($config_rss['PAP_PREF']))
            $config_rss['PAP_PREF'] = 'Y-m';
        
        if (empty($GLOBALS['thistime']))
            $this->pap_data = '/' . date($config_rss['PAP_PREF']);
        else
            $this->pap_data = '/' . date($config_rss['PAP_PREF'], strtotime($GLOBALS['thistime']));
        }
		
        if (!is_dir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data)) {
            @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data, 0777);
            chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data);
            @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/thumbs', 0777);
            chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/thumbs');
            if ($config['version_id'] >= '10.3') {
                @mkdir(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/medium', 0777);
                chmod_pap(ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/medium');
            }
        }
        if ($this->dim_date == 1)
            $this->pap_data .= '/day_' . date('d');
        $this->img_orig  = ROOTS_DIR . '/uploads/posts' . $this->post . $this->pap_data . '/';
        $this->img_thumb = $this->img_orig . 'thumbs/';
        if ($config['version_id'] >= '10.3')
            $this->img_medium = $this->img_orig . 'medium/';
        $eror = array();
        if (trim($this->short_story) != '') {
            $this->short_get_images($this->short_story);
        }
        if (trim($this->full_story) != '') {
            $this->full_get_images($this->full_story);
        }
        $this->images = array_unique(array_merge($this->short_images, $this->full_images));
        if (intval($this->min_image) != 0) {
            $imagess = array();
            foreach ($this->images as $image_url) {
                $imageSizeInfo = @getimagesize($image_url);
                if ($imageSizeInfo[0] < $this->min_image or $imageSizeInfo[1] < $this->min_image) {
                    $imager_url        = trim(addcslashes(stripslashes($image_url), '"[]!-+.?*\\()|/'));
                    $this->short_story = str_replace("[img=left]" . $image_url . "[/img]", "", $this->short_story);
                    $this->short_story = str_replace("[img=right]" . $image_url . "[/img]", "", $this->short_story);
                    $this->short_story = str_replace("[img]" . $image_url . "[/img]", "", $this->short_story);
                    $this->full_story  = str_replace("[img=left]" . $image_url . "[/img]", "", $this->full_story);
                    $this->full_story  = str_replace("[img=right]" . $image_url . "[/img]", "", $this->full_story);
                    $this->full_story  = str_replace("[img]" . $image_url . "[/img]", "", $this->full_story);
                    $this->short_story = preg_replace("#(\[thumb(.*?)\]" . $imager_url . "\[\/thumb\])#i", "", $this->short_story);
                    $this->full_story  = preg_replace("#(\[thumb(.*?)\]" . $imager_url . "\[\/thumb\])#i", "", $this->full_story);
                    $this->short_story = preg_replace("#\[center\][\n\r\t ]+\[\/center\]#is", '', $this->short_story);
                    $this->full_story  = preg_replace("#\[center\][\n\r\t ]+\[\/center\]#is", '', $this->full_story);
                    $this->short_story = str_replace("[center][/center]", '', $this->short_story);
                    $this->full_story  = str_replace("[center][/center]", '', $this->full_story);
                } else {
                    $imagess[] = $image_url;
                }
            }
            $this->images = $imagess;
        }
        $gfr = count($this->images);
        $i   = 0;
        if ($download == 'serv') {
            if ($this->proxy == 10) {
                foreach ($this->images as $image_url) {
                    unset($rz);
                    if ($this->fastpic($image_url) == false)
                        $rz = $image_url;
                    if (isset($rz) == true and $download != '0')
                        $eror[] = $rz;
                }
                $this->images = array_unique(array_merge($eror, $this->imagess));
                $eror         = array();
            }
            foreach ($this->images as $image_url) {
                ++$i;
                echo "
        <div id=\"progressbar\"></div>
<script> storyes($i, $gfr,'" . trim($image_url) . "');</script>";
                ob_flush();
                flush();
                unset($rz);
                if (check_url($image_url) == true) {
                    for ($x = 0; $x < $this->prob; $x++) {
                        if ($this->serv($image_url, $i) == false) {
                            $rz = $image_url;
                        } else {
                            unset($rz);
                            break;
                        }
                    }
                } else {
                    $rz = $image_url;
                }
                if (isset($rz) == true and $download != '0')
                    $eror[] = $rz;
            }
            $ers = $this->parseserv($this->image);
            if (count($ers) != 0)
                $eror = array_unique(array_merge($eror, $ers));
        } else {
            foreach ($this->images as $k_d => $image_url) {
                if ($this->image_host($image_url) == false)
                    unset($this->images[$k_d]);
            }
            if ($this->shs == true) {
                if ($download != '0') {
                    if (intval($this->wat_h) == 1) {
                        $eror = $this->rezerv_host($this->full_images, $download);
                    } else {
                        foreach ($this->full_images as $image_url) {
                            ++$i;
                            echo "
        <div id=\"progressbar\"></div>
<script> storyes($i, $gfr,'" . trim($image_url) . "');</script>";
                            ob_flush();
                            flush();
                            unset($rz);
                            if (check_url($image_url) == true and $download != '0') {
                                if ($this->$download($image_url) == false)
                                    $rz = $image_url;
                            } else {
                                $rz = $image_url;
                            }
                            if (isset($rz) == true and $download != '0')
                                $eror[] = $rz;
                        }
                    }
                }
                $this->image = array();
                foreach ($this->short_images as $image_url) {
                    ++$i;
                    echo "
        <div id=\"progressbar\"></div>
<script> storyes($i, $gfr,'" . trim($image_url) . "');</script>";
                    ob_flush();
                    flush();
                    if (check_url($image_url) == true) {
                        for ($x = 0; $x < 1; $x++) {
                            if ($this->serv($image_url, $i) == false) {
                                $rz = $image_url;
                            } else {
                                unset($rz);
                                break;
                            }
                        }
                    } else {
                        $rz = $image_url;
                    }
                    if (isset($rz) == true and $download != '0')
                        $eror[] = $rz;
                }
                $ers = $this->parseserv($this->image);
                if (count($ers) != 0)
                    $eror = array_unique(array_merge($eror, $ers));
            } else {
                if (intval($this->wat_h) == 1) {
                    $eror = $this->rezerv_host($this->images, $download);
                } else {
                    foreach ($this->images as $image_url) {
                        unset($rz);
                        if ($image_url != '' and $download != '0') {
                            if ($this->$download($image_url) == false)
                                $rz = $image_url;
                        } else {
                            $rz = $image_url;
                        }
                        if (isset($rz) == true and $download != '0')
                            $eror[] = $rz;
                    }
                }
            }
        }
        if (count($eror) != 0) {
            if ($download != 'serv' and $download != '0')
                $eror = $this->rezerv_host($eror, $this->chek_serv($download));
        }
        return $eror;
    }
    function chek_serv($download)
    {
        global $options_host;
        if (check_url('http://' . $options_host[$download]) == true)
            return $download;
        unset($options_host['0'], $options_host['serv'], $options_host[$download]);
        while (count($options_host) != 0) {
            $download_d = array_rand($options_host);
            if (check_url('http://' . $options_host[$download_d]) == true)
                break;
            else
                unset($options_host[$download_d]);
        }
        return $download_d;
    }
    function parseserv($timage, $wat_host = false)
    {
        global $config, $config_rss, $title, $db;
        $this->short_story = str_replace("[medium", "[img", $this->short_story);
        $this->full_story  = str_replace("[medium", "[img", $this->full_story);
        $this->short_story = str_replace("medium]", "img]", $this->short_story);
        $this->full_story  = str_replace("medium]", "img]", $this->full_story);
        $this->short_story = str_replace("[thumb", "[img", $this->short_story);
        $this->full_story  = str_replace("[thumb", "[img", $this->full_story);
        $this->short_story = str_replace("thumb]", "img]", $this->short_story);
        $this->full_story  = str_replace("thumb]", "img]", $this->full_story);
        if (!empty($config['medium_image'])) {
            $m_image = explode("x", $config['medium_image']);
            $x_image = explode("x", $this->max_image);
            if (intval($m_image[0]) > intval($x_image[0])) {
                $this->medium_image = $config['medium_image'];
                $medium             = 'medium';
            } else {
                $medium = 'thumb';
            }
        } else {
            $medium = 'thumb';
        }
        $eror = array();
        $i    = 1;
        foreach ($timage as $image_new => $image_url) {
			$save_webp = false;
            $folder_prefix = $this->img_orig;
            $image_news    = basename($image_url);
            $image_news    = explode('/', $image_news);
            $image_news    = end($image_news);
            $image_arr     = explode('_', $image_news);
            if (count($image_arr) != 0)
                $imag_news = totranslit(end($image_arr));
            if (@file_exists($folder_prefix . $image_new)) {
                $imageSizeInfo = @getimagesize($folder_prefix . $image_new);
                if ($imageSizeInfo[2] == '6') {
                    $isrc = ImageCreateFromBMP($folder_prefix . $image_new);
                    imagejpeg($isrc, $folder_prefix . $image_new, 100);
                    $imageSizeInfo = @getimagesize($folder_prefix . $image_new);
                }
                if ($imageSizeInfo[2] == '1') {
                    $imageType = 'gif';
					if ($config['force_webp']){
						$isrc = imageCreateFromGif($folder_prefix . $image_new);
						imageWebp($isrc, $folder_prefix . $image_new, 90);
						$imageType = 'webp';
						$imageSizeInfo[2] = '18';
					}
                }
                if ($imageSizeInfo[2] == '2') {
                    $imageType = 'jpg';
					if ($config['force_webp']){
						$isrc = imageCreateFromJpeg($folder_prefix . $image_new);
						imageWebp($isrc, $folder_prefix . $image_new, 90);
						$imageType = 'webp';
						$imageSizeInfo[2] = '18';
					}
                }
                if ($imageSizeInfo[2] == '3') {
                    $imageType = 'png';
					if ($config['force_webp']){
						$isrc = imageCreateFromPng($folder_prefix . $image_new);
						imageWebp($isrc, $folder_prefix . $image_new, 90);
						$imageType = 'webp';
						$imageSizeInfo[2] = '18';
					}
                }
                if ($imageSizeInfo[2] == '18') {
                    $imageType = 'webp';
                }
							
				$imageSizeInfo = @getimagesize($folder_prefix . $image_new);
				
                $image_form = explode('.', $image_new);
                if ($imageType != end($image_form) and $imageType) {
                    if (count($image_form) >= 2)
                        $image_name = str_replace(end($image_form), $imageType, $image_new);
                    else
                        $image_name = $image_new . '.' . $imageType;
                    if (@file_exists($folder_prefix . $image_name))
                        $image_name = mt_rand(10, 99) . $image_name;
                    @rename($folder_prefix . $image_new, $folder_prefix . $image_name);
                } else {
                    $image_name = $image_new;
                }
                chmod_file($folder_prefix . $image_name);
                if (@file_exists(ENGINE_DIR . '/inc/image_mirror.php')) {
                    include_once ENGINE_DIR . '/inc/image_mirror.php';
                    image_mirror($folder_prefix . $image_name);
                }
            }
            $folder_img = $this->post . $this->pap_data;
            $img_file   = trim($folder_img . '/' . $image_name, '/');
            $img_hash   = sha1_file($folder_prefix . $image_name);
            $file_hash  = array();
            if ($config_rss['img_hash'] == 'yes') {
                $hash_table = $db->query("SHOW TABLES LIKE '%_img_hash%'");
                $file_hash  = $db->super_query('SELECT * FROM ' . PREFIX . "_img_hash WHERE hash = '{$img_hash}'", false);
            }
            if (!empty($file_hash[$img_hash]) and @file_exists(ROOTS_DIR . '/uploads/posts' . $file_hash[$img_hash]) and $config_rss['img_hash'] == 'yes') {
                @unlink($folder_prefix . $image_name);
                $image_name    = basename($file_hash['img']);
                $folder_img    = str_replace("/" . $image_name, "", $file_hash['img']);
                $folder_prefix = ROOTS_DIR . '/uploads/posts' . $folder_img . "/";
            } else {
                if ($config_rss['img_hash'] == 'yes')
                    $db->query("INSERT INTO " . PREFIX . "_img_hash (hash, img) VALUES ('{$img_hash}', '{$img_file}')", false);
                if (in_array($imageSizeInfo[2], array( '1','2','3','18')) and filesize($folder_prefix . $image_name) != 0) {
                    if (!@file_exists($this->watermark_image_light))
                        $this->watermark_image_light = '';
                    if (!@file_exists($this->watermark_image_dark))
                        $this->watermark_image_dark = '';
                    require_once ENGINE_DIR . '/inc/plugins/thumb.class.php';
                    if (!empty($this->rt) or !empty($this->rl) or !empty($this->rb) or !empty($this->rr)) {
                        $width     = $imageSizeInfo[0] - $this->rl - $this->rr;
                        $height    = $imageSizeInfo[1] - $this->rt - $this->rb;
                        $thumb     = new rss_thumbnail($folder_prefix . $image_name);
                        $thumb->rl = $this->rl;
                        $thumb->rt = $this->rt;
                        $thumb->rr = $this->rr;
                        $thumb->rb = $this->rb;
                        $thumb->size_auto($width . 'x' . $height);
                        $thumb->save($folder_prefix . $image_name);
                        chmod_file($folder_prefix . $image_name);
                        unset($thumb);
                    }
                    if (intval($this->max_up_side) != 0) {
                        $thumb = new rss_thumbnail($folder_prefix . $image_name);
                        $thumb->jpeg_quality($config['jpeg_quality']);
                        $thumb->size_auto($this->max_up_side, $config['o_seite']);
                        $thumb->save($folder_prefix . $image_name);
                        chmod_file($folder_prefix . $image_name);
                        unset($thumb);
                    }
                    if ($this->max_image != '0' and $wat_host == false) {
						
					
                        $thumb = new rss_thumbnail($folder_prefix . $image_name);
                        if ($thumb->size_auto($this->max_image, $config['t_seite'])) {
                            $thumb->jpeg_quality($config['jpeg_quality']);
                            if ($this->allow_watermark) {
                                $thumb->watermark_image_light = $this->watermark_image_light;
                                $thumb->watermark_image_dark  = $this->watermark_image_dark;
                                $thumb->x                     = $this->x;
                                $thumb->y                     = $this->y;
                                $thumb->margin                = $this->margin;
                                $thumb->insert_watermark($config['max_watermark']);
                            }
                            $thumb->save($this->img_thumb . $image_name);
                            unset($thumb);
                        }
                    }
                    if (!empty($this->medium_image) and $wat_host == false) {
                        $thumb = new rss_thumbnail($folder_prefix . $image_name);
                        if ($thumb->size_auto($this->medium_image, $config['t_seite'])) {
                            $thumb->jpeg_quality($config['jpeg_quality']);
                            if ($this->allow_watermark) {
                                $thumb->watermark_image_light = $this->watermark_image_light;
                                $thumb->watermark_image_dark  = $this->watermark_image_dark;
                                $thumb->x                     = $this->x;
                                $thumb->y                     = $this->y;
                                $thumb->margin                = $this->margin;
                                $thumb->insert_watermark($config['max_watermark']);
                            }
                            $thumb->save($this->img_medium . $image_name);
                            unset($thumb);
                        }
                    }
                    if ($this->allow_watermark or $wat_host == true) {
                        $thumb                        = new rss_thumbnail($folder_prefix . $image_name);
                        $thumb->watermark_image_light = trim($this->watermark_image_light);
                        $thumb->watermark_image_dark  = trim($this->watermark_image_dark);
                        $thumb->x                     = $this->x;
                        $thumb->y                     = $this->y;
                        $thumb->margin                = $this->margin;
                        $thumb->insert_watermark($config['max_watermark']);
                        $thumb->save($folder_prefix . $image_name);
                        chmod_file($folder_prefix . $image_name);
                        unset($thumb);
                    }
                } else {
                    @unlink($folder_prefix . $image_name);
                    $eror[] = $image_url;
                }
            }
            $imager_url = '';
            if (@file_exists($folder_prefix . $image_name)) {
                $serv_url   = ($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url']) . 'uploads/posts' . $folder_img . '/' . $image_name;
                $imager_url = trim(addcslashes(stripslashes($image_url), '"[]!-+.?*\\()|/:'));
                if ($medium == 'medium')
                    $ile = $folder_prefix . 'medium/';
                else
                    $ile = $folder_prefix . 'thumbs/';
                if (chmod_file($ile . $image_name) == true) {
                    $this->short_story = str_replace("[img=left]" . $image_url . "[/img]", "[" . $medium . "=left]" . $serv_url . "[/" . $medium . "]", $this->short_story);
                    $this->short_story = str_replace("[img=right]" . $image_url . "[/img]", "[" . $medium . "=right]" . $serv_url . "[/" . $medium . "]", $this->short_story);
                    $this->short_story = str_replace("[img]" . $image_url . "[/img]", "[" . $medium . "]" . $serv_url . "[/" . $medium . "]", $this->short_story);
                    $this->full_story  = str_replace("[img=left]" . $image_url . "[/img]", "[" . $medium . "=left]" . $serv_url . "[/" . $medium . "]", $this->full_story);
                    $this->full_story  = str_replace("[img=right]" . $image_url . "[/img]", "[" . $medium . "=right]" . $serv_url . "[/" . $medium . "]", $this->full_story);
                    $this->full_story  = str_replace("[img]" . $image_url . "[/img]", "[" . $medium . "]" . $serv_url . "[/" . $medium . "]", $this->full_story);
                }
                $this->short_story = str_replace($image_url, $serv_url, $this->short_story);
                $this->full_story  = str_replace($image_url, $serv_url, $this->full_story);
                $i++;
                if (!(in_array($image_name, $this->upload_images))) {
                    $this->upload_image[$image_url] = $serv_url;
                    $this->upload_images[]          = $image_name;
                }
            }
        }
        return $eror;
    }
    function download_host($url, $fg = '', $api = false)
    {
        $urls = str_replace(' ', '%20', $url);
        $fg   = str_replace(' ', '%20', $fg);
        $ch   = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urls);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        if (preg_match("#https#i", $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        if (@file_exists(ENGINE_DIR . '/inc/plugins/files/urls.txt')) {
            $urls = @file(ENGINE_DIR . '/inc/plugins/files/urls.txt');
            $urls = $urls[array_rand($urls)];
            curl_setopt($ch, CURLOPT_INTERFACE, $urls);
        }
        if ($GLOBALS['proxy'] == 1) {
            if ($config_rss['proxy_file'] == 'yes' or $config_rss['proxy'] == '') {
                $proxy_url = @file(ENGINE_DIR . '/inc/plugins/files/proxy.txt');
                $proxy_url = $proxy_url[array_rand($proxy_url)];
            } else {
                $proxy_url = $config_rss['proxy'];
            }
            if (trim($proxy_url) != '') {
                $data_proxy = explode("@", trim($proxy_url));
                if (count($data_proxy) == 3) {
                    curl_setopt($ch, CURLOPT_PROXY, $data_proxy[1]);
                    if (!empty($data_proxy[1]))
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data_proxy[0]);
                    if (!empty($data_proxy[2]))
                        curl_setopt($ch, CURLOPT_PROXYTYPE, $data_proxy[2]);
                } else {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
                }
            }
        }
        curl_setopt($ch, CURLOPT_USERAGENT, get_random_agent());
        curl_setopt($ch, CURLOPT_ENCODING, '');
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if (!empty($fg)){
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fg);
	}
        if ($api == true)
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-HTTP-Method-Override: GET'
            ));
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result != '')
            return $result;
        else
            return false;
    }
    function parsehost($image_url, $result, $pr = 1)
    {
        $image_url = addcslashes(stripslashes($image_url), '"[]!-.?*\\()|/');
        if ($pr == '1' or $pr == '') {
            $this->short_story = preg_replace('#\[img(.*?)\]' . $image_url . '\[\/img\]#i', '[img\\1]' . $result . '[/img]', $this->short_story);
            $this->full_story  = preg_replace('#\[img(.*?)\]' . $image_url . '\[\/img\]#i', '[img\\1]' . $result . '[/img]', $this->full_story);
            $this->short_story = preg_replace("#\[thumb(.*?)\]" . $image_url . "\[\/thumb\]#i", '[img\\1]' . $result . '[/img]', $this->short_story);
            $this->full_story  = preg_replace("#\[thumb(.*?)\]" . $image_url . "\[\/thumb\]#i", '[img\\1]' . $result . '[/img]', $this->full_story);
        } else {
            $this->short_story = preg_replace('#\[img(.*?)\]' . $image_url . '\[\/img\]#i', $result, $this->short_story);
            $this->full_story  = preg_replace('#\[img(.*?)\]' . $image_url . '\[\/img\]#i', $result, $this->full_story);
            $this->short_story = preg_replace("#\[thumb(.*?)\]" . $image_url . "\[\/thumb\]#i", $result, $this->short_story);
            $this->full_story  = preg_replace("#\[thumb(.*?)\]" . $image_url . "\[\/thumb\]#i", $result, $this->full_story);
        }
    }
    function get_host($url)
    {
        global $config_rss;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, get_random_agent());
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        if ($GLOBALS['proxy'] == 1) {
            if ($config_rss['proxy_file'] == 'yes' or $config_rss['proxy'] == '') {
                $proxy_url = @file(ENGINE_DIR . '/inc/plugins/files/proxy.txt');
                $proxy_url = $proxy_url[array_rand($proxy_url)];
            } else {
                $proxy_url = $config_rss['proxy'];
            }
            if (trim($proxy_url) != '') {
                $data_proxy = explode("@", trim($proxy_url));
                if (count($data_proxy) == 3) {
                    curl_setopt($ch, CURLOPT_PROXY, $data_proxy[1]);
                    if (!empty($data_proxy[1]))
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data_proxy[0]);
                    if (!empty($data_proxy[2]))
                        curl_setopt($ch, CURLOPT_PROXYTYPE, $data_proxy[2]);
                } else {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
                }
            }
        }
        if (preg_match('#(https)#i', $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        @$data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    function clipkey($image_url)
    {
        global $config, $config_rss;
        $url = 'http://www.im.sexkey.ru/upload.php';
        $fg  = 'typ=u&u_' . date('d', time()) . '1=' . $image_url;
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match('!<META.*?URL=(.*?)\">!i', $data, $out);
                $data = $this->get_host($out[1]);
                preg_match('!<input.*?value="(.*?)".*?>!i', $data, $out);
                if ($config_rss['url_img_sklad'] == '' or $config_rss['url_img_sklad'] == '1') {
                    preg_match('!<input.*?value=".*?\[img\](.*?)\[\/img\].*?>!i', $data, $out);
                    $out[1] = str_replace('thumb', 'image', $out[1]);
                    $pr     = '1';
                } else {
                    preg_match('!<input.*?value="(.*?)".*?>!i', $data, $out);
                    $out[1] = str_replace('thumb', 'image', $out[1]);
                    $pr     = '2';
                }
                if ($out[1] != '') {
                    $this->parsehost($image_url, $out[1], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function radikal($image_url)
    {
        global $config, $config_rss;
        $url = 'http://radikal.cc/Img/UploadTmpImg';
        if ($config_rss['water_radikal'] == 'yes' and $config_rss['post_radikal'] == '') {
            $water_radikal = '&SrcImg.XE=yes&SrcImg.X=' . $this->get_url(($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url']));
        } else {
            $water_radikal = '&SrcImg.XE=yes&SrcImg.X=' . convert('cp1251', 'utf-8', $config_rss['post_radikal']);
        }
        $fg = 'SrcImg.File=&SrcImg.Kind=Url&SrcImg.Url=' . $image_url . '&SrcImg.JQ=100&IM=7&SrcImg.VM=' . $this->max_image . $water_radikal;
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                if ($config_rss['url_radikal'] == '')
                    $url_radikal = '1';
                else
                    $url_radikal = $config_rss['url_radikal'];
                preg_match('!<input id="input_link_' . $url_radikal . '" value="(.*?)".*?>!i', $data, $out);
                if ($config_rss['url_radikal'] == '1' or $config_rss['url_radikal'] == '')
                    $pr = '1';
                $json = json_decode($data);
                if ($json->IsError) {
                    echo iconv('utf-8', 'cp1251', $json->Errors->_allerrors_[0]) . "<br>";
                    return false;
                }
                $time     = time() * 1000;
                $rand     = (float) rand() / (float) getrandmax();
                $callback = 'jQuery191' . $rand . '_' . $time;
                $filename = basename($image_url);
                $url      = 'http://radikal.cc/Img/SaveTmpImg?callback=' . $callback . '&Id=' . $json->Id . '&SrcKind=File&OriginalFileName=' . $filename . '&ImgParams.NeedResize=false&ImgParams.MaxSize=800&ImgParams.NeedRotate=true&ImgParams.Rotate=None&ImgParams.TextLettering=&PrevImgParams.MaxSize=180&ImgProps.IsPublicImg=false&ImgProps.Tags=&ImgProps.Comment=&ImgProps.AlbumId=&_=' . $time . '&MachineName=' . $json->MachineName;
                $result   = $this->download_host($url);
                preg_match_all('#id=(.+?)"#', $result, $matches);
                $result = $this->download_host('http://radikal.cc/Img/ShowUploadedImg?id=' . $matches[1][0]);
                if (preg_match_all('#"Url":"(.+?)"#', $result, $matches))
                    $this->parsehost($image_url, $matches[1][0], $pr);
                return true;
            }
            /*
            if(preg_match_all('#"Url":"(.+?)".+?PublicPrevUrl:"(.+?)"#', $result, $matches)){}
            */
        }
        return false;
    }
    function hostpix($image_url)
    {
        global $config, $config_rss;
        $url = 'http://hostpix.ru/upload.php';
        $fg  = 'url=' . $image_url . '&thumb_size=500';
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?>!i', $data, $out);
                if ($config_rss['url_hostpix'] == '' or $config_rss['url_hostpix'] == '1') {
                    $new_url = $out[1][2];
                    $pr      = '1';
                } else {
                    $new_url = $out[1][0];
                    $pr      = '2';
                }
                if ($new_url != '') {
                    $this->parsehost($image_url, $new_url, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function zikuka_pr($image_url)
    {
        global $config, $config_rss;
        $url = 'http://www.radikal.ru/action.aspx';
        if ($config_rss['water_radikal'] == 'yes' and $config_rss['post_radikal'] == '') {
            $water_radikal = '&XE=yes&X=' . $this->get_url(($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url']));
        } else {
            $water_radikal = '&XE=yes&X=' . convert('cp1251', 'utf-8', $config_rss['post_radikal']);
        }
        $fg = 'upload=yes' . $thumbs . '&URLF=' . $image_url . '&JQ=100&IM=7&VM=' . $this->max_image . $water_radikal;
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                if ($config_rss['url_radikal'] == '')
                    $url_radikal = '1';
                else
                    $url_radikal = $config_rss['url_radikal'];
                preg_match('!<input id="input_link_' . $url_radikal . '" value="(.*?)".*?>!i', $data, $out);
                if ($config_rss['url_radikal'] == '1' or $config_rss['url_radikal'] == '')
                    $pr = '1';
                if ($out[1] != '') {
                    $this->parsehost($image_url, $out[1], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function zikuka($image_url)
    {
        global $config, $config_rss;
        $url = 'http://zikuka.ru:8080/upload.php';
        $fg  = 'uploadtype=2&userurl=' . $image_url . '&method=file';
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                if ($config_rss['url_zikuka'] == '' or $config_rss['url_zikuka'] == '1') {
                    preg_match('!<input id="bbCode3".*?<img src=\'(.*?)\'.*?/>!i', $data, $out);
                    $pr = '1';
                } else {
                    preg_match('!<input id="THL1".*?value="(.*?)".*?/>!i', $data, $out);
                    $pr = '2';
                }
                if ($out[1] != '') {
                    $this->parsehost($image_url, $out[1], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function epikz($image_url)
    {
        global $config, $config_rss;
        $url = 'http://epikz.net/remote.php';
        $fg  = 'links=' . $image_url;
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?/>!i', $data, $out);
                if ($config_rss['url_epikz'] == '' or $config_rss['url_epikz'] == '1') {
                    $image_host = $out[1][0];
                    $pr         = '1';
                } else {
                    $image_host = $out[1][2];
                    $pr         = '2';
                }
                if ($image_host != '') {
                    $this->parsehost($image_url, $image_host, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function wwwpix($image_url)
    {
        global $config, $config_rss;
        $url = 'http://www.10pix.ru/';
        $fg  = 'uploadType=1&url=' . $image_url . '&sizeBar=0';
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                if ($config_rss['url_10pix'] == '' or $config_rss['url_10pix'] == '1') {
                    preg_match('!<input type="text" style="width: 500px;".*?\[IMG\](.*?)\[\/img\].*?>!i', $data, $out);
                    $out[1] = str_replace('.th', '', $out[1]);
                    $pr     = '1';
                } else {
                    preg_match('!<input type="text" style="width: 500px;".*?value=\'(.*?)\'.*?>!i', $data, $out);
                    $pr = '2';
                }
                if ($out[1] != '') {
                    $this->parsehost($image_url, $out[1], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function immage($image_url)
    {
        global $config, $config_rss;
        $url = 'http://immage.de/upload.html';
        $fg  = 'upart=zusammen&remote[0]=' . $image_url . '&drehen0=0&umwandeln0=0&thumbg0=1&thumbinfos0=0';
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?>!i', $data, $out);
                if ($config_rss['immage'] == '' or $config_rss['immage'] == '1') {
                    $new_url = $out[1][5];
                    $pr      = '1';
                } else {
                    $new_url = $out[1][1];
                    $pr      = '2';
                }
                if (preg_match('#http#i', $new_url)) {
                    $this->parsehost($image_url, $new_url, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function imageshack($image_url)
    {
        global $config, $config_rss;
        $url = 'http://www.imageshack.us/transload.php';
        $fg  = 'uploadtype=on&url=' . $image_url;
        for ($x = 0; $x < 1; $x++) {
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?>!i', $data, $out);
                if ($config_rss['imageshack'] == '' or $config_rss['imageshack'] == '1') {
                    $new_url = $out[1][4];
                    $pr      = '1';
                } else {
                    $new_url = $out[1][5];
                    $pr      = '2';
                }
                if ($new_url != '') {
                    $this->parsehost($image_url, $new_url, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function tinypic($image_url)
    {
        global $config, $config_rss;
        for ($x = 0; $x < 1; $x++) {
            $rez = $this->get_host('http://tinypic.com/');
            preg_match('#<form action="http:\/\/([A-z0-9]*?)\.tinypic\.com\/upload\.php".*?id="uid" value="(.*?)".*?name="upk" value="(.*?)"#is', $rez, $out);
            $url  = 'http://' . $out[1] . '.tinypic.com/upload.php';
            $fg   = 'UPLOAD_IDENTIFIER=' . $out[2] . '&upk=' . $out[3] . '&domain_lang=en&action=upload&MAX_FILE_SIZE=500000000&shareopt=true&url=' . $image_url . '&file_type=url&dimension=1600&video-settings=sd';
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?>!i', $data, $out);
                $new_url = 'http://i' . $out[1][2] . '.tinypic.com/' . $out[1][0];
                $pr      = '1';
                if (intval($out[1][2]) != 0 and $out[1][0] != '') {
                    $this->parsehost($image_url, $new_url, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function ambrybox($image_url)
    {
        global $config, $config_rss;
        if ($config_rss['water_ambrybox'] == 'yes' and $config_rss['post_ambrybox'] == '') {
            $water = '&string=true&string_text=' . $this->get_url(($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url']));
        } else {
            $water = '&string=true&string_text=' . convert('cp1251', 'utf-8', $config_rss['post_ambrybox']);
        }
        for ($x = 0; $x < 1; $x++) {
            $url  = 'http://i1.ambrybox.com/_scripts/';
            $fg   = 'quality=85&upurl=' . $image_url . $water;
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?>!i', $data, $out);
                $new_url = 'http://i1.ambrybox.com/' . $out[1][5] . '/' . $out[1][0];
                $pr      = '1';
                if ($out[1][0] != '' and $out[1][5] != '') {
                    $this->parsehost($image_url, $new_url, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function shituf($image_url)
    {
        global $config, $config_rss;
        for ($x = 0; $x < 1; $x++) {
            $url  = 'http://shituf.org/inc/uploaderurl.php';
            $fg   = "urls=" . $image_url . "&thumb_size=500";
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all("!upload\('.*?','.*?','.*?','.*?','.*?','\|(.*?)','.*?','\|(.*?)','.*?','.*?','.*?','.*?'\)!i", $data, $out);
                $pr = '1';
                if ($out[1][0] != '') {
                    $this->parsehost($image_url, $out[1][0], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function fastpic($image_url)
    {
        $im_url = $image_url;
        if (preg_match('#(https)#i', $im_url))
            $image_url = str_replace('https', 'http', $image_url);
        global $config, $config_rss;
        if ($config_rss['water_fastpic'] == 'yes' and trim($config_rss['post_fastpic']) == '') {
            $water = '&check_thumb=text&thumb_text=' . $this->get_url($config['http_home_url']);
        } else {
            $water = '&check_thumb=text&thumb_text=' . convert('cp1251', 'utf-8', $config_rss['post_fastpic']);
        }
        if ($config_rss['water_fastpic'] == 'no')
            $water = '&check_thumb=size';
        for ($x = 0; $x < 1; $x++) {
            $url = 'http://fastpic.ru/upload_copy?api=1';
            if (intval($config_rss['thumb_fastpic']) == 0)
                $config_rss['thumb_fastpic'] = $this->max_image;
            $fg   = "files=" . $image_url . "&uploading=1&orig_rotate=0&thumb_size=" . $config_rss['thumb_fastpic'] . $water;
            $data = $this->download_host($url, $fg);
            if (preg_match("!<status>ok<\/status>!i", $data)) {
                preg_match('!<imagepath>(.*?)</imagepath>!i', $data, $out);
                if ($config_rss['url_fastpic'] == '' or $config_rss['url_fastpic'] == '1') {
                    $new_url = $out[1];
                    $pr      = '1';
                } else {
                    preg_match('!<thumbpath>(.*?)</thumbpath>!i', $data, $ouut);
                    $new_url = '[url=' . $out[1] . '][img]' . $ouut[1] . '[/img][/url]';
                    $pr      = '2';
                }
                if ($out[1] != '') {
                    if (preg_match('#(https)#i', $im_url))
                        $image_url = str_replace('http', 'https', $image_url);
                    $this->parsehost($image_url, $new_url, $pr);
                    if (!(in_array($new_url, $this->image))) {
                        $this->imagess[] = $new_url;
                    }
                    return true;
                }
            }
        }
        return false;
    }
    function fotonons($image_url)
    {
        global $config, $config_rss;
        for ($x = 0; $x < 1; $x++) {
            $url  = 'http://fotonons.ru/';
            $fg   = "remota=" . $image_url;
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                if ($config_rss['url_fotonons'] == '' or $config_rss['url_fotonons'] == '1') {
                    preg_match('!<input tabindex="5"value="(.*?)".*?>!i', $data, $out);
                    $pr = '1';
                } else {
                    preg_match('!<input tabindex="2"value="(.*?)".*?>!i', $data, $out);
                    $pr = '2';
                }
                if ($out[1] != '') {
                    $this->parsehost($image_url, $out[1], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function rezerv($image_url)
    {
        global $config, $config_rss;
        for ($x = 0; $x < 1; $x++) {
            $rez = $this->get_host('http://tinypic.com/');
            preg_match('#<form action="http:\/\/([A-z0-9]*?)\.tinypic\.com\/upload\.php".*?id="uid" value="(.*?)".*?name="upk" value="(.*?)"#is', $rez, $out);
            $url  = 'http://' . $out[1] . '.tinypic.com/upload.php';
            $fg   = 'UPLOAD_IDENTIFIER=' . $out[2] . '&upk=' . $out[3] . '&domain_lang=en&action=upload&MAX_FILE_SIZE=500000000&shareopt=true&url=' . $image_url . '&file_type=url&dimension=1600&video-settings=sd';
            $data = $this->download_host($url, $fg);
            if ($data != '') {
                preg_match_all('!<input.*?value="(.*?)".*?>!i', $data, $out);
                $new_url = 'http://i' . $out[1][2] . '.tinypic.com/' . $out[1][0];
                $pr      = '1';
                if (intval($out[1][2]) != 0) {
                    $this->parsehost($image_url, $new_url, $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function rezerv_host($naw, $download)
    {
        global $config;
        $eror = array();
        $i    = 0;
        foreach ($naw as $image_url) {
            unset($rz);
            ++$i;
            if (check_url($image_url) == true) {
                for ($x = 0; $x < 1; $x++) {
                    if ($this->serv($image_url, $i) == false) {
                        $rz = $image_url;
                    } else {
                        unset($rz);
                        break;
                    }
                }
            } else {
                $rz = $image_url;
            }
            if (isset($rz) == true)
                $eror[] = $rz;
        }
        $ers = $this->parseserv($this->image, true);
        if (count($eror) != 0)
            $eror = array_unique(array_merge($eror, $ers));
        if ($download != 'serv' and $download != '0') {
            foreach ($this->upload_images as $key => $image_name) {
                unset($rz);
                if (@filesize($this->img_orig . $image_name) != 0 and $download != '0') {
                    $image_url = ($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url']) . 'uploads/posts' . $this->post . $this->pap_data . '/' . $image_name;
                    if ($this->$download($image_url) == false) {
                        $rz = $image_url;
                    } else {
                        @unlink($this->img_orig . $image_name);
                        unset($this->upload_images[$key]);
                    }
                } else {
                    $rz = $image_url;
                }
                if (isset($rz) == true)
                    $eror[] = $rz;
            }
        }
        return $eror;
    }
    function picp2($image_url)
    {
        global $config, $config_rss;
        if ($this->serv($image_url, 1) == false)
            return false;
        $kart          = key($this->image);
        $file          = $this->img_orig . $kart;
        $imageSizeInfo = @getimagesize($file);
        if ($imageSizeInfo[2] == '1') {
            $imageType = 'gif';
        }
        if ($imageSizeInfo[2] == '2') {
            $imageType = 'jpg';
        }
        if ($imageSizeInfo[2] == '3') {
            $imageType = 'png';
        }
        if ($imageSizeInfo[2] == '18') {
            $imageType = 'webp';
        }
        if ($imageSizeInfo[0] < $this->max_image) {
            $size_img = $imageSizeInfo[0];
        } elseif ($imageSizeInfo[1] < $this->max_image) {
            $size_img = $imageSizeInfo[1];
        } else {
            $size_img = $this->max_image;
        }
        $post               = array();
        $post['FILE']       = '@' . $file . ';type=image/' . $imageType;
        $post['psz']        = $size_img;
        $post['psize']      = $size_img;
        $post['pol']        = $config_rss['picp2_pol'];
        $post['tpr']        = '';
        $post['image_name'] = '';
        if ($config_rss['picp2_water'] == 'yes' and $config_rss['picp2_tprev'] == '') {
            $config_rss['picp2_tprev'] = $this->get_url(($config_rss['http_url'] != '' ? $config_rss['http_url'] : $config['http_home_url']));
        } else {
            $post['tprev'] = $config_rss['picp2_tprev'];
        }
        $post['ad']   = $config_rss['picp2_ad'];
        $post['prev'] = $config_rss['picp2_prev'];
        $post['cu']   = $config_rss['picp2_cu'];
        $cookie_file  = ENGINE_DIR . '/cache/system/picp2.txt';
        $fg           = "email=" . $config_rss['picp2_email'] . "&pass=" . $config_rss['picp2_pass'];
        curl_autoriz('http://picp2.com/log/', $fg, $cookie_file, 'http://picp2.com/login/');
        $data = $this->download_host_obz('http://picp2.com/cabinet/upl/', $post, $cookie_file);
        if ($data != '') {
            if ($config_rss['url_fotonons'] == '' or $config_rss['url_fotonons'] == '1') {
                preg_match('!<input name="c13".*?value="(.*?)".*?>!is', $data, $out);
            } else {
                preg_match('!<td>3.*?<input name="c12".*?value="(.*?)".*?>!is', $data, $out);
            }
            if ($out[1] != '') {
                $this->parsehost($image_url, $out[1], 2);
                unset($this->image[$kart]);
                return true;
            }
        }
        unset($this->image[$kart]);
        return false;
    }
    function imgaws($image_url)
    {
        global $config_rss, $config;
        $cookie_file       = ENGINE_DIR . '/cache/system/imgaws.txt';
        $data["usr_email"] = $config_rss['imgaws_log'];
        $data["pwd"]       = $config_rss['imgaws_pass'];
        $data["doLogin"]   = "Login";
        $data["remember"]  = "1";
        $data["action"]    = "login.php";
        $fg                = http_build_query($data);
        $avt               = curl_autoriz('https://imgaws.com/login.php', $fg, $cookie_file, 'https://imgaws.com/');
        //echo'<textarea style="width:100%;height:240px;">'.$avt.'</textarea>';
        //echo $fg.'123';
        for ($x = 0; $x < 1; $x++) {
            $dta["remote_upload"]        = $image_url;
            $dta["adult"]                = $config_rss['imgaws_adult'];
            $dta["thumb_size_contaner"]  = "2";
            $dta["single_remote_upload"] = "Upload";
            $dta["download_links"]       = "";
            $dta["action"]               = "upload.php";
            $post                        = http_build_query($dta);
            //echo $post.'123';
            $data                        = $this->download_host_obz('https://imgaws.com/upload.php', $post, $cookie_file);
            //echo'<textarea style="width:100%;height:240px;">'.$data.'</textarea>';
            if ($data != '') {
                if (!empty($config_rss['url_imgaws'])) {
                    preg_match('!<div id="uploadedimage">.*?src="(.*?)"!is', $data, $out);
                    $out[1] = str_replace("upload/small/", "upload/big/", $out[1]);
                    $pr     = '1';
                } else {
                    preg_match('!<div id="uploadcodes">.*?value="(.*?)"!is', $data, $out);
                    $pr = '2';
                }
                if ($out[1] != '') {
                    $this->parsehost($image_url, $out[1], $pr);
                    return true;
                }
            }
        }
        return false;
    }
    function download_host_obz($url, $par, $cookie_file = '')
    {
        global $config_rss;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $par);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        if ($cookie_file != '')
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        if ($GLOBALS['proxy'] == 1) {
            if ($config_rss['proxy_file'] == 'yes' or $config_rss['proxy'] == '') {
                $proxy_url = @file(ENGINE_DIR . '/inc/plugins/files/proxy.txt');
                $proxy_url = $proxy_url[array_rand($proxy_url)];
            } else {
                $proxy_url = $config_rss['proxy'];
            }
            if (trim($proxy_url) != '') {
                $data_proxy = explode("@", trim($proxy_url));
                if (count($data_proxy) == 3) {
                    curl_setopt($ch, CURLOPT_PROXY, $data_proxy[1]);
                    if (!empty($data_proxy[1]))
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data_proxy[0]);
                    if (!empty($data_proxy[2]))
                        curl_setopt($ch, CURLOPT_PROXYTYPE, $data_proxy[2]);
                } else {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
                }
            }
        }
        if (preg_match('#(https)#i', $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, get_random_agent());
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        $otvet_ot_server = curl_exec($ch);
        curl_close($ch);
        return $otvet_ot_server;
    }
}
function server_host($selected)
{
    global $options_host, $lang_grabber;
    $output = '';
    if ($selected == '0')
        $selected = 'donor';
    foreach ($options_host as $value => $description) {
        if ($value == '0')
            $value = 'donor';
        $output .= "<option value=\"$value\"";
        if ($selected == $value) {
            if ($value == 'donor')
                $value = '0';
            $output .= ' selected ';
        }
        if ($value == '0') {
            $output .= ' style="color:blue" ';
        } elseif ($value == 'serv') {
            $output .= ' style="color:green" ';
        } else {
            $output .= ' style="color:red" ';
        }
        $output .= ">$description</option>\n";
    }
    return $output;
}
function java_host()
{
    global $options_host;
    $output = "  <script type=\"text/javascript\">
    function onImgChange(value) {
ShowOrHideEx(\"0\", value == \"0\");\n";
    foreach ($options_host as $value => $description) {
        $output .= "ShowOrHideEx(\"" . $value . "\", value == \"" . $value . "\");\n";
    }
    $output .= '};
</script>';
    return $output;
}
function check_url($url, $proxy = 0)
{
    return true;
}
function chmod_pap($file)
{
    global $config_rss;
    if (intval($config_rss['chmod_pap']) == 0) {
        if (file_exists($file)) {
            if (is_writable($file)) {
                return true;
            } else {
                @chmod($file, 0755);
                if (is_writable($file)) {
                    return true;
                } else {
                    @chmod($file, 0777);
                    if (is_writable($file)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }
    if (file_exists($file)) {
        if ($config_rss['chmod_pap'] == 1 and @chmod($file, 0777))
            return true;
        if ($config_rss['chmod_pap'] == 2 and @chmod($file, 0755))
            return true;
    } else {
        return false;
    }
    return false;
}
function chmod_file($file)
{
    global $config_rss;
    if (intval($config_rss['chmod_file']) == 0) {
        if (@file_exists($file)) {
            if (is_writable($file)) {
                return true;
            } else {
                @chmod($file, 0644);
                if (is_writable($file)) {
                    return true;
                } else {
                    @chmod($file, 0666);
                    if (is_writable($file)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }
    if (@file_exists($file)) {
        if ($config_rss['chmod_file'] == 1 and @chmod($file, 0666)) {
            if (is_writable($file))
                return true;
        }
        if ($config_rss['chmod_file'] == 2 and @chmod($file, 0644)) {
            if (is_writable($file))
                return true;
        }
        return false;
    } else {
        return false;
    }
}
function info_host($url)
{
    global $config_rss;
    if ($GLOBALS['full_news_link'] == '')
        $news_link = $url;
    $news_link   = $GLOBALS['full_news_link'];
    $prov_url    = reset_url($news_link);
    $cookie_file = ENGINE_DIR . '/cache/system/' . $prov_url . '.txt';
    if (!file_exists($cookie_file))
        $cookie_file = ENGINE_DIR . '/cache/system/www.' . $prov_url . '.txt';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, str_replace(' ', '%20', $url));
    curl_setopt($ch, CURLOPT_USERAGENT, "Opera/10.00 (Windows NT 5.1; U; ru) Presto/2.2.0");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $news_link);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    if (@file_exists($cookie_file))
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    if ($GLOBALS['proxy'] == 1) {
        if ($config_rss['proxy_file'] == 'yes' or $config_rss['proxy'] == '') {
            $proxy_url = @file(ENGINE_DIR . '/inc/plugins/files/proxy.txt');
            $proxy_url = $proxy_url[array_rand($proxy_url)];
        } else {
            $proxy_url = $config_rss['proxy'];
        }
        if (trim($proxy_url) != '') {
            $data_proxy = explode("@", trim($proxy_url));
            if (count($data_proxy) == 3) {
                curl_setopt($ch, CURLOPT_PROXY, $data_proxy[1]);
                if (!empty($data_proxy[1]))
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data_proxy[0]);
                if (!empty($data_proxy[2]))
                    curl_setopt($ch, CURLOPT_PROXYTYPE, $data_proxy[2]);
            } else {
                curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
            }
        }
    }
    if (preg_match('#(https)#i', $url)) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    @curl_setopt($ch, CURLOPT_NOBODY, 1);
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $info;
}
function curl_redirect($ch, $cookie_file = false)
{
    global $config_rss;
    $loops     = 0;
    $max_loops = 10;
    if ($loops++ >= $max_loops) {
        $loops = 0;
        return FALSE;
    }
    $data = curl_exec($ch);
    $temp = $data;
    list($header, $data) = explode("\n\n", $data, 2);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http == 301 || $http == 302) {
        $matches = array();
        preg_match("#ocation:(.*?)\n#", $header, $matches);
        $url = preg_replace("#^\/\/#", "http://", trim(array_pop($matches)));
        $url = @parse_url($url);
        // print_r($url);
        if (!$url) {
            $loops = 0;
            return $data;
        }
        $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
        if (!$url['scheme'])
            $url['scheme'] = $last_url['scheme'];
        if (!$url['host'])
            $url['host'] = $last_url['host'];
        if (!$url['path'])
            $url['path'] = $last_url['path'];
        $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query'] ? '?' . $url['query'] : '');
        if ($GLOBALS['proxy'] == 1) {
            if ($config_rss['proxy_file'] == 'yes' or $config_rss['proxy'] == '') {
                $proxy_url = @file(ENGINE_DIR . '/inc/plugins/files/proxy.txt');
                $proxy_url = $proxy_url[array_rand($proxy_url)];
            } else {
                $proxy_url = $config_rss['proxy'];
            }
            if (trim($proxy_url) != '') {
                $data_proxy = explode("@", trim($proxy_url));
                if (count($data_proxy) == 3) {
                    curl_setopt($ch, CURLOPT_PROXY, $data_proxy[1]);
                    if (!empty($data_proxy[1]))
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data_proxy[0]);
                    if (!empty($data_proxy[2]))
                        curl_setopt($ch, CURLOPT_PROXYTYPE, $data_proxy[2]);
                } else {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
                }
            }
        }
        if (preg_match('#(https)#i', $new_url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        if (@file_exists($cookie_file))
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_URL, str_replace(' ', '%20', $new_url));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($ch, CURLOPT_NOBODY, 1);
        return curl_redirect($ch);
    }
}
function bmp2gd($src, $dest = false)
{
    if (!($src_f = fopen($src, "rb"))) {
        return false;
    }
    if (!($dest_f = fopen($dest, "wb"))) {
        return false;
    }
    $header = unpack("vtype/Vsize/v2reserved/Voffset", fread($src_f, 14));
    $info   = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant", fread($src_f, 40));
    extract($info);
    extract($header);
    if ($type != 0x4D42) {
        return false;
    }
    $palette_size = $offset - 54;
    $ncolor       = $palette_size / 4;
    $gd_header    = "";
    $gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
    $gd_header .= pack("n2", $width, $height);
    $gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
    if ($palette_size) {
        $gd_header .= pack("n", $ncolor);
    }
    $gd_header .= "\xFF\xFF\xFF\xFF";
    fwrite($dest_f, $gd_header);
    if ($palette_size) {
        $palette    = fread($src_f, $palette_size);
        $gd_palette = "";
        $j          = 0;
        while ($j < $palette_size) {
            $b = $palette[$j++];
            $g = $palette[$j++];
            $r = $palette[$j++];
            $a = $palette[$j++];
            $gd_palette .= "$r$g$b$a";
        }
        $gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
        fwrite($dest_f, $gd_palette);
    }
    $scan_line_size  = (($bits * $width) + 7) >> 3;
    $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;
    for ($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
        fseek($src_f, $offset + (($scan_line_size + $scan_line_align) * $l));
        $scan_line = fread($src_f, $scan_line_size);
        if ($bits == 24) {
            $gd_scan_line = "";
            $j            = 0;
            while ($j < $scan_line_size) {
                $b = $scan_line[$j++];
                $g = $scan_line[$j++];
                $r = $scan_line[$j++];
                $gd_scan_line .= "\x00$r$g$b";
            }
        } elseif ($bits == 8) {
            $gd_scan_line = $scan_line;
        } elseif ($bits == 4) {
            $gd_scan_line = "";
            $j            = 0;
            while ($j < $scan_line_size) {
                $byte = ord($scan_line[$j++]);
                $p1   = chr($byte >> 4);
                $p2   = chr($byte & 0x0F);
                $gd_scan_line .= "$p1$p2";
            }
            $gd_scan_line = substr($gd_scan_line, 0, $width);
        } elseif ($bits == 1) {
            $gd_scan_line = "";
            $j            = 0;
            while ($j < $scan_line_size) {
                $byte = ord($scan_line[$j++]);
                $p1   = chr((int) (($byte & 0x80) != 0));
                $p2   = chr((int) (($byte & 0x40) != 0));
                $p3   = chr((int) (($byte & 0x20) != 0));
                $p4   = chr((int) (($byte & 0x10) != 0));
                $p5   = chr((int) (($byte & 0x08) != 0));
                $p6   = chr((int) (($byte & 0x04) != 0));
                $p7   = chr((int) (($byte & 0x02) != 0));
                $p8   = chr((int) (($byte & 0x01) != 0));
                $gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
            }
            $gd_scan_line = substr($gd_scan_line, 0, $width);
        }
        fwrite($dest_f, $gd_scan_line);
    }
    fclose($src_f);
    fclose($dest_f);
    return true;
}
if (!function_exists('ImageCreateFromBmp')) {
    function ImageCreateFromBmp($filename)
    {
        $tmp_name = tempnam("/tmp", "GD");
        if (bmp2gd($filename, $tmp_name)) {
            $img = imagecreatefromgd($tmp_name);
            unlink($tmp_name);
            return $img;
        }
        return false;
    }
}
class torrentUploadCustom
{
    var $news_id = 0;
    function upl($uploaded_filename, $autor)
    {
        global $db, $config;
        $member_id['name'] = $autor;
        define('STANDART_UPL', true);
        define('NEW_UPLOADER', true);
        $db->query("INSERT INTO " . PREFIX . "_files (news_id, author) values ('0', '{$member_id['name']}')");
        $id = $this->id_upl = $db->insert_id();
        include ENGINE_DIR . "/modules/tracker/upload.php";
        return $img_result;
    }
}
function set_varss($file, $data)
{
    $file = totranslit($file, true, false);
    if (is_array($data) OR is_int($data)) {
        file_put_contents(ENGINE_DIR . '/inc/plugins/files/' . $file . '.php', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        @chmod(ENGINE_DIR . '/inc/plugins/files/' . $file . '.php', 0666);
    }
}
function get_varss($file)
{
    $file = totranslit($file, true, false);
    $data = @file_get_contents(ENGINE_DIR . '/inc/plugins/files/' . $file . '.php');
    if ($data !== false) {
        $data = json_decode($data, true);
        if (is_array($data) OR is_int($data))
            return $data;
    }
    return false;
}