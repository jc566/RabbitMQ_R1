<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once "/home/frontend/rabbitMQ/infoFunctions.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$rpc_server = '192.168.1.102';


$connection = new AMQPStreamConnection($rpc_server, 5672, 'admin', 'password');
$channel = $connection->channel();


$channel->queue_declare('rpc_queue', false, false, false, false);

// function SignInVerify() {
// 	$mysql_server = '192.168.0.106';
// 	$mysqli = mysqli_connect($mysql_server, "badgers", "honey", "user_info", "3306");
// 	if (mysqli_connect_errno($mysqli)) {
// 	    echo "Failed to connect to MySQL: " . mysqli_connect_error();
// 	} else {
//            // echo "Connection worked";
//         }
// 
// 
// 	
// }

//delete this call:
//SignInVerify();

echo " [x] Awaiting RPC requests\n";
$callback = function($req) {
	//$n = intval($req->body);
	$requestIn = json_decode($req->body);
	
	//echo "var dump: ";	
	var_dump($requestIn);///this point is decoded
	/////////--------------//////////
	

	//Basic Info Function
	if($requestIn->RequestType == 'BasicInfo'){
		$data = array('Symbol' => $requestIn->Symbol);
		
		//GOES TO MOJOEJOE JOE JOE JOE
		$Result_ShowBasicInfo = showBasicInfo($data);
		//echo "var dump------- of $Result_ShowBasicInfo: " . var_dump($Result_ShowBasicInfo);
		
		$msg = new AMQPMessage(
		$Result_ShowBasicInfo,
		array('correlation_id' => $req->get('correlation_id'))
		);

		$req->delivery_info['channel']->basic_publish(
		$msg, '', $req->get('reply_to'));
		$req->delivery_info['channel']->basic_ack(
		$req->delivery_info['delivery_tag']);
	}

		//Buy Function
		elseif($requestIn->RequestType == 'Buy'){
		$data = array('Symbol' => $requestIn->Symbol, 'Quantity'=>$requestIn->Quantity);
		
		//GOES TO MOJOEJOE JOE JOE JOE
		$Result_BuyInfo = buyStock($data);
		//echo "xxxxxxxxxxxxxxxxxxxx------ Buy called -----xxxx";
		//var_dump($Result_ShowBasicInfo);
                $msg = new AMQPMessage(
		json_encode($Result_BuyInfo),
		
		array('correlation_id' => $req->get('correlation_id'))
		);
		echo var_dump($Result_BuyInfo);

		$req->delivery_info['channel']->basic_publish(
		$msg, '', $req->get('reply_to'));
		$req->delivery_info['channel']->basic_ack(
		$req->delivery_info['delivery_tag']);
	}

	//Sell Function
	elseif($requestIn->RequestType == 'Sell'){
		$data = array('Symbol' => $requestIn->Symbol);
		
		//GOES TO MOJOEJOE JOE JOE JOE
		$Result_SellInfo = sellStock($requestIn);
		echo "xxxxxxxxxxxxxxxxx------Sell called -----xxxxx";
		var_dump($Result_SellInfo);
		
		$msg = new AMQPMessage(
		json_encode($Result_ShowBasicInfo),
		array('correlation_id' => $req->get('correlation_id'))
		);

		$req->delivery_info['channel']->basic_publish(
		$msg, '', $req->get('reply_to'));
		$req->delivery_info['channel']->basic_ack(
		$req->delivery_info['delivery_tag']);
	}
	
	//DisplayPortfolio
	elseif($requestIn->RequestType == 'DisplayPortfolio'){
		$data = array('Username' => $requestIn->Username);
		
		//GOES TO MOJOEJOE JOE JOE JOE
		$Result_DisplayPortfolio = portfolioDB($data);
				
		$msg = new AMQPMessage(
		$Result_DisplayPortfolio,
		array('correlation_id' => $req->get('correlation_id'))
		);

		$req->delivery_info['channel']->basic_publish(
		$msg, '', $req->get('reply_to'));
		$req->delivery_info['channel']->basic_ack(
		$req->delivery_info['delivery_tag']);
	}
	
	
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>

