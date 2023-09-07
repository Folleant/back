<?php
unset($news_id);
$full_story = '';
if (trim($end_title[4]) != ''and trim($news_link) != '') $news_lik = relace_news ($news_link,$end_title[4],$end_title[5]);
else $news_lik = $news_link;
$news_li=$link;
for ($j=1;$j <= 20;$j++){
$stoped = false;
if (trim($channel_info['dop_full']) == ''and $j != '1'and $full_story != '')$stoped = true;
if ((trim ($start_template) != '' or trim($sart_cat[2]) != '') and !$stoped)
{
if ($dop_nast[18] == 0 ){
if (trim($channel_info['dop_full'])!= ''and $j >= 2){
$news_link = str_replace('http://','',$news_lik);
$fl = explode('/',$news_link);
$news_linke = '';
for ($k=0;$k<(count($fl)-1);$k++){
$news_linke .= $fl[$k].'/';
}
$news_linke .= str_replace('{num}',$j,$channel_info['dop_full']);
$news_link = 'http://'.$news_linke.end($fl);
}
}
else{
if ($j >= 2) $news_link = $news_lik.$channel_info['dop_full'];
$news_link= preg_replace('#\.html(.*){num}#i','\\1{num}.html',$news_link);
$news_link= str_replace('.html.html','.html',$news_link);
$news_link= str_replace('{num}',$j,$news_link);
}
if (trim($end_title[4]) != ''and trim($news_link) != '') $news_link = relace_news ($news_link,$end_title[4],$end_title[5]);
$link = replace_url($news_li(trim(rss_strip($news_link))));
$full = get_full ($link['scheme'],$link['host'],$link['path'],$link['query'],$cookies,$dop_nast[2],$dop_sort[8],$dop_sort[21]);
$link = '';
}
else
{
break;
}

if($dop_sort[9] != 0) {
$full = trim(preg_replace('/[\r\n\t]+/',' ',$full));
$full = trim(preg_replace("#(<br \/>|<br>)\s+(\S)#",'\\1\\2',$full));
$full = trim(preg_replace('/\s+/',' ',$full));
}

if (trim ($full) != ''){
if (trim($dop_nast[14]) == ''or $dop_nast[14] == '0')$charik = charset($full);else $charik = strtolower($charsets[1] != ''?$charsets[1]:$charsets[0]);
if (trim($channel_info['start_title']) != '' and $dnast[22] == 1){
$news_tit = strip_tags_smart(get_full_news($full,$channel_info['start_title']));
if ($charik != strtolower($config['charset']) and trim($news_tit) != '') {$news_tit = convert($charik,strtolower($config['charset']),$news_tit);}
if (trim($end_title[2]) != '' and trim($news_tit) != '') $news_tit = relace_news ($news_tit,$end_title[2],$end_title[3]);
}
if (trim ($start_template) != '')
{
	$tags_tty = '';
if ($charik != strtolower($config['charset']) and trim ($charik) != '') {$tags_tty = convert($charik,strtolower($config['charset']),$full);}
else $tags_tty = $full;
if ($hide_leech[0] != 1){
$full_storys = get_short_news ($full,$start_template);
}else{
$full_storys = get_full_news ($full,$start_template);
}


if (sizeof($finish_template) and trim ($full_storys) == '')
{
foreach ($finish_template as $shabz){
$full_storys = get_full_news ($full,$shabz);
if (trim($full_storys) != '')break;
}
}

if ( ($full_storys) == ''){
list($start_temp, $finish_temp) = explode("{get}",$start_template, 2);
$full_storys = get_news($full,$start_temp, $finish_temp);
}

if (preg_match("#(DOCTYPE|<html>)#is",$full_storys) or e_str($start_template) > e_str($full_storys))$full_storys = '';



if ($full_storys == '')break;
if ($full_storys != $full_story1 or $j == '1'or $full_story == '')
{
if ($j == 1){
	$full_story1 = $full_storys;
	}else{
	if(e_str($full_story1) == e_str($full_storys))break;
}
$full_story .= $full_storys;

}else{break;}
}else{break;}
}
if ($full_story == '')break;
}
if (preg_match("#DOCTYPE#is",$full_story) or e_str($start_template) > e_str($full_story))$full_story='';
if (preg_match('#<script>#i',$dop_sort[5]))
	{
preg_match_all ("#(<script.+?>.+?<\/script>)#is",$full_story, $scrip);
if (count($scrip[0])){
foreach ($scrip[0] as $k_s=>$s_v){
$template = addcslashes(stripslashes($s_v),"[]!-.#?*%+\\()|");
$full_story = preg_replace("#".$template."#is", "[skpipt".$k_s."]",$full_story);

}

}

}

