<?
	
function weatherget($city)
{
        $url="http://weather.service.msn.com/data.aspx?weadegreetype=C&culture=ko-KR&weasearchstr=$city";
        $result = simplexml_load_file($url);
        $now = $result->weather[0]->current->attributes()->temperature;
        $skytext = $result->weather[0]->current->attributes()->skytext;
        $low = $result->weather[0]->forecast[0]->attributes()->low;
        $high = $result->weather[0]->forecast[0]->attributes()->high;
 
 
        return "온도 : $now, 최저/최고 온도 : $low/$high, 상태 : $skytext";
}


function UploadPost($token, $message, $id) { //require Facebook friend's timeline post permission (if wall to friend's timeline)
	$attachment =  array(
	'access_token' => $token,
	'message' => $message);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,'https://graph.facebook.com/'.$id.'/feed');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $attachment);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //to suppress the curl output 
	$result = curl_exec($ch);
	return $result;
}	

function MessageReturn($message) {
	switch (true) {
		

		case strstr($message,'날씨는?'):
			$city = str_replace(" 날씨는?", "", $message);
			return "$city 날씨는" . weatherget($city);

		default:
			return "false";
	}
}
function json_to_xml($json)
{
    include_once("XML/Serializer.php");
    
    $options = array (
      'addDecl' => TRUE,
      'encoding' => 'UTF-8',
      'indent' => '  ',
      'rootName' => 'json',
      'mode' => 'simplexml'
    );

    $serializer = new XML_Serializer($options);
    $obj = json_decode($json);
    if ($serializer->serialize($obj)) {
        return $serializer->getSerializedData();
    } else {
	return null;        
	}
}
function XMLMessageGet($xml) {
	$result = simplexml_load_string($xml);
	
	 
	$message=$result->data[0]->message;
	return $message;

}

 
function gettimejson($json) {
	$i=0;
	foreach($json->data as $node) {
		$item[$i] = $node->created_time;	
		$message[$i] = $node->message;	
		$who[$i] = $node->from->name;
		$i++;
	}
//	return $item[1];		
	
	$test = array_multisort($item, SORT_DESC, $arr);
	$var = print_r($arr, true);
//	return "who : " .$who[$test] . $test . " : " .$item[$test] . "message : " .$message[$test] . "max : " .$i;
	return $var;
	
}
function compareXMLDoc($xml)
{
	if ($xml == $_SESSION['xml']) {
		return "equal";
	} else {
				
		$_SESSION['xml'] = $xml;
		return "not";
	}
}
function XMLUserNameGet($xml) {
	$result = simplexml_load_string($xml);
	$username=$result->data[0]->from->name;
	return $username;
}
	require 'facebook.php';
	
	$page_id="FACEBOOK_PAGE_ID";
	$page_access_token = "FACEBOOK_ACCESS_TOKEN";
	$comment_url = "FACEBOOK_COMMENT_URL";
	$config = array(
      'appId' => 'FACEBOOK_APP_ID',
      'secret' => 'FACEBOOK_SECERT',
      'fileUpload' => false
  );
	$params = array(
		"access_token" => "$page_access_token"
	);	
	$facebook = new Facebook($config);


	$beforexml = "";
        $set="2";
	date_default_timezone_set('Asia/Seoul');
	function date_compare($a, $b){
		$t1 = strtotime($a);
		$t2 = strtotime($b);
		return ($t2 - $t1);
  	 };


	while($params) {
		
		$response = $facebook->api(
		    "/" . $comment_url ."/comments?limit=1000",
		    "GET",
	   		 array (
	      		 'summary' => false,
	      		  'filter' => 'toplevel',
	   		));

	
	$json = json_encode($response);
	$bejson = $json;	
	$xml = json_to_xml($json);
	
	if ($set == "2") {
		$beforexml = $xml;
		$set = "1";
	}

	$json2 = json_decode($bejson, true);
	$json = json_decode($bejson);

	if ($beforexml == $xml) {
		
	} else {
		$set = "2";
		$xml = str_replace($beforexml , "" , $xml);

		 $count = count($json2['data']);

                for ($i=0; $i<$count; $i++) {
			$data = $json2['data'][$i]['created_time'];
			$time2 = explode("T", $data);
			$time3 = explode("+", $time2[1]);
			$last = $time2[0] . ' ' .$time3[0];
			
                        $create_time[$i] = $last;
			echo $create_time[$i] ."\n";
			$text[$i] = $json2['data'][$i]['message'] . "/" . $json2['data'][$i]['from']['name'] . "/" . $json2['data'][$i]['from']['id'];
			
	
                }
		
                usort($create_time, 'date_compare');
		
		
                $message = explode("/",$text[0]);
		$result = MessageReturn($message[0]);
		echo "result : " . $message[0];
		$who = $message[1];
		$id = $message[2];
		
			
		//echo $message[1];	
		
		if ($result == "false") {
		} else {

			echo "봇 요청자 : " . $message[2] . ',' .$id .", GET : " . $message[1] . " -> POST " . $result . "\n";
			$response = $facebook->api("/".$comment_url."/comments","POST",
                                        array (
                                              'access_token' => $page_access_token,
                                                       'message' =>   $result,
                                       ));
		
		}
		

	}
	
	

	usleep(3000);

	}	
?>
