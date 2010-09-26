<?
$HOST = "http://www.lunasys.fr";

$root = "/hd1/hosting";

if( isset( $_GET["d"] ) && trim($_GET["d"])!="" )
	$dir = urldecode( $_GET["d"] )."/";
else
	$dir = "/music/";
//$dir = "/music/";

$file_formats = array( "ogg" );

function getList( $dir ) {
	//echo "<br>--- PARSE $dir<br>";
	global $root, $file_formats;
	$dh = opendir( $root.$dir );
	$files = array();
	$dirs = array();
	while (($file = readdir($dh)) !== false) {
		//echo "[$dir] filename: $file : filetype: " . @filetype($root.$dir.$file) . "<br>\n";
		if( @filetype($root.$dir.$file)=="file" && in_array( substr( $file, strlen($file)-3, 3 ), $file_formats ) )
			$files[] = $dir.$file;
		if( @filetype($root.$dir.$file)=="dir" && $file!="." && $file!=".." )
			$dirs[] = $file;
	}
	foreach( $dirs as $d ) {
		$files = array_merge( $files, getList( $dir.$d."/" ) );
	}
	return $files;
}


if( isset( $_GET["list"] ) ) {
	echo json_encode( getList( $dir ) );
	exit();
}
if( isset( $_GET["get"] ) ) {

$dh = opendir( $root.$dir );
while (($file = readdir($dh)) !== false) {
	//echo "filename: $file : filetype: " . filetype($root.$dir.$file) . "\n";
	if( filetype($root.$dir.$file)=="file" && in_array( substr( $file, strlen($file)-3, 3 ), $file_formats ) )
		$files[] = $dir.$file;
	if( filetype($root.$dir.$file)=="dir" && $file!="." && $file!=".." )
		$dirs[] = $dir.$file;
}
closedir($dh);

$updirs = split( "/", $dir );
$tmpdir = "";
array_shift( $updirs );
?><h3><?
foreach( $updirs as $updir ) {
	$tmpdir .= "/".$updir;
	?> <a href="javascript:getList('<?= urlencode( $tmpdir ) ?>');"><?= $updir ?></a> <?
}
?></h3><?

if( $dirs ) {
	?><ul><?
	foreach( $dirs as $dir ) {
		?><li><a href="javascript:getList('<?= urlencode( $dir ) ?>');">Enter <?= $dir ?></a> - <a href="javascript:enqueueDir('<?= addslashes( $dir ) ?>');">enqueue</a></li><?
	}
	?></ul><?
} else {
	?><p>No dir</p><?
}
if( $files ) {
	?><ul><?
	foreach( $files as $file ) {
		?><li><a href="javascript:play('<?= addslashes( $file ) ?>');">Play <?= $file ?></a> - <a href="javascript:enqueue('<?= addslashes( $file ) ?>');">enqueue</a></li><?
	}
	?></ul><?
} else {
	?><p>No file</p><?
}
	exit();
}
?>
<!DOCTYPE html> 
<html lang="fr"> 
<head> 
	<meta charset="utf-8"/> 
	<title>Lunatic Systems Music Player</title> 
	<link rel="SHORTCUT ICON" href="<?= $HOST ?>/media/r4.ico" /> 
	<script type="text/javascript" src="<?= $HOST ?>/media/js/jquery-1.3.2.min.js"></script> 
	<script type="text/javascript" src="<?= $HOST ?>/media/js/jquery-ui-1.7.2.custom.min.js"></script> 
	<link rel="stylesheet" href="<?= $HOST ?>/media/css/cms.css" /> 
	<link rel="stylesheet" href="music.css" /> 
	<link rel="stylesheet" href="<?= $HOST ?>/media/css/ui-darkness/jquery-ui-1.7.2.custom.css" /> 
</head> 
<body> 
<?
$preload = "none";
?>
<div id="main"> 
<div id="header"> 
	<a href="/"><img src="<?= $HOST ?>/media/img/logo-trans.png"/></a> 
	<audio id="player" class="right" src="" controls preload="<?= $preload ?>" autoplay autobuffer="autobuffer">
