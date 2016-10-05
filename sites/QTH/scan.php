<?PHP

function qth_scan($searchTerms, $timeframe){
		$URL = "http://swap.qth.com/search-results.php?keywords=KEYWORDS&fieldtosearch=TitleOrDesc";

		$link = mysql_connect('localhost', 'root', '')
		or die('Could not connect: ' . mysql_error());
		mysql_select_db('hamscan') or die('Could not select database');

		date_default_timezone_set("America/New_York");
		echo "Running qth hamScan at " . date("Y-m-d h:i:sa") . "\n";

			foreach($searchTerms as $term){
				$actualURL = str_replace("KEYWORDS", urlencode($term), $URL);

				// Create DOM from URL or file
				$html = file_get_html($actualURL);
				// Find all images 
				foreach($html->find('dt') as $rawTitle) {
					$category = mysql_real_escape_string($rawTitle->find('b', 0)->find('font', 0)->innertext);
					$title = mysql_real_escape_string($rawTitle->find('b', 0)->find('font', 1)->innertext);
					$title = substr($title, 2);

					//get description:
					$description = mysql_real_escape_string($rawTitle->next_sibling()->last_child()->innertext);
					$meta = $rawTitle->next_sibling()->next_sibling()->last_child()->innertext;
					$listingID = mysql_real_escape_string(trim(substr($meta, 9, 8)));
					$listingLink = "http://swap.qth.com/view_ad.php?counter=". $listingID; 

					//get image:			
					$imageURL = null;
					if (strpos($rawTitle, 'camera_icon.gif') !== false) {
						$imageURL = "http://swap.qth.com/segamida/" .$listingID. ".jpg";
					}

					//add to database if its new!
					if(!check_qth($listingID)){
						echo "inserting ListingID: " . $listingID . "\n";
						$query = "INSERT INTO hamScan_swap_qth (id, search_term, title, img_url, description, category, url) values ($listingID, '$term', '$title', '$imageURL', '$description', '$category', '$listingLink')";
						$result = mysql_query($query) or die('Query failed: ' . mysql_error());
					}
					else if(check_qth_update($listingID, $description)){//already exists, but was it updated?
						echo "updating ListingID: " . $listingID . "\n";
						$query = "UPDATE hamScan_swap_qth set description =  '$description', title = '$title' where id = $listingID";
						$result = mysql_query($query) or die('Query failed: ' . mysql_error());
					}
				}
			}


		//query to see if there are any updates
		$query = "SELECT img_url, category, title, description, url FROM hamScan_swap_qth WHERE last_update >= DATE_SUB(NOW(),INTERVAL $timeframe HOUR)";

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
function check_qth($id){

// Performing SQL query
	$query = "SELECT * FROM hamScan_swap_qth WHERE id = $id";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = false;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = true;
	}
	return $found;

}

//check to see if a listing that already exists has updated contents
//returns boolean
function check_qth_update($id, $description){

// Performing SQL query
	$query = "SELECT * FROM hamScan_swap_qth WHERE id = $id AND description = '$description'";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = true;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = false;
	}
	return $found;

}
