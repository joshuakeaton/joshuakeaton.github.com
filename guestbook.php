<?php ob_start(); header("Cache-Control: no-cache, must-revalidate"); ?>

<?php
include "jk_header_1000.php";
?>

<?php
/*================================*\
	-- user variables --
   flumpcakes.co.uk/php/guestbook
\*================================*/
define('ADMIN_USER',	'jk9000');
define('ADMIN_PASS',	'4f26f11db8086c5e6790e2b639230994');

// Added by Josh
$illegaltext = array("о", "ч", "и", "т", "а", "л", "п", "celexa", "accolate", "cephalexin", "cipro", "biaxin", "diarrhea", "propranolol", "doxazosin", "plavix", "flextra", "triphasil", "triamterene", "zyban", "hydrocodone", "viagra", "ditropan", "vaniqa", "keflex", "penis", "vagina","plendil", "lotensin", "nizoral", "loratadine", "alesse", "methylphenidate", "ramipril", "nasacort", "captopril", "clopidogrel", "nifedipine", "adipex", "soma", "phentermine", "xanax", "ambien", "cialis", "\[url\]", "\[link\]", "http://", "\\.com", "wikidot"); // escape regular expression characters
$joshnames = array("josh", "josh keaton", "joshua", "jk", "joshkeaton"); // forbidden names
// All posts must have one of these terms
$musthavetext = array('josh', 'jesus', 'heart is true', 'joshua', 'keaton', 'coffee time', 'love', 'luv tha website', 'in the key of', 'having fun', 'loving life', 'christ', 'my favorite bible text is');

define('GB_FILE',	'jk9000-gb.dat');
define('DATE_FORMAT',	'jS F Y - H:i');
define('MAX_PER_PAGE',	10);

define('MAX_NAME_LEN',	32);
define('MIN_MESG_LEN',	3);
define('MAX_MESG_LEN',	600);
define('POST_TIME',	60);

define('ALLOW_URLS',	false);

define('SELF',		$_SERVER['PHP_SELF']);
/*================================*\
	  -- end user variables --
\*================================*/

define('FUNC', isset($_GET['func']) ? $_GET['func'] : NULL);
define('P', isset($_GET['p']) ? (int) $_GET['p'] : 1);

if (FUNC == 'logout') {
	echo '<p>You have been logged out.</p>';

	setcookie ('password', '');
	unset($_COOKIE['password'], $password);
}

if (isset($_POST['password'])) {
	$password = md5($_POST['password']);

	if ($password == ADMIN_PASS) {
		setcookie('password', $password);
	}
} else {
	$password = isset($_COOKIE['password']) ? $_COOKIE['password'] : NULL;
}

ob_end_flush();



