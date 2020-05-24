<?PHP
include "simple_html_dom.php";
include "sites/QTH/scan.php";
include "sites/QRZ/scan.php";

$timeframe = 1; //hours

//get the list of search terms, ingest them into an array:
$searchTerms = file(__DIR__ . '/searchTerms.txt', FILE_IGNORE_NEW_LINES);

//iterate through each site's scan file and pass search terms into it
$qthResults = qth_scan($searchTerms, $timeframe);
$qrzResults = qrz_scan($searchTerms, $timeframe);


$combinedResults = array_merge($qthResults, $qrzResults);

//generate email report
if(sizeof($combinedResults) > 0){
	send_email($combinedResults);
}




function send_email($combinedResults){
	$to = 'amajot@gmail.com';

	$subject = 'HamScan Results!';

	$headers = "From: noreply@hamlistings.com" . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	$message = generate_email_body($combinedResults);

	mail($to, $subject, $message, $headers);

}

function generate_email_body($combinedResults){
	$message = '<html><body>';
	$message .= "<table border=1px>";
	$message .= "<tr>";
	$message .= "<td>Pic</td>";
	$message .= "<td>Description</td>";
	$message .= "</tr>";

    foreach($combinedResults as $line){
		$message .= "<tr>";
		$count = 0;
		foreach ($line as $col_value) {
			if($count==0){//link image
				$message .= "<td><img src='$col_value' style='width:100px;height:100px;'></td><td>";
			}
			else if($count==4){//link listingURL
				$message .= "<br/><a href='$col_value'>LINK</a>";
			}
			else{
				$message .= "$col_value<br/>";				
			}
			$count++;
		}
		$message .= "</td>";
		$message .= "</tr>";
	}
	$message .= "</table>";
	$message .= "</html></body>";
	return $message;
}
