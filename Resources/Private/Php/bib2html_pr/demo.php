<html>
<head>
<script type="text/javascript" src="http://www.uni-oldenburg.de/img/script/master.js"></script>
<link rel="stylesheet" type="text/css" href="http://www.uni-oldenburg.de/img/css3/stylesheet.css" media="all">
</head>

<body>
<br>
<?php
include_once(dirname(__FILE__) . '/bib2html/bib2html.php');

/* Parameter, die übergeben werden sollen */
$pars = (isset($_REQUEST['allow'])?' allow='.$_REQUEST['allow']:'') 
    . (isset($_REQUEST['deny'])?' deny='.$_REQUEST['deny']:'') 
    . (isset($_REQUEST['key'])?' key='.$_REQUEST['key']:'');

$sort = isset($_REQUEST['sort'])?$_REQUEST['sort']:false;
$lang = @$_GET['lang']=='en'?'_en':'';


// Ist die Sortierung fix vorgegeben, oder soll der_die Nuter_in selbst 
// sortieren können? Bei 'fale' wird oberhalb der Publikationen
// "Sortierung: Art der Publikation | Autor | Jahr | (ohne)" angezeigt.
// Standard ist das bisherige Verhalten, also 'false'
$sortFixed = false; //true;

// Hier kann eine eigene Vorlage ausgewählt werden. Wir nichts angegeben, wird
// die bisherige Vorlage verwendet. Standard ist das bisherige Verhalten, also ''
$template = ''; //dirname(__FILE__) . '/bibentry-twist.tpl';

$file = dirname(__FILE__) . '/paper.bib';

$tempstr = bib2html(' [bibtex file='.$file.$pars.'] ', $sort, $lang, $sortFixed, $template);
print_r($tempstr);

?>

</body>
</html>
