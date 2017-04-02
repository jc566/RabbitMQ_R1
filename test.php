<?php

$mysqli = mysqli_connect("192.168.0.10", "ashish", "password", "user_info");
	if (mysqli_connect_errno($mysqli)) {
	    echo "Failed to connect to MySQL: " . mysqli_connect_error();
	} else {
            echo "it worked";
        }

?>