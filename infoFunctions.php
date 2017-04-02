<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;


//$x = array("Symbol"=>"atvi");

/***********
Local Tests*
***********/
//buyStock($x);
//calcPriceChange($x);
//calcPercentChange($x);
//netGain($x);
//showBasicInfo($x);
//showLast7($x);
//showLast30($x);

//portfolioDB()
$mysql_server = '192.168.1.103';
    
$mysqli = new mysqli($mysql_server, "badgers", "honey", "user_info");
// Check connection
if ($mysqli->connect_error) 
{
    die("Connection failed: " . $mysqli->connect_error);
} 
else 
{
    echo "connected";
}


/*****************************************
Connects to API and retrieves information*
*****************************************/
function getInfo($string){
    $sym = $string;
    
    //Create a request but don't send it immediately
    $client = new Client();

    //$Client is server we are using to connect server API
    $client = new GuzzleHttp\Client(['base_url' => '192.168.1.154']);

    //This 'page' is the one we use to gather Stock Information
    $response = $client->get('192.168.1.154:9090/stocks?sym='.$sym);
    
    $getInfo = $response->getBody();
    
    return $getInfo;
}
/*************************************************
Get the Quantity/Ticker requested from User input*
Run calculations to give Total Value             *
*************************************************/
function buyStock($data){
    //Local Testing
    $sym = $data["Symbol"];
    
    //$qty = $data["Quantity"];
    //Retrieve the Queries
    //$sym = $data->Symbol;
    //$qty = $data->Quantity;
    
    /*Local Testing*/
    $qty = 10;
    
    //grab the information needed
    $jsonObj = getInfo($sym);
    //decode the structure
    $newObj = json_decode($jsonObj);
    //Single out the Closing price aka Current Price
    $currentCost = $newObj->Close[0];
    //var_dump($newObj->Close[0]); //display the closing price

    //$username is either user input or given from DB
    $username = 'amm99';
    //$purCost is to store a value in DB, ex: $purCost = API->currentCost;
    $purCost = $currentCost;
    
    //Check to see if there are quantities in the DB already.
    portfolioDB($purCost,$qty,$username,$sym);
    //Calculate the total value of your purchase. Cost * Shares
    $totalValue = $purCost * $qty;
    
    //make a new object to store data to return? 
    $returnObj = array("TotalValue"=>$totalValue);
    //example of how to single out a value is Below
    //var_dump($returnObj["TotalValue"]); 
    
    
    
    //echo var_dump($returnObj);
    //returns an array
    return (json_encode($returnObj));
    //returns an json object
    //return (json_encode($returnObj));
}
function portfolioDB($data)
{
    $username_input = $data["Username"];
    
    //purpose is to display portfolio
    $mysql_server = '192.168.1.103';
    
    $mysqli = new mysqli($mysql_server, "badgers", "honey", "user_info");
    

    $qry = "Select * from portfolio where username='$username_input'";
    $result = $mysqli->query($qry);
    
    $stackSymbol = array();
    $stackPrices = array();
    $stackQty = array();
    
    if($result->num_rows > 0){ //if there is something in the DB then...
        while ($row = $result->fetch_assoc()){
            
            array_push($stackSymbol, $row['stockSymbol']);
            array_push($stackPrices, $row['price']);
            array_push($stackQty, $row['qty']);
        }
    
    
    }//just in case there should probably be a condition for when the DB is empty. 
    $returnObj = array('Stocks' => $stackSymbol, 'Prices' => $stackPrices, 'Quantity' => $stackQty);
    var_dump($returnObj);
    return (json_encode($returnObj));
    
    $mysqli.close();

    
}/********************************************************
Add a new entry into the Database with following Info:  *
Username, Stock Ticker, Quantity, Purchased Price       *
********************************************************/
function addToPortfolioDB($username_input,$purchaseCost,$qty,$symbol)
{
   
    $mysql_server = '192.168.1.103';
   
    $mysqli = new mysqli($mysql_server, "badgers", "honey", "user_info");
   
   
    $qry = "Insert into portfolio (username, stockSymbol, qty, price) values('$username_input','$symbol','$qty','$purchaseCost')";
    $result = $mysqli->query($qry);
   

    echo "Added into DB";
   //$mysqli.close();
   
}
/************************************************************
Sell Stocks in your portfolio                               *
So obtain the total value of the stock order you are selling*
Then delete the info inside the portfolio DB,               *
Then add the stored total value into Bank Account Balance   *
************************************************************/
function sellStock($data){
    //retrieve data and set variables accordingly
    $sym = $data["Symbol"];
    $username = $data["Username"];
    $qty = $data["Quantity"];
   
    //grab the information needed
    $jsonObj = getInfo($sym);
    //decode the structure
    $newObj = json_decode($jsonObj);
    //Single out the Closing price aka Current Price
    $currentCost = $newObj->Close[0];
    //var_dump($newObj->Close[0]); //display the closing price
   
    //Calculate the total value of your sell.
    $totalValue = $currentCost * $qty;
   
    //Call addtoAccountBalance and add $totalValue to the account balance
    addtoAccountBalance($username,$totalValue,$sym);
   
    //Call deleteFromPortfolioDB and delete the previous entry
    //deleteFromPortfolioDB($username
   
   
}
/*
Delete a entry from the Database.This is a tad trick however.
FOR NOW:
Since we can have multiple purchase orders with the same ticker,
we are going to compare the username, ticker and the purchased price
assuming that there will never be another instance where it repeats
with this level of specificity.
*/
function deleteFromPortfolioDB($username_input,$purchaseCost,$qty,$symbol)
{
    $mysql_server = '192.168.1.103';
   
    $mysqli = new mysqli($mysql_server, "badgers", "honey", "user_info");
   
   
    $qry = "delete from portfolio where username='$username_input' and stockSymbol='$symbol' and price='$purchaseCost'";
    $result = $mysqli->query($qry);
   
}
/*
Add totalvalue to the account balance
So what we need to do in this function..

Add the total quantity of requesterd Ticker in the account
Compare that total to the account amount asked to sell, confirm its possible
Store the sellingQuantity, remove the sellingQuantity from portfolio.
Multiply the sellingQuantity by CurrentCost and store Value in SellTotalValue.
Delete the entries from Portfolio as needed, update values in entries if needed as well.
Update Bank table's Balance column with the pre-existing value PLUS SellTotalValue.
*/
function addToAccountBalance($username,$soldValue,$stockTicker)
{
   
    $mysql_server = '192.168.1.103';
   
    $mysqli = new mysqli($mysql_server, "badgers", "honey", "user_info");
   
    $findBalQry = "SELECT * FROM portfolio where username='$username' and stockSymbol='$stockTicker'";
    $findBalResult = $mysqli->query($findBalQry);
   
   
   
    $qry = "UPDATE bank SET balance="$newBalance" where username='$username'";
    $result = $mysqli->query($qry);
}
   
