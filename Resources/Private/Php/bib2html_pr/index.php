<?
//
//if(isset($_REQUEST['bibtex'])) {
//    include('einzel.php');
//    exit;
//}

require_once ("UniOlWebseiteNeu.php");


define("LOG",dirname(__FILE__).'/log.txt');

$template = isset($_REQUEST['ref'])?$_REQUEST['ref']:'https://uol.de/www/25948.html';
$sort = isset($_REQUEST['sort'])?$_REQUEST['sort']:false;
$bTYPO3 = strpos($template,'uol.de');

loggen($template);

$seite = new UniOlWebseite();
//$seite->SelbstlinksErsetzen = false;
//$seite->LinksVerabsolutieren = true;
if($bTYPO3) $seite->TemplateHttp = 'https://uol.de';
$rueck = $seite->setzeTemplate($template);
if($bTYPO3) $seite->TemplateKopf = str_replace('<base href="https://uol.de/">','',$seite->holeTemplateKopf());

$lang = strpos($seite->holeTemplateKopf(),'_en.css')?'_en':'';

//define('BIB2HTML_URL','http://php51.uni-oldenburg.de/www/bib2html/bib2html/');
define('BIB2HTML_URL','https://uol.de/www/bib2html_pr/bib2html/');
include_once(__DIR__ . '/bib2html/bib2html.php');

$seite->TemplateKopf = str_replace('</head>','
<script src="'.BIB2HTML_URL.'js/bib2html.js"  type="text/javascript"></script>
<!-- script src="'.BIB2HTML_URL.'js/jqModal.js"  type="text/javascript"></script -->
<!-- link type="text/css" rel="stylesheet" media="all" href=./bib2html/css/jqModal.css" / -->
<style type="text/css">

div.bibtex {
    display: none;
	font-size:12px;
	border:1px solid #ccc;
	padding:5px;
}
.bibtexcontainer {
	padding:0.5em;
	border:1px solid #ccc;
}
.bibtexcontainer ul li {
	margin-bottom:0.5em;
}
div.sortierung {
	border: 0px solid #ccc;
	font-weight:bold;
	padding:0px;
	margin: 0 0 1em 0;
}
div.sortierung a {
	float:left;
	display:block;
	width:auto;
	padding:2px 5px;
	border:1px solid #ccc;
	margin-right:0.5em;
}
div.sortierung a:hover {
	text-decoration:none !important;
	background-color:#eee;
}
div.sortierung a.aktiv {
	text-decoration:none !important;
	background-color:#eee;
}
div.sortierung span {
	float:left;
	display:block;
	width:auto;	
	padding:2px 5px 2px 0;
}
div.sortierung div {
	width:1em;
}
</style>
</head>',
$seite->holeTemplateKopf()
);


//$inhalt = bib2html(' [bibtex file=HybrideSysteme.bib allow=article,inproceedings,phdthesis] ');

preg_match_all("'\[bibtex'si",$seite->holeInhalt(),$aBibtexFehler,PREG_SET_ORDER);
if(count($aBibtexFehler) && ($template != 'https://uol.de/www/25948.html')) mail("volker.burggraef@uni-oldenburg.de",'Bibtex-Fehler '.$template,'Format-Fehler in Quellseite '.$template);

//preg_match_all("'\[?<a href=\"([^\"]+)\">\[?bibtex\]?</a>\]?'si",$seite->holeInhalt(),$aBibtexStrings,PREG_SET_ORDER);
preg_match_all("'\[\s*<a href=\"([^\"]+)\"[^>]*>bibtex</a>([^\]]*)\]'si",$seite->holeInhalt(),$aBibtexStrings,PREG_SET_ORDER);


$cou = count($aBibtexStrings);
//if ($cou) print_r($aBibtexStrings);
if($cou>0) foreach ($aBibtexStrings as $aBibtexString) {
	//error_log("Bib2Html: ".$aBibtexString[0]);
//	$tempstr = bib2html(' [bibtex file='.$aBibtexString[1].'] ', $sort, $lang);
	$tempstr = bib2html(' [bibtex file='.$aBibtexString[1].$aBibtexString[2].'] ', $sort, $lang);
	$tempstr = str_replace("<ul>",'<ul class="geweitet">',$tempstr);
        if(!$bTYPO3) $tempstr = utf8_decode ($tempstr);
	//$rueck = utf2html($tempstr);
        //if(strpos($template, 'www.uni-oldenburg.de')) $tempstr = utf8_decode($tempstr);
	$seite->Inhalt = str_replace($aBibtexString[0],$tempstr,$seite->holeInhalt());
}
//$inhalt_neu = preg_replace("'\[<a\s+href=\"([^\"]+)\">\s*bibtex\s*</a>\]'i"," [bibtex file=$1] ",$seite->Inhalt);
//$inhalt = bib2html($inhalt_neu, $sort, $lang);
//$rueck = utf2html($inhalt);
//$seite->Inhalt = $inhalt;

//echo $seite->holeTemplateKopf().$seite->Inhalt.$seite->TemplateFuss;
$seite->ausgeben();

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
