<?php 

set_time_limit(0);
header('Access-Control-Allow-Origin: *');

/**
 * @packcage [sosmedstats]
 * @Author Muaz Ramdany
 * @blog https://www.webmestudio.xyz/
 * @version 1.0
 * -------------------------------------------------------------------------
 * How to Access
 * [Twitter] https://domain.com/?provider=twitter&username={value}
 * [Youtube] https://domain.com/?provider=youtube&channel_id={value}
 * [facebook] https://domain.com/?provider=facebook&type=fanspage&username={value}&param={likes,followers}
 * [instagram] https://domain.com/?provider=instagram&username={value}&param={followers,following}
 */

$provider 			= isset($_GET['provider']) ? $_GET['provider'] : null;
$fb_username        = isset($_GET['username']) ? $_GET['username'] : null;
$fb_type            = isset($_GET['type']) ? $_GET['type'] : null;
$fb_param           = isset($_GET['param']) ? $_GET['param'] : null;
$twitter_username 	= isset($_GET['username']) ? $_GET['username'] : null;
$youtube_channel  	= isset($_GET['channel_id']) ? $_GET['channel_id'] : null;
$instagram_username = isset($_GET['username']) ? $_GET['username'] : null;
$instagram_param  	= isset($_GET['param']) ? $_GET['param'] : null;

switch($provider) {
    
    case 'facebook':
		if($fb_username && $fb_type == 'fanspage') {
            getFBFansPageReaction($fb_username, $fb_param);
		}
		else {
			httpStatus(403);
		}
	break;
	
	case 'twitter':
		if($twitter_username) {
			getTwitterFollowers($twitter_username);
		}
		else {
			httpStatus(403);
		}
	break;
	
	case 'youtube':
		if($youtube_channel) {
			getYoutubeSubscriber($youtube_channel);
		}
		else {
			httpStatus(403);
		}
	break;
    
    case 'instagram':
		if($instagram_username) {
			getInstagramReaction($instagram_username, $instagram_param);
		}
		else {
			httpStatus(403);
		}
	break;
	
	default:
		httpStatus(403);
	break;
}

function getTwitterFollowers($twitter_username) {
    if($twitter_username == null) {
        httpStatus(403);
        exit;
    }
    
    $twitter_data = file_get_contents('https://mobile.twitter.com/'.$twitter_username);
    preg_match('#followers">\n.*<div class="statnum">([0-9,]*)</div>#', $twitter_data, $match);
    $twitter['count'] = str_replace(',', '', $match[1]);
    $twitter['count'] = intval($twitter['count']);
	$data = [ 'followers' => $twitter['count'] ];
	echo json_encode($data);
}

function getYoutubeSubscriber($youtube_channel_id) {
    if($youtube_channel_id == null) {
        httpStatus(403);
        exit;
    }
    
	$url = 'https://www.youtube.com/subscribe_embed?channelid='. $youtube_channel_id;
	$button_html = file_get_contents($url);
	$found_subscribers = preg_match( '/="0">(\d+)</i', $button_html, $matches );
    
	if ( $found_subscribers && isset( $matches[1] ) ) {
		$data = [ 'subscriber' => intval($matches[1]) ];
		echo json_encode($data);
	}
	else {
		$data = [ 'subscriber' => @intval($matches[1]) ];
		echo json_encode($data);
	}
}

function getInstagramReaction($username, $param) {
    if($username == null || $param == null) {
        httpStatus(403);
        exit;
    }
	
	require __DIR__ . '/vendor/autoload.php';
	$crawler = new Smochin\Instagram\Crawler();
	
	$user = $crawler->getUser('muazramdany');
	
	print_r($user);
	
    /*
    // Load HTML to DOM Object
    $dom = new DOMDocument();
    @$dom->loadHTML($data);
    
     // Parse DOM to get Meta Description
    $metas = $dom->getElementsByTagName('meta');
    $body = "";
    for ($i = 0; $i < $metas->length; $i ++) {
        $meta = $metas->item($i);
        if ($meta->getAttribute('name') == 'description') {
            $body = $meta->getAttribute('content');
        }
    }
    
    preg_match_all('/-\d+|(?!-)\d+/', $body, $match);
    
    if($param == 'followers') {
        $output = [
            'followers' => $match[0][0]
        ];
    }
    elseif($param == 'following') {
        $output = [
            'following' => $match[0][1]
        ];
    }
    else {
        httpStatus(403);
        exit;
    }

    echo json_encode($output); 
	*/
}