/*****************************
Returns company info & prices*
*****************************/
function showBasicInfo($data){
    $sym = $data["Symbol"];

    $symbol = getInfo($sym);

    return $symbol;
}
/************************************
Returns the last 7 days Price Info  *
By Default, we grab the last 30 days*
************************************/
function showLast7($data){
    $sym = $data["Symbol"];
    $symbol = getInfo($sym);
    $newObj = json_decode($symbol);

    //How many days are we returning?
    $days = 7;

    //Populate a new object with the necesary information
    for ($i = 0; $i < 2;$i++)
    {
        $jsonObj['Open'][$i] = json_encode(array(array("Open"=>$newObj->Open[$i])));
        $jsonObj['Close'][$i] = json_encode(array(array("Close"=>$newObj->Close[$i])));
        $jsonObj['High'][$i] = json_encode(array(array("High"=>$newObj->High[$i])));
        $jsonObj['Low'][$i] = json_encode(array(array("Low"=>$newObj->Low[$i])));
    }
    //Example of how to display the Data
    for ($i = 0; $i < 2; $i++)
    {
        var_dump($jsonObj['Close'][$i]); //this is how to access specific info
    }
    //return the object
    return $jsonObj;
}
/***********************************
Returns the last 30 Days Price info  *
By Default we grab the last 30 Days*
***********************************/
function showLast30($data){
    $sym = $data["Symbol"];
    $symbol = getInfo($sym);
    $newObj = json_decode($symbol);

    //How many days are we returning?
    $days = 30;

    //Populate a new object with the necesary information
        for ($i = 0; $i < $days;$i++)
        {
            $jsonObj['Open'][$i] = json_encode(array(array("Open"=>$newObj->Open[$i])));
            $jsonObj['Close'][$i] = json_encode(array(array("Close"=>$newObj->Close[$i])));
            $jsonObj['High'][$i] = json_encode(array(array("High"=>$newObj->High[$i])));
            $jsonObj['Low'][$i] = json_encode(array(array("Low"=>$newObj->Low[$i])));
        }
        //Example of how to display the Data
        for ($i = 0; $i < 2; $i++)
        {
            var_dump($jsonObj['Close'][$i]); //this is how to access specific info
        }
    //return the object
    return $jsonObj;
}

