<?php

$xreplace = array();

$ds = explode('|||', $channel_info['xfields_template']);
foreach ($ds as $xvalue) {
    $xf = array();
    $xf = explode('==', $xvalue);
    if (empty($xfields_array[$xf[0]]) and trim($xf[10]) != '')
        $xfields_array[$xf[0]] = $xf[10];
    $xreplace[$xf[0]] = $xf;
}

if ($xfields_array)
    $fieldvalue = array();
if (count($xfields_array) != 0) {
    foreach ($xfields_array as $key => $value) {
		// if ($key == 'sezony') {
			// echo "\r\n#__xfields_array#" . $key . "\r\n";
			// echo "\r\n#__xfields_array#" . $value . "\r\n";
		// }
		
        if (empty($value))
            $value = $xreplace[$key][10];
        if ($key != 'content_story' or $key != 'content0_story') {
            if (array_key_exists($key, $xreplace)) {
                $xreplace[$key][5] = str_replace("@@@", "|||", $xreplace[$key][5]);
                $xreplace[$key][6] = str_replace("@@@", "|||", $xreplace[$key][6]);
                $xreplace_key      = str_replace('{zagolovok}', $news_title, $xreplace[$key][6]);
                $xreplace_key      = str_replace('{link}', $news_link, $xreplace_key);
                foreach ($zhv_code as $k_zh => $v_zh) {
                    $xreplace_key = str_replace('{frag' . $k_zh . '}', $v_zh, $xreplace_key);
                    $xreplace_key = str_replace('{frag}', $v_zh, $xreplace_key);
                }
                $value = relace_news($value, $xreplace[$key][5], $xreplace_key);
                $value = relace_news($value, $xreplace[$key][5], $xreplace[$key][6]);
				$value = str_replace('{nogoogle}', "", $value);
				$value = str_replace('{google}', "", $value);
				
				
                if (!preg_match("#({frag|{zagolovok}|{link})#is", $value) and $xreplace[$key][10] != "{nogoogle}" ) {
                    if (($dop_sort[13] == 1 and intval($config_rss['dop_trans']) == 0) or $xreplace[$key][10] == "{google}") {
                        if ($dop_sort[18] != '') {
                            $value = rss_strip(translate_google($value, $dop_sort[14], $dop_sort[15]));
                            $value = rss_strip(translate_google($value, $dop_sort[15], $dop_sort[18]));
                        } else {
                            $value = rss_strip(translate_google($value, $dop_sort[14], $dop_sort[15]));
                        }
                    }
                    
                }
            }
            			
            if ($dop_sort[9] != 0) {
                $value = trim(preg_replace('/[\r\n\t]+/', ' ', $value));
                $value = trim(preg_replace("#(<br \/>|<br>)\s+(\S)#", '\\1\\2', $value));
                $value = trim(preg_replace('/\s+/', ' ', $value));
            }
            
            $value = parse_Thumb($value);
            $value = parse_rss($value);
            $value = $parse->decodeBBCodes($value, false);
            $value = rss_strip($value);
            $value = strip_tags_smart($value, '<object><embed><param>' . $dop_sort[5]);
            $value = preg_replace('#&quot;#', '"', $value);
            $value = create_URL($value, $link['host']);
            if ($xreplace[$key][4] == 1)
                $value = '[img]' . $value . '[/img]';
            $value = parse_host($value, $link['host'], $link['path']);
            if ($xreplace[$key][4] == 1) {
                $value = str_replace('[img]', '', $value);
                $value = str_replace('[/img]', '', $value);
            }
            
            if ($dop_nast[1] == 1) {
                $value = preg_replace("#(^|\s|>)((http://|https://|ftp://)\w+[^<\s\[\]]+)#i", "\\1[url]\\2[/url]", $value);
            }
            if ($dop_nast[1] == 2) {
                $value = preg_replace_callback('#\[url=(.+?)\](.+?)\[\/url\]#i', "downs_host", $value);
            }
            if ($dop_nast[1] == 3) {
                $value = preg_replace_callback('#\[url=(.+?)\](.+?)\[\/url\]#i', "downs_host", $value);
            }
            $value = replace_leech($value);
            if ($dop_nast[10] == 1) {
                $value = trim(preg_replace('/[\r\n\t ]{3,}/', '
', $value));
            }
            
            
            if (intval($xreplace[$key][8]) != 0 and trim($value) != '') {
                if ($dop_nast[24] != '')
                    $kones = $dop_nast[24];
                else
                    $kones = ' ';
                $kol_b     = '';
                $full_stor = $value;
                $bb_d      = array();
                $bb_dd     = array();
                $dop_kon   = strpos(e_sub($full_stor, $xreplace[$key][8]), $kones);
                $short_sto = e_sub($full_stor, 0, $xreplace[$key][8]);
                preg_match_all('#\[(img|thumb)\].*?\[\/(img|thumb)\]#is', $full_sto, $bb_d);
                if (count($bb_d[0]) != 0) {
                    foreach ($bb_d[0] as $eh) {
                        $kol_b += e_str($eh);
                    }
                }
                preg_match_all('#[.*?]#i', $full_sto, $bb_dd);
                if (count($bb_dd[0]) != 0) {
                    foreach ($bb_dd[0] as $eh) {
                        $kol_b += e_str($eh);
                    }
                }
                $xreplace[$key][8] += $kol_b;
                $dop_kon = strpos(e_sub($full_stor, $xreplace[$key][8]), $kones);
                $nach    = $xreplace[$key][8] + $dop_kon + 1;
                if (intval($xreplace[$key][8]) != 0)
                    $value = e_sub($full_stor, 0, $nach) . '...';
                else
                    $value = e_sub($full_stor, 0, strpos(e_sub($full_stor, $xreplace[$key][8]), $kones)) . '...';
                $value = preg_replace('#<\S+\.\.\.#', '...', $value);
                $value = preg_replace('#\.\s\.\.\.#', '...', $value);
                $value = preg_replace('#,\s\.\.\.#', '...', $value);
                $value = str_replace('....', '...', $value);
                if (trim($value, '., ') == '')
                    $value == '';
            }
            $value = str_replace('{zagolovok}', $news_title, $value);
            $value = str_replace('{link}', $news_link, $value);
            foreach ($zhv_code as $k_zh => $v_zh) {
                $value = str_replace('{frag' . $k_zh . '}', $v_zh, $value);
                $value = str_replace('{frag}', $v_zh, $value);
            }
            
            if (preg_match("#{data(.*?)}#is", $value, $data_title)) {
                $data_title = str_replace("=", "", $data_title[1]);
                $data_value = explode(",", $data_title);
                $time_vl    = empty($data_value[1]) ? time() : parse_date($data_value[1]);
                $data_title = empty($data_value[0]) ? langdate('Y.m.d', $time_vl) : langdate($data_value[0], $time_vl);
                $value      = preg_replace("#{data(.*?)}#is", $data_title, $value);
            }
            
            // if ($key == 'sezony') {
				// echo "\r\n#__xfields_array#" . $value . "\r\n";
			// }
            
            $fieldvalue[$key] = trim($value);
        }
    }
}

if (!empty($GLOBALS['msg_tr'])) {
    unset($GLOBALS['msg_tr']);
    $i++;
}

?>