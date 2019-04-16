<?php

require_once('db.inc.php');

$path = 0;

if (isset($_GET['path'])) $path = intval($_GET['path']);

$q = mysql_query("SELECT COUNT(*) AS revs FROM version WHERE path_id = $path",$db);
while ($r = mysql_fetch_assoc($q)) $revs = $r['revs'];

$q = mysql_query("SELECT site.* FROM site JOIN path USING(site_id) WHERE path_id = $path",$db);
$sitei = mysql_fetch_assoc($q);

$q = mysql_query("SELECT * FROM path WHERE path_id = $path",$db);
$pathi = mysql_fetch_assoc($q);

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Website MONitoring (BETA)</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="lib/codemirror.js"></script>
	<link type="text/css" rel="stylesheet" href="lib/codemirror.css" />
	<script type="text/javascript" src="lib/mergely.js"></script>
	<link type="text/css" rel="stylesheet" href="lib/mergely.css" />

	<script type="text/javascript">

		$(document).ready(function () {
		        var path = <?php echo $path; ?>;
		        var back = 0;
		        var revs = <?php echo $revs; ?>;
			$('#compare').mergely({
				width: 'auto',
				height: 'auto', // containing div must be given a height
				cmsettings: { readOnly: true },
			});
		    function update() {
			var lhs_url = 'item.php?path=' + path + '&back=' + (back + 1);
			var rhs_url = 'item.php?path=' + path + '&back=' + back;
			$.ajax({
				type: 'GET', async: true, dataType: 'text',
				url: lhs_url,
				success: function (response) {
					$('#compare').mergely('lhs', response);
				}
			});
			$.ajax({
				type: 'GET', async: true, dataType: 'text',
				url: rhs_url,
				success: function (response) {
					$('#compare').mergely('rhs', response);
				}
			});
			$.ajax({
				type: 'GET', async: true, dataType: 'text',
				url: rhs_url + '&type=date',
				success: function (response) {
					$('#date').text(response);
				}
			});
                    }
                        update()
                        $('#prev').click(function(event){
                            event.preventDefault();
		            back = back + 1;
                            if (back >= revs) back = revs - 1;
                            update();
		            return false;
		        });
                        $('#next').click(function(event){
                            event.preventDefault();
		            back = back - 1;
                            if (back < 0) back = 0;
                            update();
		            return false;
		        });
		});

	</script>

</head>
<body>
<form action="index.php" mode="get">
<p>
<select name="path">
<option selected="selected" value="0">--- please choose ---</option>
<?php
   $q = mysql_query("SELECT path_id AS id, site_description AS sdesc, path_description AS pdesc FROM path JOIN site USING(site_id) WHERE site_active = TRUE",$db);
   while ($r = mysql_fetch_assoc($q)) {
     echo '<option value="' . $r['id'] . '">' . $r['sdesc'] . ' - ' . $r['pdesc'] . '</option>' . "\n";
   }
?>
</select>
<input type="submit" name="submit" value="Update" />
</p>
<hr />
<p>
<b>SITE</b> ID: <i><?php echo $sitei['site_id']; ?></i>, URI: <i><a href="<?php echo $sitei['site_uri']; ?>"><?php echo $sitei['site_uri']; ?></a></i>, DESCRIPITON: <i><?php echo $sitei['site_description']; ?></i>
</p>
<p>
<b>PATH</b> ID: <i><?php echo $pathi['path_id']; ?></i>, XPATH: <i><?php echo $pathi['path_xpath']; ?></i>, FORMAT: <i><?php echo $pathi['path_format']; ?></i>
</p>
<p>
<b>VERSION</b> <a id="prev" href="">(prev)</a> <i id="date"></i> <a id="next" href="">(next)</a>
</p>
        <hr />
	<div id="mergely-resizer" style="height: 600px; width: 99%;">
	     <div id="compare"></div>
	</div>

</body>
</html>