if(empty($full_story) and !empty($item['full-text']) )$full_story = $item['full-text'];


if (preg_match('#<(code|pre)>#i',$dop_sort[5]))
	{
preg_match_all ("#(<(code|pre).+?>.+?<\/(code|pre)>)#is",$full_story, $pre);
if (count($pre[0])){
foreach ($pre[0] as $k_s=>$s_v){
$template_pre = addcslashes(stripslashes($s_v),"[]!-.#?*%+\\()|");
$full_story = preg_replace("#".$template_pre."#is", "[pre".$k_s."]",$full_story);

}

}

}


$news_link = $news_lik;
if ($charik != strtolower($config['charset']) and trim ($full_story) != ''and trim ($charik) != '') {$full_story = convert($charik,strtolower($config['charset']),$full_story);}

if (trim($channel_info['start']) != ''){
$row_finish = str_replace('{link}',$news_link,$channel_info['finish']);	
	
if (preg_match("#{full}#",$channel_info['start'])){
$del_gl = explode ('|||',$channel_info['start']);
if (!empty($row_finish))$ins_gl = explode ('|||',$row_finish);
	 foreach($del_gl as $k_gl=>$v_gl){
		 if (preg_match("#{full}#",$v_gl)){
$full = relace_news ($full,$v_gl,$ins_gl[$k_gl]);
$tags_tty = relace_news ($tags_tty,$v_gl,$ins_gl[$k_gl]);
$item = relace_news ($item,$v_gl,$ins_gl[$k_gl]);
		 }
	 }
  }
$full_story = relace_news ($full_story,$channel_info['start'],$row_finish,2);
$short_story = relace_news ($short_story,$channel_info['start'],$row_finish,1);

if (trim ($sart_cat[0]) != '' or trim ($sart_cat[1]) != ''){
$sart_cat[1] = relace_news ($sart_cat[1],$channel_info['start'],$row_finish);
$sart_cat[0] = relace_news ($sart_cat[0],$channel_info['start'],$row_finish);
}
}



if (trim ($sart_cat[1]) != ''){
	
	if($data_tmp_map != ''){
	$data_tmp = get_full_news ($data_tmp_map, $news_link."{skip}<lastmod>{get}</lastmod>");
	}else{
		$data_tmp =strip_tags_smart(get_full_news(var_export($item, true).$short_story.$tags_tty,$sart_cat[1]));
		}
if (trim ($data_tmp) == ''){
	$data_tmp =strip_tags_smart(get_full_news($full,$sart_cat[1]));
}
	}

$cat_tag = explode("\n", $sart_cat[0]);

