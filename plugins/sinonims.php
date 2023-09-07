<?php
function start($story)
{
    $story = strtr($story, array(
        'Й' => 'biggrab й',
        'Ц' => 'biggrab ц',
        'У' => 'biggrab у',
        'К' => 'biggrab к',
        'Е' => 'biggrab е',
        'Н' => 'biggrab н',
        'Г' => 'biggrab г',
        'Ш' => 'biggrab ш',
        'Щ' => 'biggrab щ',
        'З' => 'biggrab з',
        'Х' => 'biggrab х',
        'Ф' => 'biggrab ф',
        'В' => 'biggrab в',
        'А' => 'biggrab а',
        'П' => 'biggrab п',
        'Р' => 'biggrab р',
        'О' => 'biggrab о',
        'Л' => 'biggrab л',
        'Д' => 'biggrab д',
        'Ж' => 'biggrab ж',
        'Э' => 'biggrab э',
        'Я' => 'biggrab я',
        'Ч' => 'biggrab ч',
        'С' => 'biggrab с',
        'М' => 'biggrab м',
        'И' => 'biggrab и',
        'Т' => 'biggrab т',
        'Б' => 'biggrab б',
        'Ю' => 'biggrab ю',
        'A' => 'biggrab a',
        'B' => 'biggrab b',
        'C' => 'biggrab c',
        'D' => 'biggrab d',
        'E' => 'biggrab e',
        'F' => 'biggrab f',
        'G' => 'biggrab g',
        'H' => 'biggrab h',
        'I' => 'biggrab i',
        'J' => 'biggrab j',
        'K' => 'biggrab k',
        'L' => 'biggrab l',
        'M' => 'biggrab m',
        'N' => 'biggrab n',
        'O' => 'biggrab o',
        'P' => 'biggrab p',
        'Q' => 'biggrab q',
        'R' => 'biggrab r',
        'S' => 'biggrab s',
        'T' => 'biggrab t',
        'U' => 'biggrab u',
        'V' => 'biggrab v',
        'W' => 'biggrab w',
        'X' => 'biggrab x',
        'Y' => 'biggrab y',
        'Z' => 'biggrab z'
    ));
    $story = strtr($story, array(
        "\n" => " \n",
        ':' => ' :',
        ';' => ' ;',
        ',' => ' ,',
        '.' => ' .',
        '?' => ' ?',
        '!' => ' !',
        "\\\"" => ' #k1#',
        "\\\'" => ' #k2#',
        " \\\"" => ' #k3#',
        " \\\'" => '  #k4#',
        ')' => ' ) ',
        '(' => '( ',
        '>>' => ' >> ',
        '<<' => ' << '
    ));
    return $story;
}
function finish($story)
{
    $story = strtr($story, array(
        '  ' => ' '
    ));
    $story = str_replace('biggrab <font color="red">', '<font color="red">biggrab ', $story);
    $story = strtr($story, array(
        '  ' => ' ',
        'biggrab й' => 'Й',
        'biggrab ц' => 'Ц',
        'biggrab у' => 'У',
        'biggrab к' => 'К',
        'biggrab е' => 'Е',
        'biggrab н' => 'Н',
        'biggrab г' => 'Г',
        'biggrab ш' => 'Ш',
        'biggrab щ' => 'Щ',
        'biggrab з' => 'З',
        'biggrab х' => 'Х',
        'biggrab ф' => 'Ф',
        'biggrab в' => 'В',
        'biggrab а' => 'А',
        'biggrab п' => 'П',
        'biggrab р' => 'Р',
        'biggrab о' => 'О',
        'biggrab л' => 'Л',
        'biggrab д' => 'Д',
        'biggrab ж' => 'Ж',
        'biggrab э' => 'Э',
        'biggrab я' => 'Я',
        'biggrab ч' => 'Ч',
        'biggrab с' => 'С',
        'biggrab м' => 'М',
        'biggrab и' => 'И',
        'biggrab т' => 'Т',
        'biggrab б' => 'Б',
        'biggrab ю' => 'Ю',
        'biggrab a' => 'A',
        'biggrab b' => 'B',
        'biggrab c' => 'C',
        'biggrab d' => 'D',
        'biggrab e' => 'E',
        'biggrab f' => 'F',
        'biggrab g' => 'G',
        'biggrab h' => 'H',
        'biggrab i' => 'I',
        'biggrab j' => 'J',
        'biggrab k' => 'K',
        'biggrab l' => 'L',
        'biggrab m' => 'M',
        'biggrab n' => 'N',
        'biggrab o' => 'O',
        'biggrab p' => 'P',
        'biggrab q' => 'Q',
        'biggrab r' => 'R',
        'biggrab s' => 'S',
        'biggrab t' => 'T',
        'biggrab u' => 'U',
        'biggrab v' => 'V',
        'biggrab w' => 'W',
        'biggrab x' => 'X',
        'biggrab y' => 'Y',
        'biggrab z' => 'Z'
    ));
    $story = strtr($story, array(
        " \n" => "\n",
        ' :' => ':',
        ' ;' => ';',
        ' ,' => ',',
        ' .' => '.',
        ' ? ' => '?',
        ' ! ' => '!',
        ' #k1#' => "\"",
        ' #k2#' => "\'",
        ' #k3#' => " \"",
        ' #k4#' => " \'",
        ' ) ' => ')',
        ' ( ' => '(',
        ' >> ' => '>>',
        ' << ' => '<<',
        ' !' => '!',
        ' ?' => '?',
        "\\" => '',
        '[sin]' => '',
        '[/sin]' => '',
        '[nosin]' => '',
        '[/nosin]' => '',
        'biggrab ' => ''
    ));
    return $story;
}
function sinonims($story, $kol = false)
{
    $story = start($story);
    $story = sinomize($story, $kol);
    $story = finish($story);
    return $story;
}
function sinomize($text, $kol)
{
    global $db, $parse, $config;
    $nosa = array();
    $nosb = array();
    $nosc = array();
    preg_match_all("#<.*?>#is", $text, $htmlreps);
    preg_match_all('#\[(img|flash).*?\](.*?)\[\/(img|flash)\]#is', $text, $bbrep);
    preg_match_all("#(\[.*?\])#is", $text, $bbreps);
    foreach ($htmlreps[0] as $key => $value) {
        $nosa[' 3r3r3' . $key] = $value;
    }
    if (count($nosa) != '')
        $text = strtr($text, array_flip($nosa));
    foreach ($bbrep[0] as $key => $value) {
        $nosb[' 1q1q1' . $key] = $value;
    }
    if (count($nosb) != '')
        $text = strtr($text, array_flip($nosb));
    foreach ($bbreps[0] as $key => $value) {
        $nosc[' 2w2w2' . $key] = $value;
    }
    if (count($nosc) != '')
        $text = strtr($text, array_flip($nosc));
    $text    = str_replace("\n", '<br />', $text);
    $story   = $parse->BB_Parse($text, true);
    $story   = strip_tags($story);
    $sinonim = array();
    preg_match_all('/([а-яА-Яa-zA-Z]+)/', $text, $words);
    $sss = $words[1];
    sort($sss);
    $oldvalue = '';
    $where    = '';
    foreach ($sss as $key => $value) {
        if ($value != $oldvalue and $value != '' and strlen($value) > 1 and $value != 'biggrab') {
            $newarr[] = "like '%" . $db->safesql($value) . "|%'";
        }
        $oldvalue = $value;
    }
    if (count($newarr) != '0') {
        $where = implode(' or string ', $newarr);
        if (intval($config_rss['limit_sinonims']) != '0')
            $where .= "LIMIT " . $config_rss['limit_sinonims'];
        $db->connect(DBUSER, DBPASS, DBNAME, DBHOST);
        $sql = $db->query("SELECT * FROM " . PREFIX . "_synonims WHERE string $where");
        if ($db->num_rows($sql) > 0) {
            while ($row = $db->get_array($sql)) {
                $storyr = explode("|", $row['string']);
                if (preg_match("#" . $storyr[0] . "#i", $text)) {
                    $pattern = ' ' . $storyr[0] . ' ';
                    $vars    = explode(",", $storyr[1]);
                    $rnd     = array_rand($vars);
                    $f       = '<font color="red">';
                    $e       = "</font>";
                    if ($kol == true) {
                        $repl = ' ' . $f . $vars[$rnd] . $e . ' ';
                    } else {
                        $repl = ' ' . $vars[$rnd] . ' ';
                    }
                    $sinonim1[] = trim($pattern);
                    $sinonim2[] = $repl;
                }
            }
            $text = str_replace($sinonim1, $sinonim2, $text);
        }
    }
    if (count($nosa) != '')
        $text = strtr($text, $nosa);
    if (count($nosb) != '')
        $text = strtr($text, $nosb);
    if (count($nosc) != '')
        $text = strtr($text, $nosc);
    return $text;
}
$db->close;
?>