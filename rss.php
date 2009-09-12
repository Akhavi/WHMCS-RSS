<?php
// WHMCS Ticket RSS Feed
// (c) 2009 Eden Akhavi
//
// No warranty expressed or implied
//
// Released under GPL v3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// Id: rss.php, v 1.0 2009-09-12


// Tickets to display in RSS
$constTicketsToDisplay = 10;

include('class.rss.php');
include("../dbconnect.php");
include("../includes/functions.php");

if (!isset($_REQUEST['Username']) or !isset($_REQUEST['Password']))
{
    die("Append User and Password to request string");
}

$tempAuthUser = mysql_real_escape_string(substr($_REQUEST['Username'],0,64));
$tempAuthPass = md5(mysql_real_escape_string(substr($_REQUEST['Password'],0,64)));

// Limit Dept length to 4 digits. If you have more than 9999 depts, extend this.
$tempAuthDept = mysql_real_escape_string(substr($_REQUEST['Dept'],0,4));

$query = "select * from tbladmins where username='" . $tempAuthUser . "' and password='" . $tempAuthPass . "'";
$result = mysql_query($query) or die("Administrator Not Found");
if(mysql_num_rows($result)==0) {
    die("Administrator Not Found");
}
$row=mysql_fetch_assoc($result);
$tempAdminSupportDepts = $row['supportdepts'];

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Validate Department Access


$tempSQL="";
$tempPermittedDepts = explode(",",$tempAdminSupportDepts);

if ($tempAuthDept==0) // All available departments
{
    foreach($tempPermittedDepts as $tempDeptID)
    {
		if ($tempDeptID!="")
        {
			if (strlen($tempSQL)>0)
            {
				$tempSQL .= " or ";
            }
			$tempSQL .= "did=" . $tempDeptID;
        }
    }
}
else
{
    foreach($tempPermittedDepts as $tempDeptID)
    {
		if ($tempDeptID!="" and $tempDeptID == $tempAuthDept)
        {
			$tempSQL .= "did=" . $tempDeptID;
        }
    }
	if ($tempSQL=="") // No depts authorised
    {
		die("This Administrator does not have access to this department");
    }
}



if (strlen($tempSQL)>0)
{
	$tempSQL = " where " . $tempSQL;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get basic system variables

// Get Company Name
$query = "Select * FROM tblconfiguration where setting='CompanyName' limit 0,1";
$result = mysql_query($query) or die("tblconfiguration: CompanyName not found");
if(mysql_num_rows($result)==0) {
    die("tblconfiguration: CompanyName not found");
}
$row=mysql_fetch_assoc($result);
$tempCompanyName=$row['value'];

// Get Company Email
$query = "Select * FROM tblconfiguration where setting='Email' limit 0,1";
$result = mysql_query($query) or die("tblconfiguration: Email not found");
if(mysql_num_rows($result)==0) {
    die("tblconfiguration: Email not found");
}
$row=mysql_fetch_assoc($result);
$tempCompanyEmail=$row['value'];


// Get SystemURL
$query = "Select * FROM tblconfiguration where setting='SystemURL' limit 0,1";
$result = mysql_query($query) or die("tblconfiguration: SystemURL not found");
if(mysql_num_rows($result)==0) {
    die("tblconfiguration: SystemURL not found");
}
$row=mysql_fetch_assoc($result);
$tempSystemURL=$row['value'];

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


$rss = new rss('utf-8');

$rss->channel($tempCompanyName . ' Tickets', $tempSystemURL, $tempCompanyName . ' Tickets');



$rss->language('en-us');
$rss->copyright('Copyright ' . $tempCompanyName);
$rss->managingEditor($tempCompanyEmail);
$rss->category('CategoryName');



$rss->startRSS();

$query = "Select * FROM tbltickets " . $tempSQL . " order by lastreply desc limit 0," . $constTicketsToDisplay;
$result = mysql_query($query) or die("No Tickets Found");
if(mysql_num_rows($result)==0) {
    die("No Tickets Found");
}

while ($row=mysql_fetch_assoc($result))
{
	
    $rss->itemTitle("<![CDATA[" . $row['title'] . "]]>");
    $rss->itemLink("<![CDATA[" . $tempSystemURL . "/admin/supporttickets.php?action=viewticket&id=" . $row['id']."]]>");
    $rss->itemDescription("<![CDATA[" . $row['message'] . "]]>");
    $rss->itemAuthor($row['name'] ." ".$row['email']);
    $rss->addItem();
}

echo $rss->RSSdoneVar();


?>
