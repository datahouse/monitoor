<?php

namespace Datahouse\MON;

ini_set('xdebug.var_display_max_depth', 6);
ini_set('xdebug.max_nesting_level', 10000);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . '/vendor/autoload.php');

use \Dice\Dice as Dice;
use \Dice\Rule as DiceRule;
use \Datahouse\Framework\Router as Router;

\rpt_rpt::set_report_file('./log/mon.log');
\rpt_rpt::enable_indent();

// ..thanks to Stack-Overflow...
function strToHex($string)
{
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

function sparkline_link($data)
{
    // Strip leading and trailing '{' and '}'
    $data = substr($data, 1, strlen($data) - 1);

    // Skip 'NULL's
    $data = explode(",", $data);
    $data = array_filter($data, function ($v) {
        return $v != 'NULL';
    });
    $data = implode(",", $data);

    // generate sparkline.php link
    $link = 'sparkline.php?size=100x25&data=' . $data
        . '&back=fff&line=5b6&fill=dfd';
    return '<img src="' . $link . '" width="100" height="25"/>';
}

function showStatusPage($pdo)
{
    $pdo->beginTransaction();

    echo '<h2>registered spiders</h2><table><tr><th>uuid</th><th>age</th><th>assigned<br/>jobs</th><th>errors<br/>(last 24h)</th><th>load<br/>(last 24h)</th><th>network traffic<br/>(last 24h)</th><th>iowait time<br/>(last 24h)</th></tr>';

    $query =
        'SELECT
            spider.spider_uuid AS uuid,
            now() - spider.spider_last_seen AS age,
            jobs.assigned_jobs,
            errs.count AS num_errors,
            load.agg_load_one, load.max_load_one,
            load.agg_network, load.max_network,
            load.agg_iowait_time, load.max_iowait_time
         FROM spider
         LEFT JOIN LATERAL (
           SELECT COUNT(1) AS assigned_jobs FROM spider_job
           WHERE spider.spider_uuid = spider_job.job_spider_uuid
         ) AS jobs ON True
         LEFT JOIN LATERAL (
           SELECT COUNT(1) AS count FROM spider_errlog
           WHERE spider_uuid = spider.spider_uuid
             AND now() - ts < \'1 day\'
         ) AS errs ON True
         LEFT JOIN LATERAL (
           SELECT
             array_agg(load_one_avg) AS agg_load_one,
             max(load_one_avg) AS max_load_one,
             array_agg(network_bytes_sum) AS agg_network,
             max(network_bytes_sum) AS max_network,
             array_agg(cpu_iowait_time_sum) AS agg_iowait_time,
             max(cpu_iowait_time_sum) AS max_iowait_time
           FROM spider_load_agg
           WHERE spider_uuid = spider.spider_uuid
         ) AS load ON True
         ORDER BY spider.spider_uuid NULLS LAST;
    ';
    $res = $pdo->query($query);

    foreach ($res->fetchAll() as $row) {
        $fmt = "<tr>" . str_repeat("<td>%s</td>", 7) . "</tr>";

        $load_one_sparkline = sparkline_link($row['agg_load_one']);
        $network_sparkline = sparkline_link($row['agg_network']);
        $iowait_sparkline = sparkline_link($row['agg_iowait_time']);

        $network_max = round(
          $row['max_network'] / 5.0 / 60.0 / 1000000.0,
          2
        ) . ' Mbps';

        $iowait_max = $row['max_iowait_time'] / 5.0 / 60.0 * 100.0 . '%';

        echo sprintf(
            $fmt,
            (
                strlen($row['uuid']) > 0
                ? substr($row['uuid'], 0, 8) . '..'
                : '(unassigned)'
            ),
            $row['age'],
            $row['assigned_jobs'],
            "<a href=\"status.php?errors=" . $row['num_errors'] . "&job=" . $row['uuid'] . "\">" . $row['num_errors'] . "</a>",
            $load_one_sparkline . '(' . $row['max_load_one'] . ')',
            $network_sparkline . '(' . $network_max . ')',
            $iowait_sparkline . '(' . $iowait_max . ')'
        );
    }
    echo "</table>\n";

    $res = $pdo->query(
      'SELECT job_active, count(1) AS cnt
       FROM spider_job
       GROUP BY job_active
       ORDER BY job_active DESC;
    ');

    echo "<h2>total jobs</h2><table><tr><th>active</th><th>count</th></tr>\n";
    foreach ($res->fetchAll() as $row) {
        echo sprintf(
          "<tr><td>%s</td><td>%s</td></tr>\n",
          ($row['job_active'] ? 'yes' : 'no'),
          $row['cnt']
        );
    }
    echo "</table>\n";

    echo "<h2>Retrieve Document</h2>\n";
    echo '<form action="status.php"><input name="document_id" type="text"/><br/>
          <input type="submit"/></form>';

    echo "<h2>URL Status</h2>\n";
    echo '<div><form action="status.php">';
    echo '<label>URL oder url_id: </label>';
    echo '<input name="url_status" type="text" /><input type="submit"/></form></div>';

    echo "<h2>Test Xpath</h2>\n";
    echo getTestXpathForm();

    $pdo->rollBack();
}

function showDocument($pdo, $document_id)
{
    try {
        $document_id = intval($document_id);
    } catch (Exception $e) {
        "<h2>Invalid document id</h2>";
        return;
    }
    echo "<h2>Document $document_id</h2>\n";

    $pdo->beginTransaction();
    $res = $pdo->query("SELECT contents, contents_hash, reception_ts,
                               xfrm_commands, xfrm_args, url
                        FROM spider_document
                        LEFT JOIN spider_job USING (job_id)
                        LEFT JOIN xfrm USING (xfrm_id)
                        WHERE spider_document_id = $document_id;");
    foreach ($res->fetchAll() as $row) {
        $contents = stream_get_contents($row['contents']);
        $contents_hash = stream_get_contents($row['contents_hash']);
        $url = $row['url'];
        $urlLink = "status.php?url_status=" . urlencode($url);
        echo "<p>URL: " . $row['url'] . " (<a href=\"$urlLink\">url status</a>)</p>\n";
        echo "<p>Hash: " . strToHex($contents_hash) . "</p>\n";
        echo "<p>Received: " . $row['reception_ts'] . "</p>\n";
        echo "<p>Transformation commands used: '" . $row['xfrm_commands']
            . "' with arguments: " . $row['xfrm_args'] . "</p>\n";
        echo '<pre style="background-color: #000; color: #fff; font-family: mono;">'
            . $contents . '</pre>';
    }

    $pdo->rollBack();
}

function showUser($pdo)
{
    $pdo->beginTransaction();

    $query =
        "SELECT u.user_id, user_email, account_name_first, account_name_last, account_company, user_valid_from, ";
    $query .= "user_activated, p.pricing_plan_id, p.pricing_plan_text, voucher_code from mon_user u JOIN account a ON ";
    $query .= "(a.user_id=u.user_id) JOIN pricing_plan p ON (a.pricing_plan_id=p.pricing_plan_id) LEFT JOIN voucher v ON (a.voucher_id=v.voucher_id) ";
    $query .= "WHERE user_valid_till is NULL OR user_valid_till > NOW() ";
    $res = $pdo->query($query);
    echo('user_id;email;first_name;last_name;company;valid_from;active;plan_id;plan_text;voucher;' . '<br/>');
    foreach ($res->fetchAll() as $row) {
        echo($row['user_id']) . ';';
        echo($row['user_email']) . ';';
        echo($row['account_name_first']) . ';';
        echo($row['account_name_last']) . ';';
        echo($row['account_company']) . ';';
        echo($row['user_valid_from']) . ';';
        echo($row['user_activated']) . ';';
        echo($row['pricing_plan_id']) . ';';
        echo($row['pricing_plan_text']) . ';';
        echo($row['voucher_code']) . ';' . '<br/>';
    }

    $pdo->rollBack();
}

function showUrl($pdo)
{
    $pdo->beginTransaction();

    $query =
        "SELECT c.user_id,u.url_id, u.url_title, u.url, u.url_group_id, g.url_group_title, al.alert_id, al.alert_option_name, al.alert_threshold, xfrm_args ";
    $query .= "FROM access_control c JOIN url u ON u.url_id=c.url_id ";
    $query .= "JOIN mon_user m ON (c.user_id=m.user_id) ";
    $query .= "JOIN url_group g ON (u.url_group_id=g.url_group_id) ";
    $query .= "LEFT JOIN (SELECT a. alert_id, url_group_id, user_id, alert_option_name, alert_threshold FROM alert_x_url_group ax JOIN alert a ON (a.alert_id=ax.alert_id) ";
    $query .= "JOIN alert_option o ON (a.alert_option_id=o.alert_option_id) WHERE alert_active) as al ON (g.url_group_id = al.url_group_id AND c.user_id = al.user_id) ";
    $query .= "LEFT JOIN xfrm f ON (u.xfrm_id=f.xfrm_id) ";
    $query .= "WHERE url_active AND access_type_id=2  AND user_activated AND (user_valid_till is NULL OR user_valid_till > NOW()) ";
    $query .= "ORDER BY 1,2,5";
    $res = $pdo->query($query);
    echo('user_id;url_id;url_title;url;xpath;url_group_id;url_group_title;alert_id;alert_option;alert_threshold;<br/>');
    foreach ($res->fetchAll() as $row) {
        echo($row['user_id']) . ';';
        echo($row['url_id']) . ';';
        echo($row['url_title']) . ';';
        echo($row['url']) . ';';
        echo($row['xfrm_args']) . ';';
        echo($row['url_group_id']) . ';';
        echo($row['url_group_title']) . ';';
        echo($row['alert_id']) . ';';
        echo($row['alert_option_name']) . ';';
        echo($row['alert_threshold']) . ';' . '<br/>';
    }

    $pdo->rollBack();
}

function showKeyword($pdo)
{
    $pdo->beginTransaction();

    $query = "SELECT  a.user_id, a.alert_id, alert_keyword  ";
    $query .= "FROM alert a JOIN alert_keyword k ON (a.alert_id=k.alert_id) ";
    $query .= "JOIN mon_user m ON (a.user_id=m.user_id) ";
    $query .= "WHERE alert_keyword_active AND user_activated AND (user_valid_till is NULL OR user_valid_till > NOW()) ";
    $query .= "ORDER BY 1,2,3";
    $res = $pdo->query($query);
    echo('user_id;alert_id;alert_keyword;<br/>');
    foreach ($res->fetchAll() as $row) {
        echo($row['user_id']) . ';';
        echo($row['alert_id']) . ';';
        echo($row['alert_keyword']) . ';' . '<br/>';
    }

    $pdo->rollBack();
}

function showFinanz($pdo)
{
    $pdo->beginTransaction();

    $query = "SELECT u.user_id, user_email, url_id, user_action, subscription_ts ";
    $query .= " FROM user_subscription s, mon_user u WHERE u.user_id=s.user_id ";
    $query .= " AND url_id = (SELECT url_id FROM url where url_group_id = ";
    $query .= " (SELECT url_group_id FROM url_group WHERE url_group_title ='Wirtschaftsnachrichten' AND is_subscription)) ";
    $query .= " ORDER BY subscription_ts DESC  ";
    $res = $pdo->query($query);

    echo('user_id;email;url_id;action;timestamp;<br/>');
    foreach ($res->fetchAll() as $row) {
        echo($row['user_id']) . ';';
        echo($row['user_email']) . ';';
        echo($row['url_id']) . ';';
        echo($row['user_action']) . ';';
        echo($row['subscription_ts']) . ';' . '<br/>';
    }
    $pdo->rollBack();
}

function showErrors($pdo, $errors, $job)
{
    try {
        $size = intval($errors);
        if ($size > 1000) {
            $size = 1000;
        }
    } catch (Exception $e) {
        "<h2>Invalid number</h2>";
        return;
    }

    echo "<h2>Errors</h2>\n";

    $pdo->beginTransaction();
    $query = 'select * from spider_errlog where spider_uuid= :job order by ts desc LIMIT :size';
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':job', $job, \PDO::PARAM_STR);
    $stmt->bindValue(':size', $size, \PDO::PARAM_INT);
    $stmt->execute();
    $stmt->execute();
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        echo "<p>" . $row['ts'] . " | ";
        echo htmlentities($row['msg']) . " | ";
        echo $row['spider_uuid'] . "</p>\n";
        echo '<pre style="background-color: #000; color: #fff; font-family: mono;"></pre>';
    }
    $pdo->rollBack();
}

function getTestXpathForm($url='', $xpath='', $xpath_ignore='')
{
    $xpath = htmlspecialchars($xpath);
    $xpath_ignore = htmlspecialchars($xpath_ignore);

    echo '<div id="test-xpath-form">';
    echo '<form action="status.php">
        <label>URL</label><input type="text" name="test_xpath_url" value="' . $url . '">
        <label>XPath</label><input type="text" name="xpath" value="' . $xpath . '"/>
        <label>XPath Ignore</label><input type="text" name="xpath_ignore" value="' . $xpath_ignore . '"/>
        <input type="submit"/></form>';
    echo '</div>';
}

function testXpath($pdo, $url, $xpath, $xpath_ignore)
{
    try {
        // $document_id = intval($document_id);
    } catch (Exception $e) {
        "<h2>Invalid document id</h2>";
        return;
    }
    echo "<h2>XPath Test Result</h2>\n";

    echo getTestXpathForm($url, $xpath, $xpath_ignore);

    // Listen on the result channel before dispatching work.
    $pdo->exec('LISTEN spider_live_result_channel');

    $pdo->beginTransaction();
    $task_data = [
        'url' => $url,
        'xpath' => $xpath,
        'xpath-ignore' => $xpath_ignore
    ];
    $task_data_val = pg_escape_string(utf8_encode(json_encode($task_data)));
    $res = $pdo->query(
        "SELECT enqueue_live_task('test-xpath', '$task_data_val') AS task_uuid;"
    );

    $row = $res->fetch();
    $task_uuid = $row['task_uuid'];
    echo 'task_uuid: <pre>' . $task_uuid . '</pre>';
    $pdo->commit();

    // then wait for a response
    $channel = null;
    for ($retries = 15; $retries > 0; $retries--) {
        $notification = $pdo->pgsqlGetNotify(\PDO::FETCH_NUM, 1000);
        if (is_array($notification)) {
            list($channel, $pid) = $notification;
            if ($channel == 'spider_live_result_channel') {
                break;
            }
            // ignore other notifications
        } else {
        }
    }

    if (is_null($channel)) {
        echo '<p class="error">Task did not succeed within 15 seconds.</p>';
    } else {
        echo "<h2>Transformation Result</h2>\n";

        $pdo->beginTransaction();
        $res = $pdo->query("DELETE FROM live_task_result
                        WHERE task_uuid = '$task_uuid'
                        RETURNING task_result;");
        foreach ($res->fetchAll() as $row) {
            $result = json_decode($row['task_result'], true);
            if ($result['success']) {
                echo '<pre style="background-color: #000; color: #fff; font-family: mono;">'
                    . $result['data'] . '</pre>';
            } else {
                echo '<p class="error">Transformation failed in the backend: '
                    . $result['error'] . '</p>';
            }
        }

        $pdo->rollBack();
    }
}

function showUrlStatus($pdo, $queryUrl)
{
    echo "<h2>URL Status</h2>\n";

    // lookup the URL
    $pdo->beginTransaction();
    $query = 'SELECT
             last_check_ts,
             last_modification,
             spider_job.url,
             json_agg(same_url.url_id) AS url_ids,
             json_agg(same_url.url_group_id) AS url_group_ids,
             spider_job.job_id
         FROM spider_job
         LEFT JOIN url AS lookup_url
             ON lookup_url.spider_job_id = spider_job.job_id
             AND spider_job.url <> :url_str
         LEFT JOIN url AS same_url
             ON same_url.spider_job_id = spider_job.job_id
         WHERE (spider_job.url = :url_str OR lookup_url.url_id = :url_int)
         GROUP BY
             last_check_ts,
             last_modification,
             spider_job.url,
             spider_job.job_id';
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':url_str', $queryUrl, \PDO::PARAM_STR);
    $stmt->bindValue(':url_int', intval($queryUrl), \PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $jobId = $row['job_id'];
        $urlIds = json_decode($row['url_ids']);
        $urlGroupIds = json_decode($row['url_group_ids']);
        echo '<table>';
        echo '<tr><td>url</td><td>' . $row['url'] . '</td></tr>';
        echo '<tr><td>last check (effective)</td><td>' . $row['last_check_ts'] . '</td></tr>';
        echo '<tr><td>Last-Modification (HTTP)</td><td>' . $row['last_modification'] . '</td></tr>';
        echo '<tr><td>url ids</td><td>' . implode(', ', $urlIds) . '</td></tr>';
        echo '<tr><td>url group ids</td><td>' . implode(', ', $urlGroupIds) . '</td></tr>';
        echo "</table>";
    } else {
        $parts = array_filter(preg_split('/[\/\:]+/', $queryUrl), function ($part) {
            return substr($part, 0, 4) !== 'http';
        });
        $pattern = '%' . implode('%', $parts) . '%';
        echo "<p class=\"error\">ERROR: no such URL found: &quot;$queryUrl&quot;</p>\n";

        echo "<h2>similar URLs</h2>";
        $query = 'SELECT url, json_agg(url_id) AS url_ids
            FROM url
            WHERE url.url LIKE :pattern
            GROUP BY url;';
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':pattern', $pattern, \PDO::PARAM_STR);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $url_ids = json_decode($row['url_ids']);
            $link = "status.php?url_status=" . $url_ids[0];
            echo(
                '<p>' . $row['url']
                . ": <a href=\"$link\">url status</a>, url ids: "
                . $row['url_ids'] . '</p>'
            );
        }
        return;
    }

    echo "<h3>alerts on this url</h3>";
    $query = 'SELECT
            alert.alert_id,
            mon_user.user_email,
            json_agg(DISTINCT alert_keyword.alert_keyword) AS keywords,
            json_agg(DISTINCT alert_cycle.alert_cycle_description) AS cycles,
            json_agg(DISTINCT alert_type.alert_type_description) AS types
        FROM alert_x_url_group
        LEFT JOIN alert
            ON alert.alert_id = alert_x_url_group.alert_id
        LEFT JOIN alert_x_type_cycle
            ON alert.alert_id = alert_x_type_cycle.alert_id
        LEFT JOIN type_x_cycle
            ON type_x_cycle.type_x_cycle_id = alert_x_type_cycle.type_x_cycle_id
        LEFT JOIN alert_cycle
            ON alert_cycle.alert_cycle_id = type_x_cycle.alert_cycle_id
        LEFT JOIN alert_type
            ON alert_type.alert_type_id = type_x_cycle.alert_type_id
        LEFT JOIN mon_user
            ON mon_user.user_id = alert.user_id
        LEFT JOIN alert_keyword
            ON alert_keyword.alert_id = alert.alert_id
            AND alert_keyword.alert_keyword_active
        WHERE alert_x_url_group.url_group_id = ANY((:url_group_ids)::INT[])
            AND alert.alert_active
        GROUP BY
            alert.alert_id,
            mon_user.user_email';
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(
        ':url_group_ids',
        '{' . implode(',', $urlGroupIds) . '}',
        \PDO::PARAM_STR
    );
    $stmt->execute();
    echo "<table>\n";
    echo "<tr><th>alert_id</th><th>user_email</th><th>cycle</th><th>type</th><th>keywords</th></tr>\n";
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $keywords = json_decode($row['keywords']);
        $keywords = count($keywords) > 0 ? implode(', ', $keywords) : 'n/a';
        $cycles = json_decode($row['cycles']);
        $types = json_decode($row['types']);
        echo(sprintf(
            "<tr>" . str_repeat("<td>%s</td>", 5) . "</tr>",
            $row['alert_id'],
            $row['user_email'],
            implode(', ', $cycles),
            implode(', ', $types),
            $keywords
        ));
    }
    echo "</table>";


    echo "<h3>versions retrieved</h3>";
    $query = 'SELECT
            spider_document_id,
            reception_ts,
            xfrm.xfrm_commands,
            xfrm.xfrm_args,
            json_agg(notification.alert_id) AS triggered_alert_ids,
            json_agg(alert_keyword.alert_keyword) AS keywords_matched
        FROM spider_document
        LEFT JOIN xfrm
            ON xfrm.xfrm_id = spider_document.xfrm_id
        LEFT JOIN change
            ON change.new_doc_id = spider_document.spider_document_id
        LEFT JOIN notification
            ON notification.change_id = change.change_id
        LEFT JOIN notification_x_keyword x
            ON x.alert_id = notification.alert_id
            AND x.change_id = notification.change_id
            AND x.type_x_cycle_id = notification.type_x_cycle_id
        LEFT JOIN alert_keyword
            ON x.alert_keyword_id = alert_keyword.alert_keyword_id
        WHERE job_id = :job_id
          AND spider_document.reception_ts >= (now() - \'3 months\'::INTERVAL)
        GROUP BY
            spider_document_id,
            reception_ts,
            xfrm.xfrm_commands,
            xfrm.xfrm_args
        ORDER BY reception_ts DESC
        LIMIT 1000';
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':job_id', $jobId, \PDO::PARAM_INT);
    $stmt->execute();
    echo "<table>\n";
    echo "<tr><th>reception_ts</th><th>document</th><th>xfrm_commands</th><th>xfrm_args</th><th>notifications triggered</th><th>keywords matched</th></tr>\n";
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $docLink = "status.php?document_id=" . $row['spider_document_id'];
        $doc = '<a href="' . $docLink . '">' . $row['spider_document_id'] . '</a>';
        $alertIds = json_decode($row['triggered_alert_ids']);
        $keywords = json_decode($row['keywords_matched']);
        echo(sprintf(
            "<tr>" . str_repeat("<td>%s</td>", 6) . "</tr>",
            $row['reception_ts'],
            $doc,
            $row['xfrm_commands'],
            $row['xfrm_args'],
            implode(', ', $alertIds),
            implode(', ', $keywords)
        ));
    }
    echo "</table>";
    $pdo->rollBack();
}

