
<?php

echo("<pre>");

echo "google api php file loadede \n";
define('APPLICATION_NAME', 'Imperial Secrets');
define('CREDENTIALS_PATH', './credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/sheets.googleapis.com-php-quickstart.json
define('SCOPES', implode(
    ' ',
    array(
  Google_Service_Sheets::SPREADSHEETS)
));



/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        //$why = $client->refreshToken($refreshToken);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
//https://docs.google.com/spreadsheets/d/1OalnSHxppazXlbm8zP81FNXhu9ergGd8OddM29ZCJwY/edit?ts=5a73a967#gid=1260006595
$spreadsheetId = '1OalnSHxppazXlbm8zP81FNXhu9ergGd8OddM29ZCJwY';
$sheetGID = '1260006595';

$theActualSecretBit;
$inResponseTo;
$anythingToAdd;
$whatYear;
$whatCourse;


$row = 2;//topmost valid row
$nothingDeleted = true;
$rowCount = getRowCount();
echo "\nrow count $rowCount";
if (!$postOnlyApproved) {
    getRowValues($row, $theActualSecretBit, $inResponseTo, $anythingToAdd, $whatYear, $whatCourse);
} else {
    getNextApprovedPost($row, $theActualSecretBit, $inResponseTo, $anythingToAdd, $whatYear, $whatCourse, $nothingDeleted);
}

if (runAutomatedDeletionFilter($row, $theActualSecretBit) && $nothingDeleted) {
    //returns true if nothing was deleted
    echo "\n passesd all filters so calling api stuff";
    file_put_contents("postedSecrets.txt", $theActualSecretBit. "\n\n", FILE_APPEND);
    require "facebookapi.php";
    if ($postSuccessful) {
        echo "\n post was successful so deleting from sheets";
        deleteRow($row, "", $nothingDeleted);
    }
} else {
    echo "something was deleted so not trying to post";
}

function getRowValues(&$row, &$theActualSecretBit, &$inResponseTo, &$anythingToAdd, &$whatYear, &$whatCourse)
{
    global $service, $spreadsheetId;

    $range = "Form responses 1!A$row:F$row";
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);//oldest appear at top
    $values = $response->getValues();
    print_r($values);

    $theActualSecretBit = $values[0][1];
    echo "\n actual secret:" . $theActualSecretBit;
    //    $theActualSecretBit = str_ireplace("Paul Balaji", "Prince Harry", $theActualSecretBit);
    $inResponseTo = $values[0][2];

    $anythingToAdd = $values[0][3];

    $whatYear = trim($values[0][4]);
    //keep the spaces or it gets fucked and the 1st year becomes 1styear
    $whatCourse = trim($values[0][5]);
}


function getNextApprovedPost(&$row, &$theActualSecretBit, &$inResponseTo, &$anythingToAdd, &$whatYear, &$whatCourse, &$nothingDeleted)
{
    global $service, $spreadsheetId, $rowCount;

    echo "\nrow $row";
    if ($row >= $rowCount || $row > 10) {//change this
        echo "\n row greater or equal to row count or greater than 10 so returning";
        $nothingDeleted = false;
        return;
    }//don't wanna cause inf recursion.
    $params = array(
    // 'spreadsheetID' => $spreadsheetId,
   'includeGridData' => true,
   'ranges' => 'B'.$row

);
    $getCellInfo = $service->spreadsheets->get($spreadsheetId, $params);
    echo '<pre>', var_export($getCellInfo['sheets']['0']['data']['0']['rowData']['0']['0']['effectiveFormat']['backgroundColor'], true), '</pre>', "\n";

    $red = $getCellInfo['sheets']['0']['data']['0']['rowData']['0']['0']['effectiveFormat']['backgroundColor']['red'];
    $green = $getCellInfo['sheets']['0']['data']['0']['rowData']['0']['0']['effectiveFormat']['backgroundColor']['green'];
    $blue = $getCellInfo['sheets']['0']['data']['0']['rowData']['0']['0']['effectiveFormat']['backgroundColor']['blue'];
    echo"\nred $red green $green blue $blue";
    $secretFromCellInfo = $getCellInfo['sheets']['0']['data']['0']['rowData']['0']['0']['formattedValue'];
    if ($green == 1 && $red == 0 && $blue == 0) {
        echo "\nfound green so approved to post on row $row";
        getRowValues($row, $theActualSecretBit, $inResponseTo, $anythingToAdd, $whatYear, $whatCourse);
    }
    if ($green == 0 && $red == 1 && $blue == 0) {
        echo "\ncalling delet at row $row with secret $theActualSecretBit";
        deleteRow($row, $secretFromCellInfo, $nothingDeleted);
        // $row += 1;
        // getNextApprovedPost($row, $theActualSecretBit, $inResponseTo, $anythingToAdd, $whatYear, $whatCourse);
    }
    if ($green == 1 && $red == 1 && $blue == 1) {
        //cell is white
        $row += 1;
        getNextApprovedPost($row, $theActualSecretBit, $inResponseTo, $anythingToAdd, $whatYear, $whatCourse);
    }
}

