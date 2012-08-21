<?php
class linkontrol {
	function runSqlMulti($SQL) {
		global $db;
		return $db->run($SQL);
	}

	function runSql($SQL) {
		$arr = $this->runSqlMulti($SQL);
		return $arr[0];
	}

	function getTimeFeeds($movieid) {
		$movieid = intval($movieid);
		return $this->runSqlMulti("SELECT timefeed.*, linktype.name as linktypename " .
				"FROM linkontrol.timefeed, linkontrol.linktype " .
				"WHERE timefeed.linktypeid = linktype.linktypeid " .
				"AND movieid = $movieid " .
				"AND deleted = 0 ORDER BY start ASC LIMIT 1000");
	}

	function getTimeFeed($timefeedid) {
		$timefeedid = intval($timefeedid);
		return $this->runSql("SELECT timefeed.*, movie.userid FROM linkontrol.timefeed, linkontrol.movie " .
				"WHERE movie.movieid = timefeed.movieid " .
				"AND timefeedid = $timefeedid");
	}

	function deleteTimeFeed($timefeedid) {
		$timefeedid = intval($timefeedid);
		return $this->runSql("UPDATE linkontrol.timefeed SET deleted = 1 WHERE timefeedid = $timefeedid");
	}

	function addTimeFeed($movieid, $start, $end, $title, $img, $body, $href, $linktypeid) {
                $this->runSql("INSERT INTO linkontrol.timefeed(movieid, start, end, title, img, body, href, linktypeid) " .
				"VALUES ($movieid, $start, $end, '$title', '$img', '$body', '$href', $linktypeid)");
		return mysql_insert_id();
        }

	function updateTimeFeed($timefeedid, $start, $end, $title, $img, $body, $href, $linktypeid) {
                $this->runSql("UPDATE linkontrol.timefeed " . 
				"SET start = $start, end = $end, title = '$title', img = '$img', " .
				"body = '$body', href = '$href', linktypeid = $linktypeid " .
				"WHERE timefeedid = $timefeedid");
		return mysql_affected_rows();
	}

	function addMovie($userid, $name, $href) {
                $this->runSql("INSERT INTO linkontrol.movie(userid, name, href) " .
				"VALUES ($userid, '$name', '$href')");
		return mysql_insert_id();
        }

	function updateMovie($movieid, $name, $href) {
		$movieid = intval($movieid);
                $this->runSql("UPDATE linkontrol.movie SET name = '$name', href = '$href' WHERE movieid = $movieid");
		return mysql_affected_rows();
        }

	function deleteMovie($movieid) {
		$movieid = intval($movieid);
		return $this->runSql("UPDATE linkontrol.movie SET deleted = 1 WHERE movieid = $movieid");
	}

	function getMovies() {
		return $this->runSqlMulti("SELECT movie.*, users.username FROM linkontrol.movie, linkontrol.users " .
					"WHERE deleted = 0 " .
					"AND movie.userid = users.userid " .
					"ORDER BY movieid DESC LIMIT 100"); 
        }

	function getMovie($movieid) {
		$movieid = intval($movieid);
		return $this->runSql("SELECT * FROM linkontrol.movie WHERE movieid = $movieid LIMIT 1"); 
        }
	
	function createSession($movieid) {
		$movieid = intval($movieid);
		$sessionkey = substr(base_convert(md5($movieid + time()), 10, 36), 1, 5);
                $this->runSql("INSERT INTO linkontrol.session(movieid, sessionkey) " .
				"VALUES ($movieid, '$sessionkey')");
		return $sessionkey;
	}

	function reuseSession($sessionkey, $movieid) {
		$movieid = intval($movieid);
                $this->runSql("INSERT INTO linkontrol.session(movieid, sessionkey) " .
				"VALUES ($movieid, '$sessionkey') " . 
				"ON DUPLICATE KEY UPDATE movieid = $movieid, sessionkey = '$sessionkey'");
	}

	function getSession($sessionkey) {
		return $this->runSql("SELECT session.movieid, movie.name as moviename " .
			"FROM linkontrol.session, linkontrol.movie " .
			"WHERE session.movieid = movie.movieid " .
			"AND sessionkey = '$sessionkey' LIMIT 1"); 
	}
	
	function timeFeedToHtml($val) {
		return "<tr><td>" . $val['timefeedid'] . "</td><td>" . 
			$val['start'] . ',' . $val['end'] . "</td><td>" . 
			$val['body'] . "</td><td>" . 
			$val['img'] . "</td><td>" . 
			$val['href'] . "</td><td>" . 
			"<a href=\"?do=delete_time_feed&timefeedid=" . $val['timefeedid'] . "&movieid=" . $val['movieid'] . "\">Delete</a></td><td>" .
			"<a href=\"?do=get_time_feed&timefeedid=" . $val['timefeedid'] . "&movieid=" . $val['movieid'] . "\">View</a></td><td>" .
			"</td></tr>\n";
	}

