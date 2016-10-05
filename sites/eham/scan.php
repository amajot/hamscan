<?PHP

function eham_scan($searchTerms, $timeframe){
		$URL = "http://www.eham.net/classifieds/results";
		$POST_PARAMS = array();


		$link = mysql_connect('localhost', 'root', '')
		or die('Could not connect: ' . mysql_error());
		mysql_select_db('hamscan') or die('Could not select database');

		date_default_timezone_set("America/New_York");
		echo "Running eham hamScan at " . date("Y-m-d h:i:sa") . "\n";

		foreach($searchTerms as $term){
			$data = array(
					'catid' => null,
					'date_time' => 7,
					'displaycnt' => 20,
					'search' => $term
				);

			$ch = curl_init($URL); // your curl instance
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_COOKIE, 'ehamsid');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			
			$curlResult = curl_exec($ch); // request's result
			
			$html = new simple_html_dom(); // create new parser instance
			$html->load($curlResult); // load and parse previous result

			//3rd table displays "No records found." if no new results
			$resultCountTable = $html->find('table', 3); 
			if (strpos($resultCountTable, 'No records found.') !== false) {
				continue;
			}
			$initialResultsTables = $html->find('table', 4);
			$parsedResultsTables = $initialResultsTables->find('table');
			array_pop($parsedResultsTables);

			foreach($parsedResultsTables as $result){
					//get listing category
					$listingCategory = mysql_real_escape_string($result->children(0)->last_child()->find('p', 0)->plaintext);

					//get listing ID 
					$parsedURLArray = explode('/', $result->children(1)->first_child()->first_child()->find('a', 0)->href);
					$listingID = mysql_real_escape_string($parsedURLArray[count($parsedURLArray)-1]);

					//get image thumbnail
					$listingThumbURL = "http://www.eham.net/data/classifieds/images/$listingID.t.jpg";

					//get listing title
					$listingTitle = mysql_real_escape_string($result->children(1)->first_child()->first_child()->plaintext);

					//get listing URL
					$listingURL = "http://www.eham.net/classifieds/detail/$listingID";

					//get listing description
					$listingDescription = mysql_real_escape_string($result->children(1)->first_child()->children(1)->plaintext);

					//add to database if its new!
					if(!check_eham($listingID)){
						echo "inserting ListingID: " . $listingID . "\n";
						$query = "INSERT INTO hamScan_eham (id, search_term, title, img_url, description, category, url) values ($listingID, '$term', '$listingTitle', '$listingThumbURL', '$listingDescription', '$listingCategory', '$listingURL')";
						$result = mysql_query($query) or die('Query failed: ' . mysql_error());
					}
					else if(check_eham_update($listingID, $listingDescription)){//already exists, but was it updated?
						echo "updating ListingID: " . $listingID . "\n";
						$query = "UPDATE hamScan_eham set description =  '$listingDescription', title = '$listingTitle' where id = $listingID";
						$result = mysql_query($query) or die('Query failed: ' . mysql_error());
					}

			}//foreach

		}//foreach


		//query to see if there are any updates
		$query = "SELECT img_url, category, title, description, url FROM hamScan_eham WHERE last_update >= DATE_SUB(NOW(),INTERVAL $timeframe HOUR)";

		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		$results = array();
		while($row = mysql_fetch_assoc($result))
		{
			$results[] = $row;
		}

		return $results;
}

//check to see if a listing already exists
//returns boolean
function check_eham($id){

// Performing SQL query
	$query = "SELECT * FROM hamScan_eham WHERE id = $id";
	$result = mysql_query($query) or die('duplicate check Query failed: ' . mysql_error());
	$found = false;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = true;
	}
	return $found;

}

//check to see if a listing that already exists has updated contents
//returns boolean
function check_eham_update($id, $description){

// Performing SQL query
	$query = "SELECT * FROM hamScan_eham WHERE id = $id AND description = '$description'";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = true;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = false;
	}
	return $found;

}
