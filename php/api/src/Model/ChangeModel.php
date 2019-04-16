<?php

namespace Datahouse\MON\Model;

use Datahouse\Framework\Model;
use Datahouse\MON\Common\KeywordHighlighter;
use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Types\Gen\Change;
use Datahouse\MON\Types\Gen\ChangeItem;

/**
 * Class PasswordModel
 *
 * @package     Model
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class ChangeModel extends Model
{
    /**
     * @var \PDO the pdo
     */
    protected $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * checkUserChange
     *
     * @param $changeId
     * @param $userId
     *
     * @return bool
     * @throws \Exception
     */
    public function checkUserChange($changeId, $userId)
    {
        $query = '';
        try {
            $query = 'SELECT change_id FROM v_change WHERE ';
            $query .= ' change_id = :change ';
            $query .= ' AND user_id = :user ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':change', $changeId, \PDO::PARAM_INT);
            $stmt->bindValue(':user', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * createChangeItem
     *
     * @param array $res the result
     *
     * @return ChangeItem
     */
    protected function createChangeItem(array $res)
    {
        $changeItem = new ChangeItem();
        $alertId = $res['alert_id'];
        $changeItem->setAlert(
            array('id' => $alertId)
        );
        $changeItem->setUrlGroup(
            array(
                'id' => $res['url_group_id'],
                'title' => $res['url_group_title']
            )
        );
        $changeItem->setUrl(
            array(
                'id' => $res['url_id'],
                'title' => $res['url_title'],
                'url' => ($res['url']),
                'external' => (0 === strpos($res['url'], 'external'))
            )
        );
        $changeItem->setRating($res['rating_value_id']);
        $change = new Change();
        $change->setFavorite($res['favorite']);
        $change->setId($res['change_id']);
        $change->setChangeDate(
            date_format(new \DateTime($res['creation_ts']), 'd.m.Y H:i:s')
        );

        // Postgres' json_agg adds JSON-nulls we are not interested in.
        $keywords = array_filter(
            json_decode($res['keywords']),
            function ($v) {
                return !is_null($v);
            }
        );

        // The backend used to store an old and a new document. Here, the API
        // then created a diff between the two. Something the backend already
        // has to do.
        //
        // Since v1.5, the backend stores the delta in the database, including
        // annotations for matched keywords.
        if (!is_null($res['delta'])) {
            $delta_data = json_decode($res['delta'], true);

            $diff = "";
            $diffHtml = "";
            $diffPreview = "";

            // version of the delta format - if no version is given, we're
            // talking v1.
            $version = isset($delta_data['version'])
                ? $delta_data['version'] : 1;
            if ($version == 1) {
                // This case was only possible for external data sources
                // before v1.5 of MON.
                //
                // Here, the delta simply consists of an array of
                // diff-sections, each one having added and removed lines.
                $sections = $delta_data;
            } else {
                // More detailed, but still based on a textual diff. Contains
                // a version field, the sections and match_positions for
                // keyword matches per alertId.
                $sections = $delta_data['sections'];
            }

            if ($version === 1) {
                list ($diff, $diffHtml, $diffPreview, $alternativeUrl)
                    = $this->renderDeltaVersion1($sections);
            } elseif ($version == 2) {

                assert(array_key_exists('match_positions', $delta_data));
                $mpos = $delta_data['match_positions'];
                $alert_mpos = isset($mpos[$alertId]) ? $mpos[$alertId] : [];
                list ($diff, $diffHtml, $diffPreview, $alternativeUrl)
                    = $this->renderDeltaVersion2($sections, $alert_mpos);
            } else {
                throw new \RuntimeException('unknown delta version');
            }
            $change->setDiff($diff);
            $change->setDiffHtml($diffHtml);
            $change->setDiffPreview($diffPreview);
            $changeItem->setAlternativeUrl($alternativeUrl);
        } else {
            // This code path should only be executed for changes created
            // by the backend before v1.5.
            $change->setNewDoc(
                array(
                    'id' => $res['new_doc_id']
                    //,'content' => $this->getDocument($res['new_doc_id'])
                )
            );
            $change->setOldDoc(
                array(
                    'id' => $res['old_doc_id']
                    //,'content' => $this->getDocument($res['old_doc_id'])
                )
            );
            $oldContent = $res['old_doc_id'];
            $newContent = $res['new_doc_id'];
            $diffs = $this->getDiff(
                $this->getDocument($oldContent),
                $this->getDocument($newContent),
                new \DateTime(
                    $res['creation_ts']
                ),
                $keywords
            );
            //similar_text($oldContent, $newContent, $percentage);
            //$a = $percentage; // the higher the more similar the docs are
            $change->setDiff($diffs['diff']);
            $change->setDiffHtml($diffs['diffHtml']);
            $change->setDiffPreview($diffs['diffPreview']);
        }

        $change->setMatchedKeywords($keywords);
        $changeItem->setChange($change);
        return $changeItem;
    }

    private function renderDeltaVersion1($sections)
    {
        $diff = "";
        $diffHtml = "";
        $diffPreview = "";
        $alternativeUrl = null;

        $maxLines = 5;
        foreach ($sections as $section) {
            assert(is_array($section));
            if (array_key_exists('add', $section)) {
                $lines = $this->splitByNewline($section['add']);
                foreach ($lines as $idx => $line) {
                    $altUrl = $this->getAlternativeUrl($line);
                    if ($altUrl) {
                        if (!isset($alternativeUrl)) {
                            $alternativeUrl = $altUrl;
                        }
                        continue;
                    }
                    $diff .= '+ ' . $line . PHP_EOL;

                    $htmlLine = $this->substituteMarkdown($line);
                    $diffHtml .= '<div class="ins">' . $htmlLine . '</div>';

                    // Use only the first couple of lines for the preview.
                    if ($maxLines > 0) {
                        $diffPreview .= '<div>' . $htmlLine . '</div>';
                        $maxLines--;
                    }
                }
            }
            if (array_key_exists('del', $section)) {
                $lines = $this->splitByNewline($section['del']);
                foreach ($lines as $line) {
                    $diff .= '- ' . $line . PHP_EOL;
                    $diffHtml .= '<div class="del">' . $line . '</div>';
                }
            }
        }

        return [$diff, $diffHtml, $diffPreview, $alternativeUrl];
    }

    private function renderDeltaVersion2($sections, $alert_mpos)
    {
        $diff = "";
        $realDiff = "";
        $diffHtml = "";
        $diffPreview = "";
        $alternativeUrl = null;

        $changedLineInfo = [];
        foreach ($sections as $section_idx => $section) {
            assert(is_array($section));
            if (array_key_exists('header', $section)) {
                $realDiff .= '@ ' . $section['header'] . '\n';
            }
            if (array_key_exists('add', $section)) {
                // We cannot use splitByNewline here, as that would break
                // keyword highlighting. Awwk!
                $lines = $section['add'];

                // External changes may contain newlines.
                $lines = is_array($lines) ? $lines : [$lines];

                $section_mpos = isset($alert_mpos[$section_idx])
                    ? $alert_mpos[$section_idx] : [];

                foreach ($lines as $idx => $line) {
                    $altUrl = $this->getAlternativeUrl($line);
                    if ($altUrl) {
                        if (!isset($alternativeUrl)) {
                            $alternativeUrl = $altUrl;
                        }
                        continue;
                    }
                    $diff .= '+ ' . $line . PHP_EOL;
                    $realDiff .= '> ' . $line . PHP_EOL;

                    list ($matching_keywords, $line)
                        = KeywordHighlighter::mark($section_mpos, $idx, $line);

                    $change = $this->substituteMarkdown($line);


                    // Do newline splitting here. This way, line and change
                    // both really are misnomers. Sorry.
                    foreach (explode("\n", $change) as $real_line) {
                        $diffHtml .= '<div class="ins">' . $real_line . '</div>';
                        $changedLineInfo[] = [
                            $section_idx,
                            $idx,
                            count($matching_keywords),
                            $real_line
                        ];
                    }

                }
            }
            if (array_key_exists('add', $section)
                && array_key_exists('del', $section)
            ) {
                // this is just to emulate the original diff output, not
                // sure if it's actually used.
                $realDiff .= "---\n";
            }
            if (array_key_exists('del', $section)) {
                $lines = $this->splitByNewline($section['del']);
                foreach ($lines as $line) {
                    $diff .= '- ' . $line . PHP_EOL;
                    $realDiff .= '< ' . $line . PHP_EOL;
                    $change = $this->substituteMarkdown($line);
                    $diffHtml .= '<div class="del">' . $change . '</div>';
                }
            }
        }

        if (count($changedLineInfo) > 5) {
            // For the preview, limit to the five *best* lines (by number of
            // keyword matches and index).
            $matchCmpFunc = function ($a, $b) {
                if ($a[2] == $b[2]) {
                    if ($a[0] == $b[0]) {
                        if ($a[1] == $b[1]) {
                            return 0;
                        } else {
                            return $a[1] < $b[1] ? -1 : 1;
                        }
                    } else {
                        return $a[0] < $b[0] ? -1 : 1;
                    }
                } else {
                    return $a[2] < $b[2] ? -1 : 1;
                }
            };
            usort($changedLineInfo, $matchCmpFunc);
            $changedLineInfo = array_slice($changedLineInfo, 0, 5);

            // Then reorder the lines according to their index, so they appear
            // in the correct order.
            sort($changedLineInfo);
        }

        foreach ($changedLineInfo as $entry) {
            $diffPreview .= '<div class="ins">' . $entry[3] . '</div>';
        }

        return [$diff, $diffHtml, $diffPreview, $alternativeUrl];
    }

    private function substituteMarkdown($line)
    {
        $sz = strlen($line);
        if ($sz > 1 && $line[0] == '#') {
            if ($sz > 2 && $line[1] == '#') {
                if ($sz > 3 && $line[2] == '#') {
                    if ($sz > 4 && $line[3] == '#') {
                        if ($sz > 5 && $line[4] == '4' && $line[5] == ' ') {
                            $line = '<div class="header5">' . substr($line, 6) . '</div>';
                        } elseif ($line[4] == ' ') {
                            $line = '<div class="header4">' . substr($line, 5) . '</div>';
                        }
                    } elseif ($line[3] == ' ') {
                        $line = '<div class="header3">' . substr($line, 4) . '</div>';
                    }
                } elseif ($line[2] == ' ') {
                    $line = '<div class="header2">' . substr($line, 3) . '</div>';
                }
            } elseif ($line[1] == ' ') {
                $line = '<div class="header1">' . substr($line, 2) . '</div>';
            }
        }
        return $line;
    }

    private function splitByNewline($input)
    {
        $input = is_array($input) ? $input : [$input];
        $result = [];
        foreach ($input as $entry) {
            foreach (explode("\n", $entry) as $line) {
                $result[] = $line;
            }
        }
        return $result;
    }

    /**
     * getDocument
     *
     * @param int $docId the doc id
     *
     * @return mixed
     * @throws KeyNotFoundException
     */
    private function getDocument($docId)
    {
        $query = 'SELECT contents ';
        $query .= ' FROM spider_document WHERE spider_document_id=';
        $query .= intval($docId);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return stream_get_contents($res['contents']);
        }
        throw new KeyNotFoundException('no doc with id ' . $docId);
    }

    /**
     * getDiff
     *
     * @param string    $contentOld the content
     * @param string    $contentNew the content
     * @param \DateTime $changeDate the change date
     * @param array     $keywords   the matched keywords
     *
     * @return array
     */
    private function getDiff($contentOld, $contentNew, $changeDate, $keywords)
    {
        $diffArr = array();
        // $oldFile = $this->saveFile(htmlspecialchars($contentOld, ENT_QUOTES, 'UTF-8'));
        // $newFile = $this->saveFile(htmlspecialchars($contentNew, ENT_QUOTES, 'UTF-8'));
        $oldFile = $this->saveFile(strip_tags($contentOld));
        $newFile = $this->saveFile(strip_tags($contentNew));
        /* This should match the diff command used by the backend */
        $diff_cmd = "diff --minimal"
            . " --ignore-space-change"
            . " --ignore-blank-lines"
            . " --ignore-trailing-space"
            . " --ignore-all-space";
        $legacyDate = new \DateTime("2015-11-06 00:00:00");
        if ($changeDate < $legacyDate) {
            $diff_cmd = "diff";
        }
        $cmd =
            "$diff_cmd $oldFile $newFile | grep '^[<>]' | sed 's/^>/+/' | sed 's/^</-/'";
        $diff = shell_exec($cmd);
        $replaceKeyword = "";
        foreach ($keywords as $keyword) {
            assert(!is_null($keyword));
            foreach (explode(' ', $keyword) as $singleKeyword) {
                assert(strlen($singleKeyword) > 0);
                $replaceKeyword .= " | sed 's/$singleKeyword/<mark>&<\/mark>/ I'";
            }
        }
        $cmd =
            //"$diff_cmd $oldFile $newFile --suppress-common-lines --ignore-all-space | grep '^[<>]' | sed 's/^>/+/' | sed 's/^</-/' |" .
            "$diff_cmd $oldFile $newFile | grep '^[<>]' | sed 's/^>/+/' | sed 's/^</-/'";
        $diffExec = shell_exec($cmd);
        file_put_contents($oldFile, $diffExec);
        $diffPreview = '';
        $arr = explode(PHP_EOL, $diffExec);
        $num = count($arr); $count = 0;
        for ($i = 0; $i < $num; ++$i) {
            if (substr($arr[$i], 0, 1) == '+') {
                $diffPreview .= '<div>' .
                    preg_replace('/^[#\*]+\s/i', '', trim(substr($arr[$i], 1))) .
                    '</div>';
                ++$count;
            }

            if ($count >= 5) break;
        }

        foreach ($keywords as $keyword) {
            assert(!is_null($keyword));
            foreach (explode(' ', $keyword) as $singleKeyword) {
                assert(strlen($singleKeyword) > 0);
                $diffPreview = preg_replace('/' . $singleKeyword . '/i', '<mark>$0</mark>', $diffPreview);
            }
        }

        $cmd = "cat " . $oldFile . " | " .
            "sed 's/#####/<div class=\"header4\">/' | sed '/header4/s/$/<\/div>/' | " .
            "sed 's/####/<div class=\"header4\">/' | sed '/header4/s/$/<\/div>/' | " .
            "sed 's/###/<div class=\"header3\">/' | sed '/header3/s/$/<\/div>/' | " .
            "sed 's/##/<div class=\"header2\">/' | sed '/header2/s/$/<\/div>/' | " .
            "sed -e 's/^+ #/+<div class=\"header1\">/' -e 's/^- #/-<div class=\"header1\">/' | sed '/header1/s/$/<\/div>/' | " .
            "sed 's/^+/<div class=\"ins\">/' | sed 's/^-/<div class=\"del\">/' | " .
            "sed  's/$/<\/div>/' | sed  '/<div class=\"ins\"><\/div>/d' | sed  '/<div class=\"del\"><\/div>/d' | " .
            "sed  '/<div class=\"del\">[\" +\"]<\/div>/d' | sed  '/<div class=\"ins\">[\" +\"]<\/div>/d'" . $replaceKeyword;

        $diffHtml = shell_exec($cmd);
        unlink($oldFile);
        unlink($newFile);
        //command line diff without saving file: does work on the console but not from here
        /*
         $cmd =
            "diff <(echo \"" . $contentOld . "\") <(echo \"" . $contentNew .
            "\") | grep '^[<>]' | sed 's/^>/+/' | sed 's/^</-/'";
        $diff = shell_exec($cmd);
        */
        $diffArr['diff'] = $diff;
        $diffArr['diffHtml'] = $diffHtml;
        $diffArr['diffPreview'] = $diffPreview;
        /*
        $diffPreview = ''; $numChars = 300;
        $string = preg_replace('/<div class="del">(.*?)<\/div>/i', '', $diffHtml);
        $arr = $matches[0];
        $num = count($arr); $first = true;
        if ($num > 5) $num = 5;
        for ($i = 0; $i < $num; ++$i) {
          $string .= $arr[$i];
        }

        $diffArr['diffPreview'] = substr($string, 0, $numChars) . $diffPreview;

        */
        return $diffArr;
    }

    /**
     * createSaltString
     *
     *
     * @return string
     */
    private function createSalt()
    {
        $salt = '';
        for ($i = 0; $i < 8; $i++) {
            $salt .= chr(rand(33, 126)); // random, printable ascii char
        }
        return $salt;
    }

    /**
     * saveFile and return unique file name
     *
     * @param string $content the content
     *
     * @return string
     */
    private function saveFile($content)
    {
        $fileName = null;
        do {
            $fileName = './tmp/' .
                hash('sha256', $content . $this->createSalt() . '.txt');

        } while (file_exists($fileName));
        $file =
            fopen($fileName, "w");
        fwrite($file, $content);
        fclose($file);
        return $fileName;
    }

    private function getAlternativeUrl($line)
    {
        if (preg_match('/\[.*\]\((?<url>.+)\)/',$line, $matches)) {
            return $matches['url'];
        } else {
            return null;
        }
    }
}