if (trim($cat_tag[0]) != '') {
    $tags_tmp = get_full_news(var_export($item, true).$short_story.$tags_tty, $cat_tag[0], true);
    $tags_tmp = preg_replace( "#<.*?>#is", ",", $tags_tmp );
	$tags_tmp = strip_tags_smart($tags_tmp);
    if (trim($tags_tmp, ", ") == '') {
        if ($charik != strtolower($config['charset']) and trim($cat_tag[0]) != '' and trim($charik) != '') {$cat_tag[0] = convert(strtolower($config['charset']), $charik, $cat_tag[0]);}
        $tags_tmp = get_full_news($full, $cat_tag[0], true);
        if ($charik != strtolower($config['charset']) and trim($tags_tmp) != '' and trim($charik) != '') {$tags_tmp = convert($charik, strtolower($config['charset']), $tags_tmp);}
    
    }
    
    $tags_tmp    = preg_replace("#\[del-tags\].*\[\/del-tags\]#iUs", "", $tags_tmp);
    $full_story  = preg_replace("#\[del-tags\](.*)\[\/del-tags\]#iUs", "\\1", $full_story);
    $short_story = preg_replace("#\[del-tags\](.*)\[\/del-tags\]#iUs", "\\1", $short_story);
    $full        = preg_replace("#\[del-tags\](.*)\[\/del-tags\]#iUs", "\\1", $full);
}

if ($cat_tag[1] != '') {
    
    $cat_tmp = strip_tags_smart(get_full_news(stripslashes(var_export($item, true)).$short_story . $tags_tty, $cat_tag[1], true));
    
    if (trim($cat_tmp, ", ") == '') {
        if ($charik != strtolower($config['charset']) and trim($cat_tag[1]) != '' and trim($charik) != '') {$cat_tag[1] = convert(strtolower($config['charset']), $charik, $cat_tag[1]);}
        $cat_tmp = strip_tags_smart(get_full_news($full, $cat_tag[1], true));
        if ($charik != strtolower($config['charset']) and trim($cat_tmp) != '' and trim($charik) != '') {$cat_tmp = convert($charik, strtolower($config['charset']), $cat_tmp);}
    }
}

$xfields_ren = array();
if ($dop_sort[25] == 1 and $hide_leech[3] == 1) {
    while ($ren = $db->get_row($sql_result)) {
        $xfields_rew = explode("||", $ren['xfields']);
        if (!empty($xfields_rew)) {
            foreach ($xfields_rew as $value) {
                list($xfielddataname, $xfielddatavalue) = explode("|", $value);
                if ($xfielddatavalue == "") {
                    continue 1;
                }
                $xfields_ren[$xfielddataname] = $xfielddatavalue;
            }
        }
    }
}

if (@file_exists (ENGINE_DIR .'/inc/plugins/include/parse_dop.php')) include ENGINE_DIR .'/inc/plugins/include/parse_dop.php';

if (trim($channel_info['xfields_template']) != '')
{
$xfields_array = get_xfields (rss_strip($full_story),$short_story,$channel_info['xfields_template'],$xfields_ren,$tags_tty);
$full_story = $xfields_array['content_story'];
unset($xfields_array['content_story']);
$short_story = $xfields_array['content0_story'];
unset($xfields_array['content0_story']);

}
$link = replace_url($news_li(trim(rss_strip($news_link))));
$full_story = html_strip ($full_story);
if (trim($news_tit) == ''and trim ($full) != '')
{
$news_tit = get_title($full);
if ($charik != strtolower($config['charset']) and trim($news_tit) != '') {$news_tit = convert($charik,strtolower($config['charset']),$news_tit);}
if (trim($end_title[2]) != '' and trim($news_tit) != '') $news_tit = relace_news ($news_tit,$end_title[2],$end_title[3]);
}
if (trim($news_tit) == '' and trim ($full_story) != ''){
$news_tit = get_tit($short_story.$full_story);
if ($charik != strtolower($config['charset']) and trim($news_tit) != '') {$news_tit = convert($charik,strtolower($config['charset']),$news_tit);}
if (trim($end_title[2]) != '' and trim($news_tit) != '') $news_tit = relace_news ($news_tit,$end_title[2],$end_title[3]);
}
if($dop_sort[9] != 0) {
$full_story = trim(preg_replace('/[\r\n\t]+/',' ',$full_story));
$short_story = trim(preg_replace('/[\r\n\t]+/',' ',$short_story));
$full_story = trim(preg_replace("#(<br \/>|<br>)\s+(\S)#",'\\1\\2',$full_story));
$short_story = trim(preg_replace("#(<br \/>|<br>)\s+(\S)#",'\\1\\2',$short_story));
$full_story = trim(preg_replace('/\s+/',' ',$full_story));
$short_story = trim(preg_replace('/\s+/',' ',$short_story));
}


