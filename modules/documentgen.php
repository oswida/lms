<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$_DOC_DIR = DOC_DIR;

/* Using AJAX for template plugins */
require(LIB_DIR.'/xajax/xajax.inc.php');

function plugin($template, $customer)
{
	global $_DOC_DIR;

	$result = '';
	
	// read template informations
	@include(DOC_DIR.'/templates/'.$template.'/info.php');
	// call plugin
	@include(DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['plugin'].'.php');
	
	// xajax response
	$objResponse = new xajaxResponse();
	$objResponse->addAssign("plugin", "innerHTML", $result);
	return $objResponse;
}

$xajax = new xajax();
//$xajax->debugOn();
$xajax->errorHandlerOn();
$xajax->registerFunction("plugin");
$xajax->processRequests();						

$SMARTY->assign('xajax', $xajax->getJavascript('img/', 'xajax.js'));
/* end AJAX plugin stuff */

$layout['pagetitle'] = trans('Documents Generator');

if(isset($_POST['document']))
{
	$document = $_POST['document'];
	
	$oldfromdate = $document['fromdate'];
	$oldtodate = $document['todate'];

	if(!$document['type'])
		$error['type'] = trans('Document type is required!');

	if(!$document['title'])
		$error['title'] = trans('Document title is required!');

	if($document['number'] == '')
	{
		$tmp = $LMS->GetNewDocumentNumber($document['type'], $document['numberplanid']);
		$document['number'] = $tmp ? $tmp : 0;
	}
	elseif(!eregi('^[0-9]+$', $document['number']))
    		$error['number'] = trans('Document number must be an integer!');
	elseif($LMS->DocumentExists($document['number'], $document['type'], $document['numberplanid']))
		$error['number'] = trans('Document with specified number exists!');
	
	if($document['fromdate'])
	{
		$date = explode('/',$document['fromdate']);
		if(checkdate($date[1],$date[2],$date[0]))
			$document['fromdate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
		else
			$error['fromdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	else 
		$document['fromdate'] = 0;

	if($document['todate'])
	{
		$date = explode('/',$document['todate']);
		if(checkdate($date[1],$date[2],$date[0]))
			$document['todate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
		else
			$error['todate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	else 
		$document['todate'] = 0;
	
	if($document['fromdate'] > $document['todate'] && $document['todate']!=0)
		$error['todate'] = trans('Start date can\'t be greater than end date!');

	switch($_POST['filter'])
	{
		case 0:
			$customerlist = $LMS->GetCustomerList(NULL, $_POST['filter'], $_POST['network'], $_POST['customergroup']);
		break;
		case 1:
			$customerlist = $LMS->GetCustomerList(NULL, $_POST['filter']);
		break;
		case 2:
			$customerlist = $LMS->GetCustomerList(NULL, $_POST['filter']);
		break;
		case 3:
			$customerlist = $LMS->GetCustomerList(NULL, $_POST['filter'], $_POST['network'], $_POST['customergroup']);
		break;
		case 5:
			$customerlist = $LMS->GetCustomerList(NULL, $_POST['filter'], $_POST['network'], $_POST['customergroup']);
		break;
		case 6:
			$customerlist = $LMS->GetCustomerList(NULL, $_POST['filter'], $_POST['network'], $_POST['customergroup']);
		break;
		case -1:
			if($customerlist = $LMS->GetCustomerList(NULL, NULL, NULL, $_POST['customergroup']))
			{
				foreach($customerlist as $idx => $row)
					if(! $row['account'])
						$ncustomerlist[] = $customerlist[$idx];
				
				$customerlist = $ncustomerlist;
			}
		break;	
	}		

	if(!isset($customerlist) || $customerlist['total'] == 0)
		$error['customer'] = trans('Customers list is empty!');

	if(!$document['templ'])
		$error['template'] = trans('Document template not selected!');
	
	if(!$error)
	{
		$header = '';
		$time = time();
		$gencount = 0;
		$genresult = '<H1>'.$layout['pagetitle'].'</H1>';

		$numtemplate = $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($document['numberplanid']));

		// read template informations
		include(DOC_DIR.'/templates/'.$document['templ'].'/info.php');
		
		// run template engine
		if(file_exists(DOC_DIR.'/templates/'.$engine['engine'].'/engine.php'))
			require_once(DOC_DIR.'/templates/'.$engine['engine'].'/engine.php');
		else
			require_once(DOC_DIR.'/templates/default/engine.php');

		foreach($customerlist as $gencust)
		{
			if(!is_array($gencust)) continue;

			$document['customerid'] = $gencust['id'];
			$gencount++;	
			$output = NULL; // delete output
			$genresult .= $gencount.'. '.$gencust['customername'].': ';
			
			if(file_exists(DOC_DIR.'/templates/'.$engine['engine'].'/engine.php'))
				include(DOC_DIR.'/templates/'.$engine['engine'].'/engine.php');
			else
				include(DOC_DIR.'/templates/default/engine.php');

			if($output)
			{
		    		$file = DOC_DIR.'/tmp.file';
				$fh = fopen($file, 'w');
				fwrite($fh, $output);
				fclose($fh);
		
	    			$document['md5sum'] = md5_file($file);
			    	$document['contenttype'] = $engine['content_type'];
				$document['filename'] = $engine['output'];

				$path = DOC_DIR.'/'.substr($document['md5sum'],0,2);
				@mkdir($path, 0700);
				$newfile = $path.'/'.$document['md5sum'];
				if(!file_exists($newfile))
				{
					if(!@rename($file, $newfile))
						$error = trans('Can\'t save file in "$0" directory!', $path);
				}
			}
			else
				$error = trans('Problem during file generation!');
	
			if($error)
			{
				$genresult .= '<font class="alert">'.$error.'</font><br>';
				continue;
			}
	
			$DB->BeginTrans();
		
			$DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, customerid, userid, name, address, zip, city, ten, ssn, closed)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(	$document['type'],
					$document['number'],
					$document['numberplanid'],
					$time,
					$document['customerid'],
					$AUTH->id,
					$gencust['customername'],
					$gencust['address'] ? $gencust['address'] : '',
					$gencust['zip'] ? $gencust['zip'] : '',
					$gencust['city'] ? $gencust['city'] : '',
					$gencust['ten'] ? $gencust['ten'] : '',
					$gencust['ssn'] ? $gencust['ssn'] : '',
					isset($document['closed']) ? 1 : 0
					));
		
			$docid = $DB->GetLastInsertID('documents');

			$DB->Execute('INSERT INTO documentcontents (docid, title, fromdate, todate, filename, contenttype, md5sum, description)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
				array(	$docid,
					$document['title'],
					$document['fromdate'],
					$document['todate'],
					$document['filename'],
					$document['contenttype'],
					$document['md5sum'],
					$document['description']
					));
		
			$DB->CommitTrans();
			
			$genresult .= docnumber($document['number'], $numtemplate, $time).'.<br>';
			$document['number']++;

			if(isset($_GET['print']) && $document['contenttype']=='text/html')
			{
				print $output;
				print '<DIV style="page-break-after: always;"></DIV>'; 
				flush();
			}
		}
		
		if(!isset($_GET['print']))
		{
			$SMARTY->display('header.html');
			print $genresult;
			$SMARTY->display('footer.html');
		}
		
		die;
	}
	else
	{
		$document['fromdate'] = $oldfromdate;
		$document['todate'] = $oldtodate;
		
		if($document['templ'])
		{
			$result = '';
			// read template informations
			include(DOC_DIR.'/templates/'.$document['templ'].'/info.php');
			// set some variables
			$SMARTY->assign('document', $document);
			// call plugin
		    	@include(DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['plugin'].'.php');
			// get plugin content
			$SMARTY->assign('plugin_result', $result);
		}
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($document['numberplanid']))
{
	$document['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype<0 AND isdefault=1 LIMIT 1');
}

$numberplans = array();

if($templist = $LMS->GetNumberPlans())
	foreach($templist as $item)
		if($item['doctype']<0)
			$numberplans[] = $item;

if($dirs = getdir(DOC_DIR.'/templates', '^[a-z0-9_-]+$'))
	foreach($dirs as $dir)
	{
		$infofile = DOC_DIR.'/templates/'.$dir.'/info.php';
		if(file_exists($infofile))
		{
			unset($engine);
			include($infofile);
			$docengines[] = $engine;
		}
	}

if($docengines) asort($docengines);

$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('error', $error);
$SMARTY->assign('numberplans', $numberplans);
$SMARTY->assign('docengines', $docengines);
$SMARTY->assign('document', $document);
$SMARTY->display('documentgen.html');

?>