	function getLinkTypeSelectHtml($linktypeid) {
		$arr = $this->runSqlMulti("SELECT * FROM linkontrol.linktype");
		$html = "<SELECT name=linktypeid>\n";
		if (isset($arr)) {
			foreach ($arr as $key => $val) {
				if ($linktypeid == $val['linktypeid']) {
					$html .= '<option value="' . $val['linktypeid'] . '" SELECTED>' . $val['name'] . '</option>' . "\n";
				}
				else {
					$html .= '<option value=' . $val['linktypeid'] . '>' . $val['name'] . '</option>' . "\n";
				}
			}
		}
		$html .= "</SELECT>\n";
		return $html;
	}

	function timeFeedToHtmlForm($index, $val) {
		$html = '<form method=post action="?do=update_time_feed&movieid=' . $val['movieid'] . '">' . 
			'<input type=hidden name=feedid value=' . $val['timefeedid'] . '>' .
			'<tr>' .
			'<td>' . $index . '</td>' . 
			'<td><input name=start size=4 value="' . $val['start'] . '"></td>' . 
			'<td><input name=body size=35 value="' . $val['body'] . '"></td>' . 
			'<td><input name=img size=10 value="' . $val['img'] . '"></td>' . 
			'<td><input name=href size=35 value="' . $val['href'] . '"></td>' . 
			'<td>';
			$html .= $this->getLinkTypeSelectHtml($val['linktypeid']);

			$html .=
			'<td>' .
			'<td><input type=submit value="Save">' .
			'</form>' .
			"<td><a href=\"?do=delete_time_feed&timefeedid=" . $val['timefeedid'] . "&movieid=" . $val['movieid'] . "\">Delete</a></td>" .
			"</tr>\n";
			return $html;
	}

	function timeFeedToJson($val) {
		// if end: 0, it will not show
		return "popcorn.timefeed({\n" .
			"start: " . $val['start'] . ",\n" . 
			//"end:" . $val['end'] . ",\n" .
			"target: '#feeddiv',\n" .
			"body: '" . $val['body'] . "',\n" .
			"img: 'Icons/" . $val['img'] . "',\n" .
			"href: '" . $val['href'] . "'\n" .
		"});\n";
	}

	function timeFeedToList($val) {
		$linktypeid = $val['linktypeid'];
		$start = $val['start'];
		$img = "Icons/" . $val['img'];
		$href = $val['href'];
		$title = $linktypeid . " " . $val['linktypename'] . " " . $val['title'];
		$body = $val['body'];

		if ($linktypeid == 3) { // video
                                return "<li>" .
					'<div data-role="collapsible" data-theme="a">' .
                                        "<h3>$body</h3>" .
                                        "<iframe width='288' height='200' src='$href' frameborder='0' allowfullscreen></iframe>" .
                                        "</div>" .
                                "</li>\n";
		}
		else if ($linktypeid == 2) { // web page
			"<li style='display: none' start='" . $val['start'] . "'>\n" .
			'<a href="' . $val['href'] . '">' . "\n" .
			'<img src="Icons/' . $val['img'] . '" />' . "\n" . 
			"<p>" . $val['body'] . "</p>\n" .
			"</a>" .
			"</li>\n";
		}
		else {
			return "<li style='display: none' start='$start'><img src='$img' /><h3>$head</h3><p>$body</p></li>";
		}
	}

	function getNavigationMenu() {
		$html = <<<EOF
		<table>
		<tr>
		<td><a href="movies.php"> Movies </a></td>
		<td><a href="remote.php"> Remote </a></td>
		<td><a href="testapi.php"> Test API </a></td>
		<tr>
		</table>
EOF;
		return $html;

	}	

	function getLoginMenu() {
		return '<div><a href="login.php" class="sign-in" id="login-link">Log In</a> | <a href="register.php" class="sign-in" id="signup-link">Sign Up</a></div>' . "\n";
	}

	function getNavigationMenu2() {
		$html = <<<EOF
		<div id="small_menu">
		<ul>
		<li><a style="padding: 0pt 9px;" href="index.php"> Linkontrol </a></li>
		<li><a style="padding: 0pt 9px;" href="movies.php"> Movies </a></li>
		<li><a style="padding: 0pt 9px;" href="remote.php"> Remote </a></li>
</ul>
EOF;
		return $html;
	}
}
?>