if ((trim($news_tit) == '' or intval($dnast[33]) == 1) and trim($short_story != '')){
$tit_kon = strpos(e_sub( strip_tags_smart ($short_story) ,"50"), " ");
$tit_nach = "50" + $tit_kon + 1;
$news_tit = e_sub( strip_tags ($short_story) ,0,$tit_nach);
if (trim($end_title[2]) != '' and trim($news_tit) != '') $news_tit = relace_news ($news_tit,$end_title[2],$end_title[3]);
}
$news_tit = strip_tags_smart ($news_tit);
$news_tit = trim(preg_replace("![\n\r\s]+!",' ',$news_tit));


if(empty($short_story) and !empty($full) and !preg_match("#(og\:image|og\:description)#is", $sart_cat[2]) and empty($dop_nast[22])){

$short_story_im = get_full_news($full, '<meta property="og:image" content="{get}"');
if (empty($short_story_im))$short_story_im = get_full_news($full, "<meta property='og:image' content='{get}'");

$short_story_dis = get_full_news($full, '<meta property="og:description" content="{get}"');
if (empty($short_story_dis))$short_story_dis = get_full_news($full, "<meta property='og:description' content='{get}'");
		  
          
        if (!empty($short_story_dis) ) {
            if ($charik != strtolower($config['charset']) and trim($short_story_dis) != '' and trim($charik) != '') {
                $short_story_dis = convert($charik, strtolower($config['charset']), $short_story_dis);
            }
		}
		
	if(!empty($short_story_im))$short_story .= "[img]".$short_story_im ."[/img]";
	$short_story .= $short_story_dis;

}

if (intval($dnast[41])=="1" and $dnast[42] != ''){$dop_sort[14] = $dnast[42];$dop_sort[15]='yan_dex';$dop_sort[18]='';$dop_sort[13] = 1;}
if($dop_sort[13] == 1) {$translate_google = translate_google (rss_strip('<{title}>'.$news_tit.'<{short}>'.trim($short_story).'<{full}>'.trim($full_story).'<{tags_tmp}>'.$tags_tmp.'<{end}>'),$dop_sort[14] ,$dop_sort[15] );
preg_match('!<{title}>(.*)<{short}>!is',$translate_google,$tran1);
preg_match('!<{short}>(.*)<{full}>!is',$translate_google,$tran2);
preg_match('!<{full}>(.*)<{tags_tmp}>!is',$translate_google,$tran3);
preg_match('!<{tags_tmp}>(.*)<{end}>!is',$translate_google,$tran4);
if (trim($tran1[1]) == '')$tran1[1] = translate_google (rss_strip($news_tit),$dop_sort[14] ,$dop_sort[15] );
if (trim($tran2[1]) == '')$tran2[1] = translate_google (rss_strip($short_story),$dop_sort[14] ,$dop_sort[15] );
if (trim($tran3[1]) == '')$tran3[1] = translate_google (rss_strip($full_story),$dop_sort[14] ,$dop_sort[15] );
if (trim($tran4[1]) == '')$tran4[1] = translate_google (rss_strip($tags_tmp),$dop_sort[14] ,$dop_sort[15] );
if (intval($dnast[34]) == 0){
if ($dnast[35] == 1 and $dop_sort[18] == ''){$news_tit = $tran1[1].'/'.$news_tit;}
else {$news_tit = $tran1[1];}
}
$short_story = $tran2[1];
$full_story = $tran3[1];
$tags_tmp = $tran4[1];
}
if($dop_sort[13] == 1 and $dop_sort[18] != '') {$translate_google = rss_strip (translate_google (rss_strip('<{title}>'.$news_tit.'<{short}>'.trim($short_story).'<{full}>'.trim($full_story).'<{tags_tmp}>'.$tags_tmp.'<{end}>'),$dop_sort[15] ,$dop_sort[18] ));
preg_match('!<{title}>(.*)<{short}>!is',$translate_google,$tran1);
preg_match('!<{short}>(.*)<{full}>!is',$translate_google,$tran2);
preg_match('!<{full}>(.*)<{tags_tmp}>!is',$translate_google,$tran3);
preg_match('!<{tags_tmp}>(.*)<{end}>!is',$translate_google,$tran4);
if (trim($tran1[1]) == '')$tran1[1] = rss_strip (translate_google (rss_strip($news_tit),$dop_sort[15] ,$dop_sort[18] ));
if (trim($tran2[1]) == '')$tran2[1] = rss_strip (translate_google (rss_strip($short_story),$dop_sort[15] ,$dop_sort[18] ));
if (trim($tran3[1]) == '')$tran3[1] = rss_strip (translate_google (rss_strip($full_story),$dop_sort[15] ,$dop_sort[18] ));
if (trim($tran4[1]) == '')$tran4[1] = rss_strip (translate_google (rss_strip($tags_tmp),$dop_sort[15] ,$dop_sort[18] ));
if (intval($dnast[34]) == 0){
if ($dnast[35] == 1){$news_tit = $tran1[1].'/'.$news_tit;}
else {$news_tit = $tran1[1];}
}
$short_story = $tran2[1];
$full_story = $tran3[1];
$tags_tmp = $tran4[1];
}
$news_tit = rss_strip($news_tit);
//if (trim($end_title[2]) != '' and trim($news_tit) != '') $news_tit = relace_news ($news_tit,$end_title[2],$end_title[3]);
srand((float)microtime() * 1000000);