switch (FUNC)
{
/*================================*\
		-- default --
\*================================*/

default:
	if (!$fp = @fopen(GB_FILE, 'r')) {
		echo '<p>failed to open: '.GB_FILE.'</p>';
		break;
	}

	$i = 0;
	$data = NULL;

	$to_show = (P * MAX_PER_PAGE) - MAX_PER_PAGE;

	if (P > 1) echo '<p><b>Page '.P.'</b></p>';

	while (!feof($fp)) {
		$i++;

		if ($i > ($to_show + MAX_PER_PAGE)) break;

		$data = fgets($fp, 4096);

		if (empty($data)) break;

		if ($i > $to_show) {
			list ($date, $name, $mesg, $ip) = str_replace('\|', '|', preg_split('/(?<!\\\)(\|)/', $data));

			echo ("\n<hr/><p><b>$name</b> on ".date(DATE_FORMAT, $date)."<br />$mesg</p>");
		}
	}

	if ($i > MAX_PER_PAGE) {
		$line_count = substr_count(fread($fp, filesize(GB_FILE)), "\n") + $i;
		$line_count = ceil($line_count / MAX_PER_PAGE);

		$s = 1;
		$f = $line_count + 1;

		echo "\n".'<p>Page: # ';
		if ($line_count > MAX_PER_PAGE) {
			if (P < 6) {
				$s = 1;
				$f = 10;
			} elseif (($line_count-P) < 6) {
				$s = $line_count - 8;
				$f = $line_count;
			} else {
				$s = P -3;
				$f = $s + 8;
			}

			echo (P > 5) ? ' <a href="'.SELF.'">1</a>-' : NULL;
		}

		for ($k=$s; $k<$f; $k++) {
			echo ($k == P) ? "$k " : "<a href=\"".SELF."?p=$k\">$k</a> ";
		}

		echo ($k <= $line_count) ? "of <a href=\"".SELF."?p=$line_count\">$line_count</a></p>" : '</p>';

	}

	fclose($fp);
break;


/*================================*\
		 -- sign --
\*================================*/

case 'sign':
	$name = (isset($_POST['name'])) ? strip_chars($_POST['name']) : NULL;
	$mesg = (isset($_POST['mesg'])) ? strip_chars($_POST['mesg']) : NULL;

	if (isset($_POST['submit'])) {
		$errors = NULL;
		$now = time();

		$name_len = strlen($name);
		$mesg_len = strlen($mesg);

		if ($name) {
			if ($name_len > MAX_NAME_LEN) {
				$errors = '- Name is too long, '.$name_len.' (Max: '.MAX_NAME_LEN.')<br />';
			}
                        if ($password != ADMIN_PASS && in_array(strtolower($name), $joshnames)) {
                                $name = "Not the real ".$name;
                        }

		} else {
			$errors = '- Name field is empty<br />';
		}


		if ($mesg) {
			if ($password != ADMIN_PASS && $mesg_len > MAX_MESG_LEN) {
				$errors.= '- Message is too long, '.$mesg_len.' (Max: '.MAX_MESG_LEN.')<br />';
			} elseif ($mesg_len < MIN_MESG_LEN) {
				$errors.= '- Message is too short  (Min: '.MIN_MESG_LEN.')<br />';
			} 
			

			foreach ($illegaltext as $txt) {
				$test = eregi($txt, $mesg);
				$txt = str_replace("\\", "", $txt);

				if ($test)
					$errors.= '- Message contains illegal text <br />';
			}
			
			foreach ($musthavetext as $txt) {
				$hasgoodwords = eregi($txt, $mesg);
				if ($hasgoodwords)
				        break;
			}
			
			if (!$hasgoodwords)
				$errors .= "- You're need to use one of the fun phrases!";
		} else {
			$errors.= '- Message field is empty<br />';
		}

		if (!$fp = @fopen(GB_FILE, 'r')) {
			echo 'Unable to open guestbook file for reading, check location and file permissions.';
			break;
		}

		list($date, , , $ip) = fgetcsv($fp, 4096, '|');

		fclose($fp);

		if ($_SERVER['REMOTE_ADDR'] == $ip && $now < $date+POST_TIME) {
			$errors.= '- You are posting too soon after your last post';
		}

		if ($errors) {
			echo '<p>'.$errors.'</p>';
		} else {
			if ($name == ADMIN_USER) {
				if (@$_POST['pass'] != ADMIN_PASS && $password != ADMIN_PASS) {
					echo '<p>This username requires a password</p>';
					echo '<form method="post" action="'.SELF.'?func=sign"><p><input type="password" name="pass" size="20" /> <input type="submit" value="Add" name="submit" /><input type="hidden" name="name" value="'.$name.'" /><input type="hidden" name="mesg" value="'.$mesg.'" /></p></form>';
					break;
				}
			}

			$filesize = filesize(GB_FILE);
			$filesize = (empty($filesize)) ? 1024 : $filesize;

			if (!$fp = @fopen(GB_FILE, 'r+')) {
				echo 'Unable to open guestbook file for reading and writing, check location and file permissions.';
				break;
			}

			$data = fread($fp, $filesize);
			rewind($fp);

			fwrite($fp, "$now|".str_replace("\n", NULL, str_replace('|', '\|', $name)).' |'.str_replace("\n", '<br />', bbcode($mesg)).' |'.$_SERVER['REMOTE_ADDR'].'|');

			if (! empty($data)) fwrite($fp, "\n". $data);

			fclose($fp);

			echo '<p>Your message has been added<br />Go to the <a href="'.SELF.'">main</a> page to view it</p>';

			break;
		}

	}

?>
<script type="text/javascript">
<?php
echo "var words = new Array();"
?>
var msg = "Hi! Josh Keaton here.\n\nYou\'re going to sign my guestbook. That\'s cool! \n\n";
msg = msg + "Internet spam is a big problem on the internet. Lately I've been getting a lot of it ";
msg = msg + "so I made a little game to fool the spam machines and also strum up some fun for us.\n\n";
msg = msg + "Use one of the following words or phrases in your message! Let's get creative!\n\n";
<?php
foreach ($musthavetext as $text) {
echo "msg = msg + " . '"' . $text . '\n";';
}
?>
msg = msg + "\n\nPraise!\n- Josh";
alert(msg);
</script>
<?php
	echo "\n".'<form method="post" action="'.SELF.'?func=sign"><p><label for="name">Name:</label><br /><input type="text" name="name" id="name" value="'.$name.'" size="24" /><br /><label for="mesg">Message:</label> <a href="'.SELF.'?func=bbcode">BBCode</a><br /><textarea name="mesg" id="mesg" cols="20" rows="4">'.$mesg.'</textarea><br /><input type="submit" name="submit" value="Add" /></p></form>';
break;


/*================================*\
		 -- admin --
\*================================*/

case 'power2me':
	if ($password == ADMIN_PASS) {
		if (isset($_GET['d'])) {
			/*================================*\
				 -- admin delete --
			\*================================*/

			if (isset($_GET['c'])) {
				if (!$fp = @fopen(GB_FILE, 'r')) {
					echo 'Unable to open guestbook file for reading , check location and file permissions.';
					break;
				}

				$output = '';

				while (!feof($fp)) {
					$line = fgets($fp, 4096);

					if (substr($line, 0, 10) == $_GET['d']) {
						$output .= fread($fp, filesize(GB_FILE));

						fclose($fp);

						if (!$fp = @fopen(GB_FILE, 'w')) {
							echo 'Unable to open guestbook file for writing, check location and file permissions.';
							break;
						}

						fwrite($fp, $output);
						fclose($fp);

						echo '<p>Message has been <b>deleted</b>.<br />Go back to the <a href="'.SELF.'?func=power2me">admin</a> page<br /></p>';
						break 2;
					} else {
						$output .= $line;
					}
				}

				fclose($fp);

				echo '<p>There was an error deleting this post, it doesn\'t seem to exist<br />Go back to the <a href="'.SELF.'?func=power2me">admin</a> page and try again</p>';
			}


			if (!$fp = @fopen(GB_FILE, 'r')) {
				echo 'Unable to open guestbook file for reading, check location and file permissions.';
				break;
			}

			while (!feof($fp)) {
				$line = fgets($fp, 4906);

				if (substr($line, 0, 10) == $_GET['d']) {
					list($date, $name, $mesg) = explode ('|', $line);

					echo '<p>Are you sure you want to delete this entry?</p>';
					echo '<p><b>'.$name.'</b> - on '.date(DATE_FORMAT, $date).'<br />'.$mesg.'</p>';
					echo '<p><a href="'.SELF.'?func=power2me&amp;d='.$_GET['d'].'&amp;c=1">Yes</a> - <a href="'.SELF.'?func=power2me">No</a></p>';

					break 2;
				}
			}

			fclose($fp);

			echo '<p>There was an error finding this post, it doesn\'t seem to exist<br />Go back to the <a href="'.SELF.'?func=power2me">admin</a> page and try again</p>';
		} elseif (isset($_GET['e'])) {
			/*================================*\
				  -- admin edit --
			\*================================*/

			if (isset($_GET['c'])) {
				$name = (isset($_POST['name'])) ? strip_chars($_POST['name']) : NULL;
				$mesg = (isset($_POST['mesg'])) ? strip_chars($_POST['mesg']) : NULL;

				$errors = NULL;

				$name_len = strlen($name);
				$mesg_len = strlen($mesg);

				if ($name) {
					if ($name_len > MAX_NAME_LEN) {
						$errors = '- Name is too long, '.$name_len.' (Max: '.MAX_NAME_LEN.')<br />';
					}
				} else {
					$errors = '- Name field is empty<br />';
				}

				if ($mesg) {
					if ($mesg_len > MAX_MESG_LEN) {
						$errors.= '- Message is too long, '.$mesg_len.' (Max: '.MAX_MESG_LEN.')<br />';
					} elseif ($mesg_len < MIN_MESG_LEN) {
						$errors.= '- Message is too short  (Min: '.MIN_MESG_LEN.')<br />';
					}
				} else {
					$errors.= '- Message field is empty<br />';
				}

				if ($errors) {
					echo '<p>'.$errors.'</p>';
				} else {
					if (!$fp = @fopen(GB_FILE, 'r')) {
						echo 'Unable to open guestbook file for reading, check location and file permissions.';
						break;
					}

					$output = '';

					while (!feof($fp)) {
						$line = fgets($fp, 4096);

						if (substr($line, 0, 10) == $_GET['e']) {
							list($date, , , $ip) = str_replace('\|', '|', preg_split("/(?<!\\\)(\|)/", $line));

							$output .= $date.'|'.str_replace("\n", NULL, str_replace('|', '\|', $name)).' |'.str_replace("\n", '<br />', bbcode($mesg)).' |'.$ip."|\n".fread($fp, filesize(GB_FILE));

							fclose($fp);

							$fp = @fopen(GB_FILE, 'w');
								fwrite($fp, $output);
							fclose($fp);

							echo '<p>Message has been <b>edited</b>.<br />Go back to the <a href="'.SELF.'?func=power2me">admin</a> page<br /></p>';

							break 2;
						} else {
							$output .= $line;
						}
					}

					fclose($fp);

					echo '<p>There was an error finding this post, it doesn\'t seem to exist<br />Go back to the <a href="'.SELF.'?func=power2me">admin</a> page and try again</p>';
				}

			}

			if (isset($_POST['submit'])) {
				echo "\n".'<form method="post" action="'.SELF.'?func=power2me&amp;e='.$_GET['e'].'&amp;c=1"><p><label for="name">Name:</label><br /><input type="text" name="name" id="name" value="'.$name.'" size="24" /><br /><label for="mesg">Message:</label> <a href="'.SELF.'?func=bbcode">BBCode</a><br /><textarea name="mesg" id="mesg" cols="20" rows="4">'.$mesg.'</textarea><br /><input type="submit" name="submit" value="Edit" /></p></form>';
				break;
			}


			if (!$fp = @fopen(GB_FILE, 'r')) {
				echo 'Unable to open guestbook file for reading, check location and file permissions.';
				break;
			}

			while (!feof($fp)) {
				$line = fgets($fp, 4906);

				if (substr($line, 0, 10) == $_GET['e']) {
					list(, $name, $mesg) = str_replace('\|', '|', preg_split("/(?<!\\\)(\|)/", $line));

					$mesg = preg_replace("(\<b\>(.+?)\<\/b>)is", "[b]$1[/b]", $mesg);
					$mesg = preg_replace("(\<i\>(.+?)\<\/i\>)is", "[i]$1[/i]", $mesg);
					$mesg = preg_replace("(\<u\>(.+?)\<\/u\>)is", "[u]$1[/u]", $mesg);
					$mesg = preg_replace("(\<del\>(.+?)\<\/del\>)is", "[s]$1[/s]", $mesg);

					$mesg = str_replace('<br />', "\n", $mesg);
					$mesg = strip_tags($mesg);

					echo "\n".'<form method="post" action="'.SELF.'?func=power2me&amp;e='.$_GET['e'].'&amp;c=1"><p><label for="name">Name:</label><br /><input type="text" name="name" id="name" value="'.$name.'" size="24" /><br /><label for="mesg">Message:</label> <a href="'.SELF.'?func=bbcode">BBCode</a><br /><textarea name="mesg" id="mesg" cols="20" rows="4">'.$mesg.'</textarea><br /><input type="submit" name="submit" value="Edit" /></p></form>';

					break 2;
				}
			}

			fclose($fp);

			echo '<p>There was an error finding this post, it doesn\'t seem to exist<br />Go back to the <a href="'.SELF.'?func=power2me">admin</a> page and try again</p>';
		}
		else
		{
			/*================================*\
				 -- admin default --
			\*================================*/

			$gb_size = filesize(GB_FILE);

			echo '<p>======================<br />';
			echo 'file size: '.round($gb_size / 1024, 1).'KB<br />';
			echo '<br />======================</p>';

			if (!$fp = @fopen(GB_FILE, 'r')) {
				echo 'Unable to open guestbook file for reading and writing, check location and file permissions.';
				break;
			}

			$i = 0;
			$data = NULL;

			$to_show = (P * MAX_PER_PAGE) - MAX_PER_PAGE;

			if (P > 1) echo '<p><b>Page '.P.'</b></p>';

			while (!feof($fp)) {
				$i++;

				if ($i > ($to_show + MAX_PER_PAGE)) break;

				$data = fgets($fp, 4096);

				if (empty($data)) break;

				if ($i > $to_show) {
					list ($date, $name, $mesg, $ip) = str_replace('\|', '|', preg_split("/(?<!\\\)(\|)/", $data));
					echo ("\n<p><a href=\"".SELF."?func=power2me&amp;e=$date\">[edit]</a> <a href=\"".SELF."?func=power2me&amp;d=$date\">[delete]</a> <a href=\"http://whois.sc/$ip\">[whois]</a><br /><span><b>$name</b> on ".date(DATE_FORMAT, $date)."</span><br />$mesg</p>");
				}
			}

			if ($i > MAX_PER_PAGE) {
				$line_count = substr_count(fread($fp, $gb_size), "\n") + $i;
				$line_count = ceil($line_count / MAX_PER_PAGE);

				$s = 1;
				$f = $line_count + 1;

				echo "\n".'<p>Page: # ';

				if ($line_count > MAX_PER_PAGE) {
					if (P < 6) {
						$s = 1;
						$f = 10;
					} elseif (($line_count-P) < 6) {
						$s = $line_count - 8;
						$f = $line_count;
					} else {
						$s = P -3;
						$f = $s + 8;
					}

					echo (P > 5) ? ' <a href="'.SELF.'?func=power2me">1</a>-' : NULL;
				}

				for ($k=$s; $k<=$f; $k++) {
					echo ($k == P) ? "$k " : "<a href=\"".SELF."?func=power2me&amp;p=$k\">$k</a> ";
				}

				echo ($k <= $line_count) ? "of <a href=\"".SELF."?func=power2me&amp;p=$line_count\">$line_count</a></p>" : '</p>';
			}

			fclose($fp);
		}
	} else {
		if (isset($_POST['submit'])) echo '<p>Sorry wrong password</p>'  ;

		echo "\n".'<form method="post" action="'.SELF.'?func=power2me"><p><input type="password" name="password" size="20" /> <input type="submit" value="Login" name="submit" /></p></form>';
	}
break;


/*================================*\
		 -- BBCode --
\*================================*/
case 'bbcode':
	echo '
		<p>BBCode is a way of putting special effects into your text.  The allowed BBCode is:</p>
		<ul>
		<li>[b]<b>bold</b>[/b]</li>
		<li>[i]<i>italic</i>[/i]</li>
		<li>[u]<u>underline</u>[/u]</li>
		<li>[s]<del>strikethrough</del>[/s]</li>
		</ul>
		<p>For example: to make <b>this</b> bold.  when posting a message add the tags [b] and [/b] around the text (as seen above).</p>
	';
break;
}


/*================================*\
	   -- functions --
\*================================*/

function strip_chars($var) {
	return trim(str_replace("\r", NULL, htmlspecialchars(stripslashes(strip_tags($var)), ENT_QUOTES)));
}

function bbcode($var) {
	if (ALLOW_URLS == true)
		$var = preg_replace('/http:\/\/[\w]+(.[\w]+)([\w\-\.,@?^=%&:\/~\+#]*[\w\-\@?^=%&\/~\+#])?/i', '<a href="$0">$0</a>', $var);

	$var = preg_replace('(\[b\](.+?)\[\/b\])is', '<b>$1</b>', $var);
	$var = preg_replace('(\[i\](.+?)\[\/i\])is', '<i>$1</i>', $var);
	$var = preg_replace('(\[u\](.+?)\[\/u\])is', '<u>$1</u>', $var);
	$var = preg_replace('(\[s\](.+?)\[\/s\])is', '<del>$1</del>', $var);

	return trim(str_replace('|', '\|', $var));
}

/*================================*\
	 -- end functions --
\*================================*/


?>

<?php
include "jk_footer_1000.php";
?>


