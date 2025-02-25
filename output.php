<?php

// match subsets of data
require_once(dirname(__FILE__) . '/sqlite.php');


$sql = 'SELECT * FROM publications 
ORDER BY CAST(year as INTEGER), CAST(spage as INTEGER), title';

$data = db_get($sql);

// group by years

$clusters = array();

foreach ($data as $obj)
{
	$cluster_id = uniqid();
	
	if (isset($obj->cluster_guid))
	{
		$cluster_id = $obj->cluster_guid;
	}
	
	if (!isset($clusters[$cluster_id]))
	{
		$clusters[$cluster_id] = array();
	}
	$clusters[$cluster_id][] = $obj;
	
}

//print_r($clusters);

// export

$keys = array(
'id',
'doi',
'bhl_part',
'bhl_doi',
'title',
'volume',
'spage',
'epage',
'year'
);

echo join("\t", $keys) . "\n";

foreach ($clusters as $id => $cluster_members)
{
	$obj = new stdclass;
	$obj->id = $id;
	
	foreach ($cluster_members as $cluster)
	{		
		if ((preg_match('/^10\./', $cluster->guid)))
		{
			if ((preg_match('/^10.5962/', $cluster->guid)))
			{
				$obj->bhl_doi = $cluster->guid;
			}
			else
			{
				$obj->doi = $cluster->guid;
			}
		}
		else
		{
			$obj->bhl_part = $cluster->guid;
		}
		
		if (!isset($obj->title))
		{
			$obj->title = $cluster->title;
		}
		if (!isset($obj->volume) && isset($cluster->volume))
		{
			$obj->volume = $cluster->volume;
		}
		if (!isset($obj->spage) && isset($cluster->spage))
		{
			$obj->spage = $cluster->spage;
		}
		if (!isset($obj->epage) && isset($cluster->epage))
		{
			$obj->epage = $cluster->epage;
		}
		if (!isset($obj->year) && isset($cluster->year))
		{
			$obj->year = $cluster->year;
		}
		
	}
		
	$row = array();
	foreach ($keys as $k)
	{
		if (isset($obj->{$k}))
		{
			$row[] = $obj->{$k};
		}
		else
		{
			$row[] = '';
		}
	}
	
	echo join("\t", $row) . "\n";

}


?>