</div>

<div id="break"></div>
<div id="fileinfo"> 
</div>

<div id="break"></div>

<div id="browser"> 
</div>

<div id="playlist"> 
<a href="javascript:clear();">CLEAR</a>
 - 
<a href="javascript:next();">NEXT</a>
<ul id="sortable"></ul>
</div>


<div id="break"></div>
<div id="info"></div>
</div>

<script lang="text/javascript">
var files = [];
//var a = new Audio();
var a = $("#player").get(0);
//a.autobuffer = "autobuffer";
//a.autobuffer = false;
//a.preload = true;
//a.autoplay = true;
function setFile( file ) {
	a.pause();
	a.lang = file;
	a.src = file;
	a.load();
	a.play();
	/*
	a.addEventListener( 'ended', function() {alert("ended");}, false );
	a.addEventListener( 'pause', function() {alert("pause");}, false );
	a.addEventListener( 'empty', function() {alert("empty");}, false );
	a.addEventListener( 'error', function() {alert("error");}, false );
	a.addEventListener( 'play', function() {alert("play");}, false );
	a.addEventListener( 'stop', function() {alert("stop");}, false );
	a.addEventListener( 'suspend', function() {alert("suspend");}, false );
	a.addEventListener( 'waiting', function() {alert("waiting");}, false );
	a.addEventListener( 'emptied', function() {alert("emptied");}, false );
	a.addEventListener( 'abort', function() {alert("abort");}, false );
	a.addEventListener( 'seeked', function() {alert("seeked");}, false );
	a.addEventListener( 'stalled', function() {alert("stalled");}, false );
	*/
}
function fileinfo( txt ) {
	$("#fileinfo").html( txt );
}
function fileinfo_add( txt ) {
	$("#fileinfo").append( txt );
}
function clear() {
	$("#playlist").children("ul").html("");
}
function enqueue( file ) {
	var filename = file.replace( "/music/", "" );
	var e = $("<li></li>").attr("lang",file); //.click( function () { playThis( this ); } );
	e.html( "<div class='handle'>&nbsp;&nbsp;</div>" );
	var fn = $("<div></div>").click( function () { playThis( $(this).parent().get(0) ); } );
	fn.html( filename );
	e.append( fn );
	$("#playlist").children("ul").append( e );
}
function enqueueDir( d ) {
	$.getJSON( "?list&d="+d, function (json) {
		for( i in json ) {
			if( json[i] )
				enqueue( json[i] );
		}
	});
}
function playThis( e ) {
	$("#playlist").children("ul").children("li").each(function() {
		$(this).removeClass("current");
	});
	$(e).addClass("current");
	setFile( e.lang );
}
function play( file ) {
	enqueue( file );
	setFile( file );
}
function next() {
	var es = $("#playlist").children("ul").children("li");
	var n = false;
	for( i in es ) {
		if( n ) {
			//alert( "SET:"+es[i].lang );
			//fileinfo_add( "SET:"+es[i].lang );
			playThis( es[i] );
			break;
		}
		if( es[i].lang==a.lang ) {
			//alert( "DETECT:"+es[i].lang+" src:"+a.lang );
			//fileinfo_add( "DETECT:"+es[i].lang+" src:"+a.lang );
			n = true;
		}
	}
	if( !n && es.length>0 ) {
		playThis( es[0] );
	}
}

window.setInterval( updateStatus, 1000 );
function updateStatus() {
	if( (Math.floor( a.currentTime )+2)>a.duration ) {
		next();
	}
	var c = Math.floor( a.currentTime );
	var d = Math.floor( a.duration );
	fileinfo( "Elapsed time: "+c+"/"+d );
}
function getList( d ) {
	$.get( "index.php?get&d="+d, function( txt ) {
		$("#browser").html( txt );
	});
}

// Entry point
$(document).ready( function() {
	getList( "<? $dir ?>" );
	$("#sortable").sortable({
		placeholder: 'ui-state-highlight',
		handle: '.handle',
	});
	$("#sortable").disableSelection();
});
</script>
</body> 
</html> 
