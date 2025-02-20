<?php

// match two sets of references from two TSV files, we use "year" to "block" the
// data 

// Simplest TSV file is guid, title, year

/*
example SQL

SELECT guid, title, volume, year, spage, epage, doi FROM publications WKERE issn="XXXX-XXXX";


*/


require_once(dirname(__FILE__) . '/compare.php');

//----------------------------------------------------------------------------------------
// get publications and group by year so we have "blocks"
function get_data($filename)
{
	$headings = array();

	$row_count = 0;

	$data = array();

	$file = @fopen($filename, "r") or die("couldn't open $filename");
		
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$line = trim(fgets($file_handle));
		
		$row = explode("\t",$line);
		
		$go = is_array($row);
	
		if ($go)
		{
			if ($row_count == 0)
			{
				$headings = $row;		
			}
			else
			{
				$obj = new stdclass;
		
				foreach ($row as $k => $v)
				{
					if ($v != '')
					{
						$obj->{$headings[$k]} = $v;
					}
				}
		
				//print_r($obj);	
			
				if (isset($obj->year))
				{
					if (!isset($data[$obj->year]))
					{
						$data[$obj->year] = array();
					}
					$data[$obj->year][] = $obj;
				}
			}
		}	
		$row_count++;
	}

	return $data;
}

//----------------------------------------------------------------------------------------

//get data and group by years to minimise comparisons we need to make

$one = get_data('one.tsv');
$two = get_data('two.tsv');

//print_r($one);
//print_r($two);

//exit();

// compare

$verbose = false;
$verbose = true;

$missing_one = array();
$missing_two = array();

foreach ($one as $year => $articles)
{
	if (isset($one[$year]) && isset($two[$year]))
	{
		if ($verbose)
		{
			echo "\n\n-- $year --\n";
		}
		
		$k1 = array();
		$k2 = array();
		

		foreach ($one[$year] as $o1)
		{
			//echo $o1->title . "\n";
			
			$o1->title = preg_replace('/\s+in conjunction.*$/i', '', $o1->title);
			$k1[] = $o1;
		}

		//echo "\n\n";

		foreach ($two[$year] as $o2)
		{
			$k2[] = $o2;
		}

		$m = count($k1);
		$n = count($k2);
		
		$k1_list = range(0, $m-1);
		$k2_list = range(0, $n-1);
		
		
		//print_r($k1);
		//print_r($k2);
		
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
				
				
				//echo "$text1\n";
				//echo "$text2\n";
				
				$result = compare_common_subsequence($text1, $text2);
				
				
				if ($result->normalised[1] > 0.95)
				{
					// one string is almost an exact substring of the other
					if ($result->normalised[0] > 0.90)
					{
						if ($result->normalised[1] > $best_normalised[1] && $result->normalised[0] >= $best_normalised[0])
						{
							$best_hit = $j;
							$best_normalised = $result->normalised;
						}
					}
				}
			}
				
			if ($best_hit != -1)
			{
				$j = $best_hit;
				
				if ($verbose)
				{
					echo "\n-- " . $k1[$i]->title . "\n";
					echo "-- " . $k2[$j]->title . "\n";
				}
		
				//------------------------------------------------------------------------
				// do something here, this may need to be edited for the specific task

				
				if (1)
				{
					// one.tsv is CrossRef, two.csv is BHL
				
					//print_r($k1[$i]);
					//print_r($k2[$j]);
				
				
					$go = true;
					
					
					if ($go)
					{
						// matched
						unset($k1_list[$i]);
						unset($k2_list[$j]);
						
						$cluster_guid = $k1[$i]->guid . '-' . $k2[$j]->guid;
						
						// combined
						echo 'UPDATE publications SET cluster_guid="' . $cluster_guid  . '" WHERE guid="' . $k1[$i]->guid . '";' . "\n";
						echo 'UPDATE publications SET cluster_guid="' . $cluster_guid  . '" WHERE guid="' . $k2[$j]->guid . '";' . "\n";
					}
				
				}	
				
				
			}
		}
		
		//print_r($k1_list);
		//print_r($k2_list);

		foreach ($k1_list as $i)
		{
			$missing_one[] = $k1[$i]->guid;
		}
		
		foreach ($k2_list as $j)
		{
			$missing_two[] = $k2[$j]->guid;
		}
	}
}


// print_r($missing_one);
// print_r($missing_two);


?>
