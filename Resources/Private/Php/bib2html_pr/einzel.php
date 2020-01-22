<?

/**
 * 2017-11-08 VB: Abfrage auf GET-Parameter "style" ergänzt
 */
define("LOG",dirname(__FILE__).'/log.txt');

//$template = isset($_REQUEST['ref'])?$_REQUEST['ref']:'http://www.uni-oldenburg.de/www/25948.html';
$sort = isset($_REQUEST['sort'])?$_REQUEST['sort']:false;
$sortfixed = isset($_REQUEST['sortfixed'])?true:false;
$template = isset($_REQUEST['template'])?$_REQUEST['template']:false;

/* Parameter, die übergeben werden sollen */
$pars = (isset($_REQUEST['allow'])?' allow='.$_REQUEST['allow']:'') 
    . (isset($_REQUEST['deny'])?' deny='.$_REQUEST['deny']:'') 
    . (isset($_REQUEST['key'])?' key='.$_REQUEST['key']:'');
    //. (isset($_REQUEST['template'])?' template='.$_REQUEST['template']:'');

// loggen($template);

$lang = @$_GET['lang']=='en'?'_en':'';

define('BIB2HTML_URL','http://php51.uni-oldenburg.de/www/bib2html_pr/bib2html/');
include_once('./bib2html/bib2html.php');


$file = isset($_REQUEST['bibtex'])?$_REQUEST['bibtex']:false;

$nStyle = isset($_REQUEST['style'])?intval($_REQUEST['style']):0;
/* Verfügbare Styles */
$aStyles = array('uniol','uniol1','IEEE');
$style = $aStyles[$nStyle]; // standardmäßig "uniol"
echo '<!-- Style: '.$style.' -->';

if(!$file) {
    echo '<p style="color:red;">FEHLER: keine g&uuml;ltige BIB-Datei &uuml;bergeben!</p>';
    exit;
}


//	error_log("Bib2Html: ".$file);
//	$tempstr = bib2html(' [bibtex file='.$aBibtexString[1].'] ', $sort, $lang);
	$tempstr = bib2html(' [bibtex file='.$file.$pars.'] ', $sort, $lang, $sortfixed, $template, $style);
       
	$tempstr = str_replace("<ul>",'<ul class="geweitet">',$tempstr);
        $tempstr = str_replace('class="toggle"','class="bibtextoggle"',$tempstr);
	//$rueck = utf2html($tempstr);
        echo $tempstr;


function loggen($str) {
	file_put_contents(LOG, date("Y-m-d H:i:s")." ".$str."\n", FILE_APPEND);
	
}
function utf2html (&$str) {
   
    $ret = "";
    $max = strlen($str);
    $last = 0;  // keeps the index of the last regular character
    for ($i=0; $i<$max; $i++) {
        $c = $str{$i};
        $c1 = ord($c);
        if ($c1>>5 == 6) {  // 110x xxxx, 110 prefix for 2 bytes unicode
            $ret .= substr($str, $last, $i-$last); // append all the regular characters we've passed
            $c1 &= 31; // remove the 3 bit two bytes prefix
            $c2 = ord($str{++$i}); // the next byte
            $c2 &= 63;  // remove the 2 bit trailing byte prefix
            $c2 |= (($c1 & 3) << 6); // last 2 bits of c1 become first 2 of c2
            $c1 >>= 2; // c1 shifts 2 to the right
            $ret .= "&#" . ($c1 * 0x100 + $c2) . ";"; // this is the fastest string concatenation
            $last = $i+1;      
        }
        elseif ($c1>>4 == 14) {  // 1110 xxxx, 110 prefix for 3 bytes unicode
            $ret .= substr($str, $last, $i-$last); // append all the regular characters we've passed
            $c2 = ord($str{++$i}); // the next byte
            $c3 = ord($str{++$i}); // the third byte
            $c1 &= 15; // remove the 4 bit three bytes prefix
            $c2 &= 63;  // remove the 2 bit trailing byte prefix
            $c3 &= 63;  // remove the 2 bit trailing byte prefix
            $c3 |= (($c2 & 3) << 6); // last 2 bits of c2 become first 2 of c3
            $c2 >>=2; //c2 shifts 2 to the right
            $c2 |= (($c1 & 15) << 4); // last 4 bits of c1 become first 4 of c2
            $c1 >>= 4; // c1 shifts 4 to the right
            $ret .= '&#' . (($c1 * 0x10000) + ($c2 * 0x100) + $c3) . ';'; // this is the fastest string concatenation
            $last = $i+1;      
        }
    }
    $str=$ret . substr($str, $last, $i); // append the last batch of regular characters
}
?>