function showLogfile()
{
    echo(nl2br(file_get_contents('./log/mon.log')) . "\n");
}

try {
    $dice = new Dice();
    $rule = new DiceRule;
    $rule->shared = true;
    $rule->constructParams = [true, 512];
    $dice->addRule('Datahouse\\Libraries\\JSON\\Converter\\Config', $rule);

    // Load the database configuration from the project-wide json.
    $db_config_str =
        file_get_contents(dirname(__FILE__) . '/conf/.db.conf.json');
    if ($db_config_str === false) {
        throw new \Exception(
            "Cannot read database configuration file: .db.conf.json"
        );
    }
    $conv = $dice->create('Datahouse\\Libraries\\JSON\\Converter');
    $db_config = $conv->decode($db_config_str);
    assert($db_config['type'] == "postgres");

    $dsn_parts = [];
    if (array_key_exists("host", $db_config)) {
        $dsn_parts[] = "host=" . $db_config['host'];
    }
    if (array_key_exists("port", $db_config)) {
        $dsn_parts[] = "port=" . $db_config['port'];
    }
    if (array_key_exists("database", $db_config)) {
        $dsn_parts[] = "dbname=" . $db_config['database'];
    }
    $dsn = "pgsql:" . implode(";", $dsn_parts);

    // Switch PDO's error mode to give us proper exceptions.
    $db_options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

    $rule = new DiceRule;
    $rule->shared = true;
    $rule->constructParams = [
      $dsn,
      $db_config['username'],
      $db_config['password'],
      $db_options
    ];
    $dice->addRule('PDO', $rule);

    try {
        // Test-run the database connection, so as to emit a somewhat
        // helpfull error message. Otherwise, we only get a misleading Dice
        // stack trace, complaining that it cannot instantiate a certain
        // Model.
        $pdo = $dice->create('PDO');

        // Switch PDO's error mode to give us proper exceptions.
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Keep this as simple and stupid as possible.
        header('Content-Type: text/html; charset=UTF-8');

        // ..uh, well, I still prefer things to be *readable*.
        echo "<html><head><style> html { font-family: sans; } table, th, " .
            "td { text-align: left; padding: 0 20px; }

            form { width: 480px; padding-bottom: 20px; }

            input[type=text] {
    width: 100%;
    padding: 4px 4px;
    margin: 8px 0;
    box-sizing: border-box;
}

            .error { color: red; }

            </style></head>";
        echo "<body><h1>monitor status</h1>\n";


        if (array_key_exists('document_id', $_REQUEST)) {
            showDocument($pdo, $_REQUEST['document_id']);
        } else if (array_key_exists('errors', $_REQUEST) && array_key_exists('job', $_REQUEST)) {
            showErrors($pdo, $_REQUEST['errors'], $_REQUEST['job']);
        } else if (array_key_exists('log', $_REQUEST)) {
            showLogfile();
        } else if (array_key_exists('user', $_REQUEST)) {
            showUser($pdo);
        } else if (array_key_exists('url', $_REQUEST)) {
            showUrl($pdo);
        } else if (array_key_exists('keyword', $_REQUEST)) {
            showKeyword($pdo);
        } else if (array_key_exists('finanz', $_REQUEST)) {
            showFinanz($pdo);
        } else if (array_key_exists('test_xpath_url' , $_REQUEST)) {
            testXpath(
                $pdo,
                $_REQUEST['test_xpath_url'],
                $_REQUEST['xpath'],
                $_REQUEST['xpath_ignore']
            );
        } elseif (array_key_exists('url_status', $_REQUEST)) {
            $url = $_REQUEST['url_status'];
            showUrlStatus($pdo, $url);
        } else {
            showStatusPage($pdo);
        }

        echo '</body></html>';

    } catch (\Exception $e) {
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            'api.php'
        );
        $log->add(
            "Failed to connect to the database: " .
            $e->getMessage()
        )->end();
        // FIXME: This certainly isn't the best way to display an error
        // message, especially not for an API.
        echo "Configuration error, please check the log file.\n";
        exit;
    }
} catch (\Exception $e) {
    $log = new \rpt_rpt(
        \rpt_level::E_CRITICAL,
        'api.php'
    );
    $log->add($e->getMessage())->end();
}
