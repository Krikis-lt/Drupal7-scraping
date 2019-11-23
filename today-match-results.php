<?php

include_once('dom/simple_html_dom.php');

class DrupalBoot {
	function drupalBoot() {
		define('DRUPAL_ROOT', getcwd());
		require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
		drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
		
		error_reporting(E_ALL);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
	}
}

$dBoot = new DrupalBoot();
$dBoot->drupalBoot();

function checkMatchResult(){
	
	// get DOM from URL or file
	$html = file_get_html('https://www.xscores.com/soccer/livescores/finished-games');
	$links = array();

	// get Home team names
	foreach($html->find('.match_line .score_home_txt') as $a) {
		$links['hteam'][] = $a->plaintext;
	}

	// get Away team names
	foreach($html->find('.match_line .score_away_txt') as $b) {
		$links['ateam'][] = $b->plaintext;
	}

	// get Home team score
	foreach($html->find('.match_line .scoreh_ft') as $c) {
		$links['hscore'][] = $c->plaintext;
	}

	// get Away team score
	foreach($html->find('.match_line .scorea_ft') as $d) {
		$links['ascore'][] = $d->plaintext;
	}

	// get Match time to timestamp
	foreach($html->find('.match_line .score_ko') as $e) {
		$links['ghour'][] = strtotime($e->plaintext)+1;
	}

	// empty table and insert new date
	db_truncate('krikis_home_page_today_results')->execute();
	for ($i=0; $i<count($links['hteam']); $i++) {
		$nid = db_insert('krikis_home_page_today_results')
		  ->fields(array(
			  'home_team' => $links['hteam'][$i],
			  'away_team' => $links['ateam'][$i],
			  'home_score' => $links['hscore'][$i],
			  'away_score' => $links['ascore'][$i],
			  'gamestart' => $links['ghour'][$i],
			  'timestamp' => date('Y-m-d H:i:s'),
		  ))
		  ->execute();
		  dpm($nid);
	  }

	// get Todays matches home teams IDs by name compare
	foreach($html->find('.match_line .score_home_txt') as $getHomeTeamID) {
		$links['hteamid'] = $getHomeTeamID->plaintext;

		// compare if team exist from xsoccer and football_team table by name
		$queryId = db_select('football_teams', 'f'); 
		$queryId->fields('f', array('name')); 
		$queryId->condition('name', $links['hteamid']);
		$resultId1 = $queryId->execute()->fetchAssoc();

		foreach($resultId1 as $rezid1){
			// if teams exist get team id number
			$queryIdIn = db_select('football_teams', 'f'); 
			$queryIdIn->fields('f', array('tid')); 
			$queryIdIn->condition('name', $links['hteamid']);
			$resultIdIn1 = $queryIdIn->execute()->fetchAssoc();

			//insert existing team id number to matches table
			for ($i=0; $i<count($rezid1); $i++) {
				db_update('krikis_home_page_today_results')
				->fields(array('h_id' => $resultIdIn1,))
				->condition('home_team', $links['hteamid'])
				->execute();
			  }
		}
	}

	// get Todays matches home teams IDs by name compare
	foreach($html->find('.match_line .score_away_txt') as $getAwayTeamID) {
		$links['ateamid'] = $getAwayTeamID->plaintext;

		// compare if team exist from xsoccer and football_team table by name
		$queryId2 = db_select('football_teams', 'f'); 
		$queryId2->fields('f', array('name')); 
		$queryId2->condition('name', $links['ateamid']);
		$resultId2 = $queryId2->execute()->fetchAssoc();

		foreach($resultId2 as $rezid2){
			// if teams exist get team id number
			$queryIdIn2 = db_select('football_teams', 'f'); 
			$queryIdIn2->fields('f', array('tid')); 
			$queryIdIn2->condition('name', $links['ateamid']);
			$resultIdIn2 = $queryIdIn2->execute()->fetchAssoc();

			//insert existing team id number to matches table
			for ($i=0; $i<count($rezid2); $i++) {
				db_update('krikis_home_page_today_results')
				->fields(array('v_id' => $resultIdIn2,))
				->condition('away_team', $links['ateamid'])
				->execute();
			  }
		}
	}

	$queryHomeTeams = db_select('krikis_home_page_today_results','k');
	$queryHomeTeams->fields('k');
	$results=$queryHomeTeams->execute()->fetchAll();

	foreach($results as $resu){
		$home_t_id = $resu->h_id;
		$home_scr = $resu->home_score;
		$away_t_id = $resu->v_id;
		$away_scr = $resu->away_score;
		$gamestrt = $resu->gamestart;

			//insert todays match results to database
			db_update('football_games')
			->fields(array('h_score' => $home_scr,'v_score' => $away_scr))
			->condition('h_id', $home_t_id, '=')
			->condition('v_id', $away_t_id, '=')
			->condition('gamestart', $gamestrt, '=')
			->execute();
	}

	cache_clear_all('*', 'cache_page', TRUE);
	
}
	

//GET LATEST UPDATE TIME FROM DATABASE
$max = db_query('SELECT MAX(timestamp) FROM {krikis_home_page_today_results}')->fetchField();

//CURRENT TIME
$time = date('Y-m-d H:i:s');

//Substract Times different Diff
$date1 = strtotime($max);
$date2 = strtotime($time);
$diff = $date2 - $date1;
//echo $diff;

//CRON running on https://www.easycron.com
//if($diff > 900 ){
	
//}

checkMatchResult();
drupal_flush_all_caches();

?>

	


