<?PHP

function qrz_scan($searchTerms, $timeframe){
		$URL = "https://forums.qrz.com/index.php?forums/ham-radio-gear-for-sale.7.rss";


		$qrz_RSS = get_web_page($URL);


		$link = mysql_connect('localhost', 'root', '')
		or die('Could not connect: ' . mysql_error());
		mysql_select_db('hamscan') or die('Could not select database');

		date_default_timezone_set("America/New_York");
		echo "Running qrz hamScan at " . date("Y-m-d h:i:sa") . "\n";

		// Create DOM from URL or file
				$html = str_get_html($qrz_RSS["content"]);

			foreach($searchTerms as $term){

				//get list of items
				foreach($html->find('item') as $item) {
					//checking search term against item
					if(search_qrz($term, $item)){
						//get listing title
						$listingTitle = mysql_real_escape_string($item->find('title', 0)->innertext);
						$parsedURL = explode('.', str_replace('/', "", $item->find('guid',0)->innertext));

						//get listing URL
						$listingURL = mysql_real_escape_string($item->find('guid',0)->innertext);

						//get listing ID
						$listingID = mysql_real_escape_string($parsedURL[count($parsedURL)-1]);

						//add to database if its new!
						if(!check_qrz($listingID)){
							echo "inserting ListingID: " . $listingID . "\n";
							$query = "INSERT INTO hamScan_qrz (id, search_term, title, url) values ($listingID, '$term', '$listingTitle', '$listingURL')";
							$result = mysql_query($query) or die('Query failed: ' . mysql_error());
						}
					}
				}
			}

		//query to see if there are any updates
		$query = "SELECT img_url, category, title, description, url FROM hamScan_qrz WHERE last_update >= DATE_SUB(NOW(),INTERVAL $timeframe HOUR)";

		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		$results = array();
		while($row = mysql_fetch_assoc($result))
		{
			$results[] = $row;
		}

		return $results;
}

//searching to see if a search term is inside the page
function search_qrz($needle, $haystack){
	if (stripos($haystack, $needle) !== false) {
		return true;
	}
	return false;
}

//check to see if a listing already exists
//returns boolean
function check_qrz($id){

// Performing SQL query
	$query = "SELECT * FROM hamScan_qrz WHERE id = $id";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = false;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = true;
	}
	return $found;

}

//check to see if a listing that already exists has updated contents
//returns boolean
function check_qrz_update($id, $description){

// Performing SQL query
	$query = "SELECT * FROM hamScan_qrz WHERE id = $id AND description = '$description'";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = true;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = false;
	}
	return $found;

}

function get_web_page( $url )
{
	    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

	        $options = array(

			        CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
				CURLOPT_POST           =>false,        //set to GET
				CURLOPT_USERAGENT      => $user_agent, //set user agent
				CURLOPT_HTTPHEADER     => array(file_get_contents('/home/pi/qrz_cookie.txt')),
				CURLOPT_RETURNTRANSFER => true,     // return web page
				CURLOPT_HEADER         => false,    // don't return headers
				CURLOPT_FOLLOWLOCATION => true,     // follow redirects
				CURLOPT_ENCODING       => "",       // handle all encodings
				CURLOPT_AUTOREFERER    => true,     // set referer on redirect
				CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
				CURLOPT_TIMEOUT        => 120,      // timeout on response
				CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
				);

	        $ch      = curl_init( $url );
	        curl_setopt_array( $ch, $options );
		    $content = curl_exec( $ch );
		    $err     = curl_errno( $ch );
		        $errmsg  = curl_error( $ch );
		        $header  = curl_getinfo( $ch );
			    curl_close( $ch );

			    $header['errno']   = $err;
			        $header['errmsg']  = $errmsg;
			        $header['content'] = $content;
				    return $header;
}