function getFBFansPageReaction($username, $param) {    
    if($username == null || $param == null) {
        httpStatus(403);
        exit;
    }
    
    header('Content-Type: text/html');
    
    // Extract HTML using curl
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, 'https://m.facebook.com/'.$username.'/community/');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
    
    $data = curl_exec($ch);
    curl_close($ch);
    
    // Load HTML to DOM Object
    $dom = new DOMDocument();
    @$dom->loadHTML($data);
    
    // parse DOM to get div
    $divs = $dom->getElementsByTagName('div');
    for ($i = 0; $i < $divs->length; $i ++) {
        $div = $divs->item($i);
        $cls = $div->nodeValue;
        $cls = (int) $cls;
        if($cls != 0) {
            $cls_arr[] = $cls;
        }
    }
    
    $cls_arr = array_unique($cls_arr);
    $cls_arr = array_values($cls_arr);
    
    if(@$cls_arr[0] == null || @$cls_arr[1] == null) {
        httpStatus(403);
        exit;
    }
    
    if($param == 'likes') {
        $output = [
            'likes' => $cls_arr[0],
        ];
    }
    elseif($param == 'followers') {
        $output = [
            'followers' => $cls_arr[1]
        ];
    }
    else {
        httpStatus(403);
        exit;
    }
    
    echo json_encode($output); 
}

function httpStatus($num) {
    $http = array(
        100 => 'HTTP/1.1 100 Continue',
        101 => 'HTTP/1.1 101 Switching Protocols',
        200 => 'HTTP/1.1 200 OK',
        201 => 'HTTP/1.1 201 Created',
        202 => 'HTTP/1.1 202 Accepted',
        203 => 'HTTP/1.1 203 Non-Authoritative Information',
        204 => 'HTTP/1.1 204 No Content',
        205 => 'HTTP/1.1 205 Reset Content',
        206 => 'HTTP/1.1 206 Partial Content',
        300 => 'HTTP/1.1 300 Multiple Choices',
        301 => 'HTTP/1.1 301 Moved Permanently',
        302 => 'HTTP/1.1 302 Found',
        303 => 'HTTP/1.1 303 See Other',
        304 => 'HTTP/1.1 304 Not Modified',
        305 => 'HTTP/1.1 305 Use Proxy',
        307 => 'HTTP/1.1 307 Temporary Redirect',
        400 => 'HTTP/1.1 400 Bad Request',
        401 => 'HTTP/1.1 401 Unauthorized',
        402 => 'HTTP/1.1 402 Payment Required',
        403 => 'HTTP/1.1 403 Forbidden',
        404 => 'HTTP/1.1 404 Not Found',
        405 => 'HTTP/1.1 405 Method Not Allowed',
        406 => 'HTTP/1.1 406 Not Acceptable',
        407 => 'HTTP/1.1 407 Proxy Authentication Required',
        408 => 'HTTP/1.1 408 Request Time-out',
        409 => 'HTTP/1.1 409 Conflict',
        410 => 'HTTP/1.1 410 Gone',
        411 => 'HTTP/1.1 411 Length Required',
        412 => 'HTTP/1.1 412 Precondition Failed',
        413 => 'HTTP/1.1 413 Request Entity Too Large',
        414 => 'HTTP/1.1 414 Request-URI Too Large',
        415 => 'HTTP/1.1 415 Unsupported Media Type',
        416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
        417 => 'HTTP/1.1 417 Expectation Failed',
        500 => 'HTTP/1.1 500 Internal Server Error',
        501 => 'HTTP/1.1 501 Not Implemented',
        502 => 'HTTP/1.1 502 Bad Gateway',
        503 => 'HTTP/1.1 503 Service Unavailable',
        504 => 'HTTP/1.1 504 Gateway Time-out',
        505 => 'HTTP/1.1 505 HTTP Version Not Supported',
    );
	
    header($http[$num]);
	header('Content-Type: application/json');
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header("Expires: 0"); // Proxies.
	
	$data = [
		'code' => $num,
        'error' => $http[$num]
	];
	
	echo json_encode($data, JSON_PRETTY_PRINT);
}

?>