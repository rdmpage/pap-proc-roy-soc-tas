<?php

// match subsets of data

require_once(dirname(__FILE__) . '/compare.php');
require_once(dirname(__FILE__) . '/sqlite.php');


$one = array();
$two = array();

$years = array(1922,1923);
$years = array(1921,1922);
$years = array(1920,1921);
$years = array(1920,1919);
$years = array(1908,1909);
$years = array(1864,1865);

$years = array(1922,1923);
$years = array(1921,1922);
$years = array(1920,1921);
$years = array(1920,1919);
$years = array(1918,1919);
$years = array(1900,1901,1902);
$years = array(1884, 1885);

$years = array(1864, 1865, 1866);

$years = array(1850, 1851, 1852);

$years = array(1859, 1860);

$sql1 = 'SELECT * FROM publications WHERE cluster_guid IS NULL AND year IN (' . join(',', $years) . ') AND guid NOT LIKE "10%"';
$sql2 = 'SELECT * FROM publications WHERE cluster_guid IS NULL AND year IN (' . join(',', $years) . ') AND guid LIKE "10%"';


$one = db_get($sql1);
//print_r($one);

$two = db_get($sql2);
//print_r($two);


$verbose = false;
$verbose = true;

$missing_one = array();
$missing_two = array();


$k1 = array();
$k2 = array();
		

foreach ($one as $o1)
{
	$k1[] = $o1;
}

//echo "\n\n";

foreach ($two as $o2)
{
	$k2[] = $o2;
}

$m = count($k1);
$n = count($k2);

$k1_list = range(0, $m-1);
$k2_list = range(0, $n-1);
		
$best_matches = array();

for ($i = 0; $i < $m; $i++)
{
	$best_hit = -1;
	$best_normalised = array(0,0);
			
	for ($j = 0; $j < $n; $j++)
	{								
		// extra cleaning?
		$text1 = $k1[$i]->title;
		$text2 = $k2[$j]->title;
				
		if (preg_match('/^(.*) \/ (.*)$/', $text1, $matches))
		{
			$text1 = $matches[1];
		}

		// echo "$text1\n";
		//echo "$text2\n";
		
		$result = compare_common_subsequence($text1, $text2);
		
		// echo $result->normalised[1] . "\n";
		// echo $result->normalised[0] . "\n";
		
		
		if ($result->normalised[1] > 0.90)
		{
			// one string is almost an exact substring of the other
			if ($result->normalised[0] > 0.60)
			{
				if ($result->normalised[1] > $best_normalised[1] && $result->normalised[0] >= $best_normalised[0])
				{
					$best_hit = $j;
					$best_normalised = $result->normalised;
				}
			}
		}
		
		// echo "-------\n";
	}
		
	if ($best_hit != -1)
	{
		$j = $best_hit;
		
		if ($verbose)
		{
			echo "\n-- " . $k1[$i]->title . "\n";
			echo "-- " . $k2[$j]->title . "\n";
		}
		
		$go = true;
		
		if ($go)
		{
			$go = $k1[$i]->spage == $k2[$j]->spage;
		}					
		
		if ($go)
		{
			// matched
			unset($k1_list[$i]);
			unset($k2_list[$j]);
			
			$cluster_guid = $k2[$j]->guid . '-' . $k1[$i]->guid;
			
			// combined
			echo 'UPDATE publications SET cluster_guid="' . $cluster_guid  . '" WHERE guid="' . $k1[$i]->guid . '";' . "\n";
			echo 'UPDATE publications SET cluster_guid="' . $cluster_guid  . '" WHERE guid="' . $k2[$j]->guid . '";' . "\n";
		}
		
		
		
	}
}

//print_r($k1_list);
//print_r($k2_list);

/*
foreach ($k1_list as $i)
{
	$missing_one[] = $k1[$i]->guid;
}

foreach ($k2_list as $j)
{
	$missing_two[] = $k2[$j]->guid;
}
*/

// print_r($missing_one);
// print_r($missing_two);



?>
