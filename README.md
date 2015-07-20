#Introduction

VoiceStorm is a platform that allows you to create advocacy communities for a brand, organization, or cause. Community managers source and distribute approved content to members. Members share the content on their social channels, thereby amplifying the brand message. Learn more at http://www.dynamicsignal.com.

##Purpose of this test package:
While you can use the stock VoiceStorm manager and member hub with any VoiceStorm community, that allows to view the reports on Manager Hub and leaderboard for members. Few organizations might want to disclose more reports data to members. This test package is intended to help you do just that.

This test package includes server to server calls to get reports and users related to those reports as below:

<ol>
<li>Authenticate server side.</li><li>Look up reports in VoiceStorm by start and end date.</li><li>If there are reports generated within the given dates get the users information such as display name, profile image.</li><li>Display the reports in a table.</li><li>Able to change the columns and the number of rows to be displayed.</li>
</ol>

You can use it as an example/guide to display reports data to members or to use internally. This test package can be extended in many ways as you want like, adding the ability to choose custom start and end date by the user etc.

##Download, install, and run to sync user data:

To display reports data of your own VoiceStorm instance, you will need to download the sample code and modify it to point at your own VoiceStorm instance. The guide below will walk you through this process in 4 steps:

1.    **Set up your webserver** to run and install the test package. Once installed properly, the test package will run against your VS instance.
2.	Contact Dynamic Signal and **get your own VS instance** and API credentials.
3.	Modify the test package in order to **point it at your new VS instance.**
4.	**Test it!**

The sample site uses REST APIs to make authentic calls to the server. Further reference docs and required documentation are available at http://dev.voicestorm.com/. Once you go through this guide, you should have enough background to display more or less information of reports data to users.

###Step 1: Set up webserver/environment

<ol>
<li>Install WAMP, or comparable webserver.</li>
<ol>
<li>Actual requirements are:</li>
<ol>
<li>Webserver</li>
<li>PHP</li>
<li>CURL</li>
<li>HTTPS cert</li>
</ol><li>No database is required</li>
</ol><li>Test the environment:</li>
<ol>
<li>Ensure CURL is installed.</li>
<li>Ensure PHP is installed.</li>
</ol><li>Download the code to the desired directory within the WAMP file structure.</li>
</ol>

You should now be able to run the code. Try it by opening up your install location in a browser. Next, let’s get you set up on your own VoiceStorm instance.

###Step 2: Obtain your VoiceStorm instance

You will need to contact DS and request an instance of VoiceStorm with API access.  Be sure you get the following from the DS rep:

<ol>
<li>URL for the new community ([example].voicestorm.com)</li>
<li>The Admin -> API should be visible in the manager application ([example].voicestorm.com/manage/api), and this information should be available in that tab:</li>
<ol>
<li>Access Token</li>
<li>Token Secret</li>
<li>REST API Base URL</li>
</ol>
</ol>

###Step 3: Modify test package to point at your own VoiceStorm instance

Download the code to your machine, and make the following changes, using tokens and URLs found at Admin -> API ([example]voicestorm.com/manage/api).

<table>
<tr>
<th>File</th>
<th>Code Line</th>
<th>From API Page</th>
</tr>
<tr>
<td rowspan="3">config.php</td>
<td>$voicestormAccessToken</td>
<td>Access Token</td>
<tr>
<td>$voicestormTokenSecret</td>
<td>Token Secret
</tr>
<tr>
<td>$voicestormBaseUrl</td>
<td>REST API Base URL</td>
</tr>
</table>

**Examples**

```php
$voicestormAccessToken="XXXXXXXXXXXXXXXXXXXX";
$voicestormTokenSecret= "XXXXXXXXXXXXXXXXXXXX";
$voicestormBaseUrl="https://[example].voicestorm.com/v1";
```

###Step 4: Testing

Please test the following:

Now you should be able to see the Last 30 days reports.
Must be able to see different results by changing the duration in the dropdown.
Also, you can modify the columns and rows that are to be displayed by changing $reportDisplayFields (columns that are displayed) and $displayRows (Number of rows that are to be displayed) in config.php.


**Have any questions?**

We are eager to hear them. Email us at [info@dynamicsignal.com](mailto:info@dynamicsignal.com)
