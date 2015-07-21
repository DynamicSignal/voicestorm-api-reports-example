<?php

include_once "config.php";

function voicestormApiRequest($requestType, $url, $requestData = null, $responseFile = null)
{
    $data = '';
    $requestUrl = $url;
    $f = fopen($GLOBALS['voicestormLogFile'], 'w');
    $url = $GLOBALS['voicestormBaseUrl'] . $url;
    $ch = curl_init();
    if (($requestData !== null) && array_key_exists('Basic', $requestData))
    {
        $header = array('Authorization: Basic ' . $requestData['Basic']);
        $data = array("grant_type" => "client_credentials");
    }
    else
    {
        $token = voicestormBearerToken();
        if (isset($token["code"]) && $token["code"] == "error")
        {
            return $token;
        }
        else
        {
            $header = array('Authorization: Bearer ' . $token);
            if (($requestData !== null) && ($requestType == "PUT" || $requestType == "POST"))
            {
                $data = json_encode($requestData, true);
                array_push($header, 'Content-Type: application/json');
            }
            if (($requestData !== null) && $requestType == "GET")
            {
                $data = http_build_query($requestData);
                $url = $url . "?" . $data;
            }
        }
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    if ($requestType != "POST")
    {
        if ($requestType == "PUT" || $requestType == "DELETE")
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
        }
    }
    if ($requestType == "PUT" || $requestType == "POST")
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $f);
    if ($requestType == "GET" && $requestUrl == "/reports/csv")
    {
        // now curl request for download file
        if (!file_exists($GLOBALS['reportCache']))
        {
            mkdir($GLOBALS['reportCache'], 0777, true);
        }
        $file = fopen($GLOBALS['reportCache'] . '/' . $responseFile . '.csv', "w+");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $file);
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            die('The cURL experienced technical difficulties');
        }
        else
        {
            $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($resultStatus != 200)
            {
                die('Request failed:' . $result);
            }
        }

        fclose($file);
        curl_close($ch);
        fclose($f);
        return "Success";
    }
    $result = curl_exec($ch);
    if (curl_errno($ch))
    {
        die('The cURL experienced technical difficulties');
    }
    else
    {
        $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resultStatus != 200)
        {
            if ($requestUrl == "/users")
            {
                $result = json_decode($result, true);
                return $result;
            }
            else
            {
                die('Request failed:' . $result);
            }
        }
    }
    curl_close($ch);
    fclose($f);
    return json_decode($result, true);
}

function voicestormBearerToken()
{
    $encAccess = urlencode($GLOBALS['voicestormAccessToken']);
    $encSecret = urlencode($GLOBALS['voicestormTokenSecret']);
    $cred = $encAccess . ":" . $encSecret;
    $baseCred = base64_encode($cred);
    $val = voicestormApiRequest("POST", "/oauth2/token", array("Basic" => $baseCred));
    if (isset($val["access_token"]))
    {
        return $val['access_token'];
    }
    else
    {
        return array("code" => "error", "message" => "Cannot get the Bearer Token");
    }
}

class FileCache
{

    protected $basePath;

    public function __construct($basePath)
    {
        if (!file_exists($basePath))
        {
            mkdir($basePath, 0777, true);
        }
        $this->basePath = $basePath;
    }

    public function add($key, $value)
    {
        $path = $this->getPath($key);
        file_put_contents($path, json_encode($value));
    }

    public function get($key)
    {
        $path = $this->getPath($key);
        if (file_exists($path))
        {
            return json_decode(file_get_contents($path), true);
        }
        else
        {
            return false;
        }
    }

    public function getPath($key)
    {
        if (!is_writable($this->basePath))
        {
            throw new Exception("Base path '{$this->basePath}' was not writable");
        }
        $dir = $this->basePath;
        if (!file_exists($dir))
        {
            mkdir($dir, 0777, true);
        }
        return $dir . '/' . $key . '.json';
    }

}

function createRows($duration, $rowCount)
{
    $row = 0;
    $displayCount = 0;
    $displayColumns = [];
    $display = '';
    $fc = new FileCache($GLOBALS['userCache']);
    $dataFile = fopen($GLOBALS['reportCache'] . '/' . $duration . '.csv', "r");
    while (($line = fgetcsv($dataFile)) !== false)
    {
        //Display only defined number of rows
        if ($row > 0 && $displayCount < $GLOBALS['displayRows'])
        {
            if ($displayCount == $GLOBALS['displayRows'] - 1)
            {
                $tmpPointer = ftell($dataFile); //get the filepointer while accessing last row
            }
            if ($row == 1)
            {
                $column = 0;
                if ($rowCount == 0)
                {
                    $tableHead = "<div class = 'row'><table class='table table-bordered'><thead>";
                }

                foreach ($line as $cell)
                {
                    if ($line[$column] == 'UserId')
                    {
                        $userIdColumn = $column;
                    }
                    //Only display the 'Member' field and the fields defined in the config
                    $isDisplayField = in_array(strtolower($line[$column]), array_map('strtolower', $GLOBALS['reportDisplayFields']));
                    if ($isDisplayField || ($column === 0 && !$isDisplayField))
                    {
                        $displayColumns [] = $column;
                        if ($rowCount == 0)
                        {
                            $tableHead.= "<th>" . $cell . "</th>";
                        }
                    }
                    $column++;
                }
                if ($rowCount == 0)
                {
                    $tableHead.= "</thead>";
                }
            }
            else
            {
                //Skip the displayed rows
                if ($rowCount >= $row - 1)
                {
                    $row++;
                    continue;
                }
                if ($rowCount == 0 && $row == 2)
                {
                    $display.= $tableHead;
                }
                $display.="<tr>";
                $displayCount++;
                foreach ($displayColumns as $field)
                {
                    if ($field === 0) // 'Member' field with 'Member URL'
                    {
                        $userId = $line[$userIdColumn];

                        if ($fc->get($userId))
                        {
                            
                            $user = $fc->get($userId);
                            if (array_key_exists('Square40', $user['profilePictureImages']))
                            {
                                $display.= "<td><img src='" . $user['profilePictureImages']['Square40']['url'] . "'>";
                            }
                            else
                            {
                                $display.= "<td><img src='userIcon40.png'>";
                            }
                            $display.= "<a href='" . $line[1] . "'</a>" . $user['displayName'] . "</td>";
                        }
                        else
                        {
                            if ($line[0])
                            {
                                $display.= "<td><img src='userIcon40.png'><a href='" . $line[1] . "'</a>" . $line[0] . "</td>";
                            }
                            else
                            {
                                $display.= "<td><img src='userIcon40.png'><a href='" . $line[1] . "'</a></td>";
                            }
                        }
                    }
                    else
                    {
                        $display.= "<td>" . $line[$field] . "</td>";
                    }
                }
                $display.= "</tr>";
            }
        }
        else
        {
            //Look if more users exists
            if ($row != 0)
            {
                //For the first parse append "Load more"
                if ($rowCount == 0)
                {
                    $display.="</table>"
                            . "</div>"
                            . "<div class='row load-button'>"
                            . "<input type='button' class='btn btn-success loadmore' value='Load more'>"
                            . "</div>";
                }
                return $display;
            }
        }
        $row++;
    }
    fclose($dataFile);
    if ($display === '')
    {
        echo "<div class='row'><h3>No results found</h3></div>";
    }
    $display.="<input type='hidden' name ='displayLoad' value='hide'/>"; //Hint to hide "Load more"
    return $display;
}

?>