function calcPriceChange($data){
    $username_input = $data["Username"];
    //grab the symbol we are looking for
    $sym = $data["Symbol"];
   
    $jsonObj = getInfo($sym);
    //decode the structure
    $newObj = json_decode($jsonObj);
    //Single out the Closing price aka Current Price
    $currentCost = $newObj->Close[0];
    //var_dump($currentCost);
    //make connection to SQL server
    $mysql_server = '192.168.1.103';
   
    $mysqli = new mysqli($mysql_server, "badgers", "honey", "user_info");
   
    //find the items that are attached to username and requested Ticker
    $qry = "Select * from portfolio where username='$username_input' and  stockSymbol='$sym'";
    $result = $mysqli->query($qry);
   
    //make empty arrays to hold data
    $stackSymbol = array();
    $stackPrices = array();
   
    if($result->num_rows > 0){
        while ($row = $result->fetch_assoc()){
           
            array_push($stackSymbol, $row['stockSymbol']);
            array_push($stackPrices, $row['price']);
        }
    }
   
    $SQLresultObj = array('Stocks' => $stackSymbol, 'Prices' => $stackPrices);
       
    $priceChange = array();
    $i= 0;
    //grab length of the array
    $arrayLength = count($SQLresultObj);
    foreach ($SQLresultObj["Prices"] as $purCost)
    {
            //if the Purchase Cost is greater than Current, you lost money
        if($purCost > $currentCost)
        {
            //calculate the Price Change in $ amount.
            $priceChange[$i] = $purCost - $currentCost;
            $priceChange[$i] = -1 * abs($priceChange);
5            $i++;
        }
        //if Purchase Cost is less than Current, you gained money
        elseif($purCost < $currentCost)
        {
            //calculate the Price Change in $ amount.
            $priceChange[$i] = ($currentCost - $purCost);
            $i++;
        }
    }
    //make new object to store data to return
    $returnObj = array("PriceChg"=>$priceChange);
    //example of how to access data below
    //var_dump($returnObj["PriceChg"]);
    return (json_encode($returnObj));
    //Close Database Connection
    $mysqli.close();
}

function calcPercentChange($data){
    $sym = $data["Symbol"];
    //grab the information needed
    $jsonObj = getInfo($sym);
    //decode the structure
    $newObj = json_decode($jsonObj);
    //Single out the Closing price aka Current Price
    $currentCost = $newObj->Close[0];

    //$purCost is coming from the DB
    $purCost = 150;

    //if currentCost is greater than purchase, you gained money
    if($currentCost > $purCost)
    {
        $percentChange = (($currentCost - $purCost) / $purCost) * 100;
    }
    //if currentCost is less than purchase, you lost money
    elseif($currentCost < $purCost)
    {
        $percentChange = (($purCost - $currentCost) /$purCost) * 100;
        $percentChange = -1 * abs($percentChange);
    }
    //make new object to store data to return
    $returnObj = array("PercentChg"=>$percentChange);
    //example of how to access data below
    var_dump($returnObj["PercentChg"]);
    //return the object
    return ($returnObj);

}

function netGain($data){
    $sym = $data["Symbol"];
    //grab the information needed
    $jsonObj = getInfo($sym);
    //decode the structure
    $newObj = json_decode($jsonObj);
    //Single out the Closing price aka Current Price
    $currentCost = $newObj->Close[0];

    $purCost = 150;

    //if Purchase Cost is greather than Current, you Lost money
    if($purCost > $currentCost)
    {
        $totalGL = ($purCost - $currentCost) / $currentCost;
        $totalGL = -1 * abs($totalGL);
    }
    //if Purchase Cost is less than Current, you Gained money
    elseif($purCost < $currentCost)
    {
        $totalGL = ($purCost - $currentCost) / $currentCost;
    }
    //make new object to store data to return
    $returnObj = array("totalGL"=>$totalGL);
    //example of how to access data below
    var_dump($returnObj["totalGL"]);
    //return the object
    return ($returnObj);
    }

    //$mysqli_close($mysqli);
?>