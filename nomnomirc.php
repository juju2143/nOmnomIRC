<?php
// Configure stuff here
$cookiepath = $_SERVER["HOME"]."/.config/google-chrome/Default/Cookies"; // Path of the login cookie for Chrome or Firefox
$cookiehost = "www.omnimaga.org";
$url = "http://omnomirc.www.omnimaga.org";
$browser = "chrome"; //"chrome" or "firefox" or "other"
$cookieother = "SMFCookie666=JustPasteYourOmnimagaCookieHere;"; //if you selected "other" then paste your Omnimaga cookie here
$channel = "#omnimaga"; // default channel
$timeout = 1;
$updatedelay = 2;

// Ok now stop configuring and have fun :P

/*
    nOmnomIRC 0.1.1, a ncurses client for OmnomIRC
    Copyright (C) 2013 Julien "Juju" Savard

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


function color_of($name)
{
	//$rcolors = Array(19, 20, 22, 24, 25, 26, 27, 28, 29);
	$rcolors = Array(3, 4, 6, 8, 9, 10, 11, 12, 13);
	$i = 0; $sum = 0;

	for($i=0;$i<strlen($name);$i++)
		$sum += ord($name[$i++]);
	$sum %= count($rcolors);
	return str_pad($rcolors[$sum], 2, 0, STR_PAD_LEFT);
}

function parseColors($str)
{
	//global $x;
	//TODO: add colors
	//return wordwrap(html_entity_decode($str), $x-12, "\n", true);
	return html_entity_decode($str);
}

function colorNick($str)
{
	return chr(3).color_of(html_entity_decode($str)).html_entity_decode($str).chr(15);
}

function base64_url_encode($input) {
	return strtr(base64_encode($input), '+/=', '-_,');
}

function base64_url_decode($input) {
	return base64_decode(strtr($input, '-_,', '+/=')); 
}

function rmBOM($string) { 
	if(substr($string,0,3) == pack("CCC",0xef,0xbb,0xbf))
	{
		$string=substr($string, 3);
	}
	return $string;
}

function void($a=null){}

function signCallback($signature, $nick, $id)
{
	global $sig;
	$sig[0] = $signature;
	$sig[1] = $nick;
	$sig[2] = $id;
}

function addLine($message)
{	
	global $curLine,$lines,$topicwin;
	$line = explode(":", $message);
	if($line[1] == "topic") printColor($topicwin,0,0,base64_url_decode($line[5]));
	else if(parseMessage($message) == "") void(0);
	else $lines[] = $message;
	$curLine = $line[0];
}

function addUser($message)
{
	global $userList;
	$userList[] = $message;
}

function addUserJoin($user, $online)
{
	global $userList;
	if(count($userList)>0) $userList[] = base64_url_encode($user).":".$online;
}

function removeUser($user, $online)
{
	global $userList;
	if(count($userList)>0) $userList = array_diff($userList, Array(base64_url_encode($user).":".$online));
}

function parseMessage($message, $parseUL=true)
{
	global $channel;
	$parts = explode(":", $message);
	$lnumber = $parts[0];
	$type = count($parts)>1?$parts[1]:"";
	$online = count($parts)>2?$parts[2]:"";
	$date = count($parts)>3?(date("[H:i:s]",$parts[3]).($online?chr(3)."02o".chr(15):" ")):"";
	$parsedMessage = "";
	if(count($parts)>4)
	for($i = 4; $i<count($parts); $i++)
	{
		$parts[$i] = base64_url_decode($parts[$i]);
	}
	$name = count($parts)>4?colorNick($parts[4]):"";
	$parsedMessage = count($parts)>5?parseColors($parts[5]):"";
	switch($type)
	{
		case "join":
			if($parseUL) addUserJoin($parts[4], $online);
			return $online?"":$date."* ".$name." has joined ".$channel;
			break;
		case "part":
			if($parseUL) removeUser($parts[4], $online);
			return $online?"":$date."* ".$name." has left ".$channel." (".$parsedMessage.")";
			break;
		case "quit":
			if($parseUL) removeUser($parts[4], $online);
			return $online?"":$date."* ".$name." has quit IRC (".$parsedMessage.")";
			break;
		case "kick":
			if($parseUL) removeUser($parts[5], $online);
			return $date."* ".$name." has kicked ".$parts[5]." from ".$channel." (".$parsedMessage.")";
			break;
		case "message":
			return $date."<".$name."> ".$parsedMessage;
			break;
		case "action":
			return $date."* ".$name." ".$parsedMessage;
			break;
		case "mode":
			return $date."* ".$name." set ".$channel." mode ".$parts[5];
			break;
		case "nick":
			return $date."* ".$name." has changed his nick to ".$parsedMessage;
			if($parseUL) removeUser($parts[4], $online);
			if($parseUL) addUserJoin($parts[5], $online);
			break;
		case "internal":
			return $date."* ".$parts[4];
			break;
		case "server":
			return $date."* ".$parsedMessage;
			break;
		case "pm":
			return $date."(PM) <".$name."> ".$parsedMessage;
			break;
	}
	return "";
}

function rl_callback($ret)
{
global $mainwin, $inputwin, $running, $sig, $context, $channel, $lines, $userList, $curLine, $context, $url;
if($ret != "")
{
	ncurses_werase($inputwin);
	readline_add_history($ret);
	if($ret == "/quit")
	{
		$running = false;
		ncurses_end();
	}
	else if(substr($ret,0,3) == "/j ")
	{
		$channel = substr($ret,3);
		$lines = Array();
		$userList = Array();
		eval(rmBOM(file_get_contents($url."/Load.php?count=150&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0])."&time=".time(), false, $context)));
	}
	else if($ret == "/update")
	{
		$newmsg = rmBOM(file_get_contents($url."/Update.php?lineNum=".$curLine."&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0]), false, $context));
		if($newmsg != "")
		{
			$msgs = explode("\n",$newmsg);
			foreach($msgs as $msg)
			{
				if($msg != "") addLine($msg);
			}
		}
	}
	else if($ret == "/test")
	{
		addLine($curLine.":internal:0:".time().":".base64_url_encode($sig[0]).":");
	}
	else
	{
		if($sig[1]!="Guest")
		{
			$return = rmBOM(file_get_contents($url."/message.php?nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0])."&message=".base64_url_encode($ret)."&channel=".base64_url_encode($channel)."&id=".$sig[2], false, $context));
		}
		else
		{
			addLine($curLine.":internal:0:".time().":".base64_url_encode("You must be logged in to send a message!").":");
		}
	}
}
}

function rl_completion($string, $index)
{
	global $userList;
	$array = Array();
	foreach($userList as $users)
	{
		$user = explode(":", $users);
		$array[] = base64_url_decode($user[0]);
		//if (preg_match ('/^".$string."(\w+)/i', base64_url_decode($user[0]), $m))
		//	return Array(base64_url_decode($user[0]));
	}
	return $array;
}

function printColor($win, $posx, $posy, $str, $strpad=false)
{
	global $x, $y;
	$j = $posy; $bold = false; $reverse = false; $uline = false;
	ncurses_wattroff($win,NCURSES_A_DIM|NCURSES_A_BOLD|NCURSES_A_REVERSE|NCURSES_A_UNDERLINE);
	//ncurses_wattron($win,NCURSES_A_BOLD);
	$regexp = "/([\x02\x1F\x0F\x16]|\x03[0-9]{1,2},[0-9]{1,2}|\x03[0-9]{1,2}|\x03)/";
	$parts = preg_split($regexp, $str, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
	foreach($parts as $part)
	{
		if($part[0] == chr(3))
		{
			$color = explode(",", substr($part, 1));
			if(!isset($color[0])) $color=0;
			ncurses_wcolor_set($win, $color[0]%16);
			if(!in_array($color[0]%16, array(0,4,8,9,11,12,13,14)))
				ncurses_wattron($win,NCURSES_A_DIM);
			else
				ncurses_wattroff($win,NCURSES_A_DIM);
		}
		else if($part[0] == chr(2))
		{
			if(!$bold) ncurses_wattron($win,NCURSES_A_BOLD);
			else ncurses_wattroff($win,NCURSES_A_BOLD);
			$bold = !$bold;
		}
		else if($part[0] == chr(15))
		{
			ncurses_wcolor_set($win, 0);
			ncurses_wattroff($win,NCURSES_A_DIM|NCURSES_A_BOLD|NCURSES_A_REVERSE|NCURSES_A_UNDERLINE);
			//ncurses_wattron($win,NCURSES_A_BOLD);
		}
		else if($part[0] == chr(22))
		{
			if(!$reverse) ncurses_wattron($win,NCURSES_A_REVERSE);
			else ncurses_wattroff($win,NCURSES_A_REVERSE);
			$reverse = !$reverse;
		}
		else if($part[0] == chr(31))
		{
			if(!$uline) ncurses_wattron($win,NCURSES_A_UNDERLINE);
			else ncurses_wattroff($win,NCURSES_A_UNDERLINE);
			$uline = !$uline;
		}
		else
		{
			ncurses_mvwaddstr($win,$posx,$j,$part);
			$j+=strlen($part);
		}
	}
	ncurses_wcolor_set($win, 0);
	ncurses_wattroff($win,NCURSES_A_DIM|NCURSES_A_BOLD|NCURSES_A_REVERSE|NCURSES_A_UNDERLINE);
	//ncurses_wattron($win,NCURSES_A_BOLD);
	$bold = false; $reverse = false; $uline = false;
	if($strpad) ncurses_mvwaddstr($win,$posx,$j,str_pad("",$x-12-$j));
}

ncurses_init();
if (ncurses_has_colors())
{
	ncurses_start_color();
	ncurses_assume_default_colors(NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
	ncurses_init_pair(1, NCURSES_COLOR_BLACK, NCURSES_COLOR_BLACK);
	ncurses_init_pair(2, NCURSES_COLOR_BLUE, NCURSES_COLOR_BLACK);
	ncurses_init_pair(3, NCURSES_COLOR_GREEN, NCURSES_COLOR_BLACK);
	ncurses_init_pair(4, NCURSES_COLOR_RED, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(5, NCURSES_COLOR_RED, NCURSES_COLOR_BLACK);
	ncurses_init_pair(6, NCURSES_COLOR_MAGENTA, NCURSES_COLOR_BLACK);
	ncurses_init_pair(7, NCURSES_COLOR_YELLOW, NCURSES_COLOR_BLACK);
	ncurses_init_pair(8, NCURSES_COLOR_YELLOW, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(9, NCURSES_COLOR_GREEN, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(10, NCURSES_COLOR_CYAN, NCURSES_COLOR_BLACK);
	ncurses_init_pair(11, NCURSES_COLOR_CYAN, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(12, NCURSES_COLOR_BLUE, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(13, NCURSES_COLOR_MAGENTA, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(14, NCURSES_COLOR_BLACK, NCURSES_COLOR_BLACK);//bold
	ncurses_init_pair(15, NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
}
$topicwin = ncurses_newwin(0,0,0,0);
ncurses_getmaxyx($topicwin, $y, $x);
$mainwin = ncurses_newwin($y-3,$x,1,0);
$statwin = ncurses_newwin(1,$x,$y-2,0);
$inputwin = ncurses_newwin(1,$x,$y-1,0);
$userwin = ncurses_newwin($y-3,12,1,$x-12);
ncurses_refresh();
$running = true;
$cookie = "";
$key = false;
$time = microtime(true);
$userList = Array();

if($browser == "chrome" || $browser == "firefox")
{
	$db = new SQLite3($cookiepath);
	if($browser == "chrome")
		$sql = $db->prepare("select name,value from cookies where host_key like :host;");
	else if($browser == "firefox")
		$sql = $db->prepare("select name,value from moz_cookies where host like :host;");
	$sql->bindValue(":host", "%".$cookiehost);
	$result = $sql->execute();
	while($res = $result->fetchArray(SQLITE3_ASSOC)){
		$cookie .= $res['name']."=".$res['value']."; ";
	}
	$sql->close();
	$db->close();
}
else
{
	$cookie=$cookieother;
}

$context = stream_context_create(array('http' => array('header' => "Cookie: ".$cookie."\r\nConnection: close\r\n")));
$contextto = stream_context_create(array('http' => array('header' => "Connection: close\r\n", 'timeout' => $timeout)));
eval(rmBOM(file_get_contents("http://www.omnimaga.org/checkLogin.php", false, $context)));

readline_callback_handler_install("", 'rl_callback');
readline_completion_function('rl_completion');

eval(rmBOM(file_get_contents($url."/Load.php?count=150&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0])."&time=".time(), false, $context)));
ncurses_wrefresh($topicwin);
ncurses_wrefresh($mainwin);
ncurses_wrefresh($userwin);
ncurses_wrefresh($statwin);
ncurses_wrefresh($inputwin);

while($running)
{
	$w = NULL;
	$e = NULL;
	$n = stream_select($r = array(STDIN), $w, $e, 0);
	if($n>0 && in_array(STDIN, $r)) readline_callback_read_char();
	$rl_info = readline_info();
	ncurses_werase($inputwin);
	ncurses_mvwaddstr($inputwin,0,0,$rl_info['line_buffer']);
	ncurses_wmove($inputwin,0,$rl_info['point']);

	if(microtime(true) > $time+$updatedelay)
	{
		@$newmsg = rmBOM(file_get_contents($url."/Update.php?lineNum=".$curLine."&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0]), false, $contextto));
		if($newmsg != "")
		{
			$msgs = explode("\n",$newmsg);
			foreach($msgs as $msg)
			{
				if($msg != "") addLine($msg);
			}
		}
		$time = microtime(true);
	}

	printColor($statwin,0,0,str_pad("-[".date("H:i:s")."]- -[".$sig[1]."(".$sig[2].")]- -[".$channel." (".count($userList)." users)]- -[curline:".$curLine."]-",$x," "));
	for($i = (count($lines)-($y-3)<0?0:count($lines)-($y-3)); $i<count($lines); $i++)
	{
		$str = parseMessage($lines[$i], false);
		printColor($mainwin,$i-(count($lines)-($y-3)),0,$str,true);
	}
	$userOffset = 0;
	$i = 0;
	//for($i = 0;$i<count($userList);$i++)
	foreach($userList as $users)
	{
		$user = explode(":", $users);
		ncurses_wcolor_set($userwin, 2);
		ncurses_mvwaddstr($userwin,$i,0,($user[1]?"σ":" "));
		ncurses_wcolor_set($userwin, 0);
		ncurses_mvwaddstr($userwin,$i,1,str_pad(base64_url_decode($user[0]),11," "));
		$i++;
	}
	ncurses_wrefresh($topicwin);
	ncurses_wrefresh($mainwin);
	ncurses_wrefresh($userwin);
	ncurses_wrefresh($statwin);
	ncurses_wrefresh($inputwin);
	usleep(10000);
}
readline_callback_handler_remove();
ncurses_end();
?>
