<?php

namespace Datahouse\MON\Common;

class KeywordHighlighter
{
    /**
     * @param array  $section_mpos as read from the database
     * @param int    $idx          index into the array of lines
     * @param string $line         current line at the above index
     * @return array
     */
    public static function mark(&$section_mpos, $idx, $line)
    {
        $matching_keywords = [];
        if ($section_mpos) {
            foreach ($section_mpos as $keyword => $mpos) {
                if (array_key_exists($idx, $mpos)) {
                    foreach ($mpos[$idx] as $pos) {
                        $matching_keywords[] = [$pos, $keyword];
                    }
                }
            }

            // Collect the amount of opening and closing markers required
            // per index in the line.
            $posOpenClose = [];
            foreach ($matching_keywords as list($startPos, $keyword)) {
                $endPos = $startPos + strlen($keyword);

                // Ensure both required positions in the line requiring a
                // start or end tag exist in $posOpenClose.
                if (!array_key_exists($startPos, $posOpenClose)) {
                    $posOpenClose[$startPos] = 0;
                }
                if (!array_key_exists($endPos, $posOpenClose)) {
                    $posOpenClose[$endPos] = 0;
                }

                // Account for the number of open and close markers for the
                // position in the line for this keyword.
                $posOpenClose[$startPos] += 1;
                $posOpenClose[$endPos] -= 1;
            }

            // Loop over the positions, emitting marks
            $positions = array_keys($posOpenClose);
            sort($positions);
            $aggMarkCount = 0;
            $idx = 0;
            $result = "";
            foreach ($positions as $pos) {
                $count = $posOpenClose[$pos];
                if ($aggMarkCount == 0 && $count > 0) {
                    // copy everything from $idx to $pos
                    $result .= mb_substr($line, $idx, $pos - $idx);
                    $idx = $pos;
                    // emit an opening mark
                    $result .= '<mark>';
                } elseif ($aggMarkCount > 0 && ($aggMarkCount + $count) < 1) {
                    // copy everything from $idx to $pos
                    $result .= mb_substr($line, $idx, $pos - $idx);
                    $idx = $pos;
                    // emit a closing mark
                    $result .= '</mark>';
                }

                // keep track of the total mark opening vs closing count
                $aggMarkCount += $count;
                assert($aggMarkCount >= 0);
            }

            // copy all of the remainder after the last closing mark
            $result .= mb_substr($line, $idx);
        } else {
            $result = $line;
        }
        return [$matching_keywords, $result];
    }
}
