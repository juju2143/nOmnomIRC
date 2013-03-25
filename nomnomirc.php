<?php
// Configure stuff here
$cookiepath = ".config/google-chrome/Default/Cookies"; // Path of the login cookie
$cookiehost = "www.omnimaga.org";
$browser = "chrome"; //"chrome" or "firefox" or "other"
$cookieother = "SMFCookie666=JustPasteYourOmnimagaCookieHere;"; //if you selected "other" then paste your Omnimaga cookie here
$channel = "#omnimaga"; // default channel
$timeout = 1;
$updatedelay = 2;

// Ok now stop configuring and have fun :P

/*
    nOmnomIRC 0.1, a ncurses client for OmnomIRC
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

function parseColors($str)
{
	//TODO: add colors
	return html_entity_decode($str);
}

function colorNick($str)
{
	//TODO: add colors
	return html_entity_decode($str);
}

function base64_url_encode($input) {
	return strtr(base64_encode($input), '+/=', '-_,');
}

function base64_url_decode($input) {
	return base64_decode(strtr($input, '-_,', '+/=')); 
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
	if($line[1] == "topic")
		ncurses_mvwaddstr($topicwin,0,0,base64_url_decode($line[5]));
	else if(parseMessage($message) == "") void(0);
	else
		$lines[] = $message;
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
	$date = count($parts)>3?date("[H:i:s] ",$parts[3]):"";
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
global $mainwin, $inputwin, $running, $sig, $context, $channel, $lines, $userList, $curLine, $context;
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
		eval(trim(file_get_contents("http://omnomirc.www.omnimaga.org/Load.php?count=150&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0])."&time=".time(), false, $context ),"\x7f..\xff"));
	}
	else if($ret == "/update")
	{
		$newmsg = trim(file_get_contents("http://omnomirc.www.omnimaga.org/Update.php?lineNum=".$curLine."&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0]), false, $context ),"\x7f..\xff");
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
			$url = "http://omnomirc.www.omnimaga.org/message.php?nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0])."&message=".base64_url_encode($ret)."&channel=".base64_url_encode($channel)."&id=".$sig[2];
			$return = file_get_contents($url, false, $context);
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

ncurses_init();
if (ncurses_has_colors())
{
ncurses_start_color();
ncurses_init_pair(1, NCURSES_COLOR_BLUE, NCURSES_COLOR_BLACK);
}
//$fp = fopen("php://stdin","r");
//stream_set_blocking($fp,0);
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
//$sig = explode("\n", file_get_contents("http://www.omnimaga.org/checkLogin.php?txt", false, $context));
eval(trim(file_get_contents("http://www.omnimaga.org/checkLogin.php", false, $context),"\x7f..\xff"));

readline_callback_handler_install("", 'rl_callback');
readline_completion_function('rl_completion');

//$page = html_entity_decode(strip_tags(str_replace("</td>","\n",str_replace("autofocus>Click here to write a message</a>","></a>",file_get_contents("http://www.omnimaga.org/checkLogin.php?textmode", false, $context)))));
eval(trim(file_get_contents("http://omnomirc.www.omnimaga.org/Load.php?count=150&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0])."&time=".time(), false, $context),"\x7f..\xff"));
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
@$newmsg = trim(file_get_contents("http://omnomirc.www.omnimaga.org/Update.php?lineNum=".$curLine."&channel=".base64_url_encode($channel)."&nick=".base64_url_encode($sig[1])."&signature=".base64_url_encode($sig[0]), false, $contextto),"\x7f..\xff");
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

ncurses_mvwaddstr($statwin,0,0,str_pad("-[".date("H:i:s")."]- -[".$sig[1]."(".$sig[2].")]- -[".$channel." (".count($userList)." users)]- -[curline:".$curLine."]-",$x," "));
for($i = (count($lines)-($y-3)<0?0:count($lines)-($y-3)); $i<count($lines); $i++)
{
	ncurses_mvwaddstr($mainwin,$i-(count($lines)-($y-3)),0,str_pad(parseMessage($lines[$i], false),$x-12," "));
}
$userOffset = 0;
$i = 0;
//for($i = 0;$i<count($userList);$i++)
foreach($userList as $users)
{
	$user = explode(":", $users);
	ncurses_color_set(1);
	ncurses_mvwaddstr($userwin,$i,0,($user[1]?"σ":" "));
	ncurses_color_set(0);
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