function deleteRow($row, $theActualSecretBit, &$nothingDeleted)
{
    global $service, $spreadsheetId, $sheetGID;
    $nothingDeleted = false;
    echo "\n deleting secret at row $row with secret $theActualSecretBit";
    if ($theActualSecretBit != "") {
        file_put_contents("blockedSecrets.txt", $theActualSecretBit. "\n\n", FILE_APPEND);
    }
    $requests = [
  // Change the spreadsheet's title.
  new Google_Service_Sheets_Request([
    'deleteDimension' => [
      'range' => [
        'sheetId' => $sheetGID,
        'dimension' => "ROWS",
        'startIndex' => $row - 1,
        'endIndex' => $row
      ]
    ]
])
]; // start 16 end 17 causes 17th row to be deleted

    $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
  'requests' => $requests
]);
    $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
}

function runAutomatedDeletionFilter($row, $theActualSecretBit)
{
    if (
            stripos($theActualSecretBit, "suicide") !== false ||
            stripos($theActualSecretBit, "suicided") !== false ||
            stripos($theActualSecretBit, "suicidal") !== false ||
            stripos($theActualSecretBit, "depression") !== false ||
            stripos($theActualSecretBit, "depressed") !== false ||
            stripos($theActualSecretBit, "rape") !== false ||
            stripos($theActualSecretBit, "raped") !== false ||
            stripos($theActualSecretBit, "depressing") !== false ||
            stripos($theActualSecretBit, "killed yourself") !== false ||
            stripos($theActualSecretBit, "killed myself") !== false ||
            stripos($theActualSecretBit, "kill myself") !== false ||
            stripos($theActualSecretBit, "kill yourself") !== false ||
            stripos($theActualSecretBit, "faggot") !== false ||
            stripos($theActualSecretBit, "hang yourself") !== false ||
            stripos($theActualSecretBit, "hang myself") !== false ||
            stripos($theActualSecretBit, "hanging yourself") !== false ||
            stripos($theActualSecretBit, "kys") !== false ||
            stripos($theActualSecretBit, "hanging myself") !== false ||
            stripos($theActualSecretBit, "cum") !== false


        ) {
        deleteRow($row, $theActualSecretBit);
        return false;
    } else {
        return true;
    }
}

function getRowCount()
{
    global $service, $spreadsheetId;
    $params = array(
   'includeGridData' => false,
);
    $getCellInfo = $service->spreadsheets->get($spreadsheetId, $params);
    echo '<pre>', var_export($getCellInfo['sheets']['0']['properties']['gridProperties'], true), '</pre>', "\n";
    return $getCellInfo['sheets']['0']['properties']['gridProperties']['rowCount'];
}

echo "\nthe actual secret bit $theActualSecretBit";
echo "\nend of google api stuff\n";