preg_match_all("#\[(.+?)\]#is",$end_title[0],$end_title00);
if (count($end_title00[1]) )
	{
$end_title08 = $end_title[0];
		foreach ($end_title00[1] as $kl=>$nmb){
$end_title000 = explode('|',$nmb);
$end_title000 = $end_title000[array_rand($end_title000)];
$end_title08 = preg_replace("#\[".addcslashes($nmb,"[]!-.#?*%+\\()|")."\]#",$end_title000,$end_title08,1);
		}
$end_title0[0] = $end_title08;
}else{
$end_title0 = explode('|',$end_title[0]);
}

preg_match_all("#\[(.+?)\]#is",$end_title[1],$end_title10);
if (count($end_title10[1]))
	{
$end_title18 = $end_title[1];
	foreach ($end_title10[1] as $kl=>$nmb){
$end_title100 = explode('|',$nmb);
$end_title100 = $end_title100[array_rand($end_title100)];
$end_title18 = preg_replace("#\[".addcslashes($nmb,"[]!-.#?*%+\\()|")."\]#",$end_title100,$end_title18,1);
	}
$end_title1[0] = $end_title18;
}else{
$end_title1 = explode('|',$end_title[1]);
}
if (trim($news_tit) == '' and !empty($_GET['c'])) $news_tit = "ERROR TITLE";

$news_title =trim($end_title0[array_rand($end_title0)].' '.((preg_match("#{frag#",$end_title[0]) or preg_match("#{frag#",$end_title[1]))?'':$news_tit).' '.$end_title1[array_rand($end_title1)]);

if(preg_match("#{data(.*?)}#is",$news_title,$data_title)){
$data_title = str_replace("=", "", $data_title[1]);
$data_title = empty($data_title) ? langdate( 'Y.m.d', false ) : langdate( $data_title, false);
$news_title = preg_replace("#{data(.*?)}#is", $data_title, $news_title);
}

$alt_name = totranslit( stripslashes( trim($news_title) ),true,false );
if ($dnast[12] == 1) $channel_info['symbol'] = $catalog_url = $db->safesql(e_sub(strip_tags(stripslashes(trim( strtolower ($news_title) ) ) ), 0, $dnast[13], $config['charset'] ) );



?>