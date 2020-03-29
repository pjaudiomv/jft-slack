<?php
/*
 * JFT Slack
 * Sends the Just For Today to a slack channel.
 */

class Jft
{

    public function sendJftToSlack($token, $channel)
    {
        $data = $this->getJft();
        return $this->generateSlackHook($token, $channel, $data);
    }

    public function getJft()
    {
        $jft_url = 'https://jftna.org/jft/';
        libxml_use_internal_errors(true);
        $url = $this->get($jft_url);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $d = new DOMDocument();
        $d->validateOnParse = true;
        $d->loadHTML($url);

        $jftKeys = array('date', 'title', 'page', 'quote', 'source', 'content', 'thought', 'copyright');
        
        $i = 0;
        $jftArray = [];

        foreach ($d->getElementsByTagName('tr') as $element) {
            if ($i != 5) {
                $formatedElement = trim($element->nodeValue);
                $jftArray[$jftKeys[$i]] = $formatedElement;
            } else {
                $dom = new DOMDocument();
                $dom->loadHTML($this->get($jft_url));
                $values = array();
                $xpath = new DOMXPath($dom);
                foreach ($xpath->query('//tr') as $row) {
                    $row_values = array();
                    foreach ($xpath->query('td', $row) as $cell) {
                        $innerHTML = '';
                        $children = $cell->childNodes;
                        foreach ($children as $child) {
                            $innerHTML .= $child->ownerDocument->saveXML($child);
                        }
                        $row_values[] = $innerHTML;
                    }
                    $values[] = $row_values;
                }
                $breakArray = preg_replace('/<br[^>]*>/i', ' ', $values[5]);
                $jftArray["content"] = strip_tags($breakArray[0]);
            }
            $i++;
        }
        return $jftArray;
    }


    public function generateSlackHook($token, $channel, $jft)
    {
        $attachments = array([
            "fallback" => "Just For Today",
            "color" => "#9ec6d8",
            "title" => $jft["date"],
            'fields' => array(
                [
                    "title" => $jft["title"],
                    "value" => $jft["page"],
                    "short" => true
                ],
                [
                    "title" => $jft["quote"],
                    "value" => $jft["source"] . "\n\n\n" . $jft["content"] . "\n\n\n" . $jft["thought"],
                    "short" => false
                ]
            ),
            "footer" => "JFT",
            "footer_icon" => "https://www.mvana.org/wp-content/uploads/2020/03/jft-icon.png",
            "ts" => time()
        ]);
        $data = array(
            'channel' => $channel,
            'username' => 'jft-bot',
            'text' => '',
            'icon_emoji'  => ':jft:',
            'attachments' => $attachments
        );

        $data_string = json_encode($data);
        $ch = curl_init("https://hooks.slack.com/services/$token");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)));
        return curl_exec($ch);
    }


    public function get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $errorno = curl_errno($ch);
        curl_close($ch);
        if ($errorno > 0) {
            throw new Exception(curl_strerror($errorno));
        }
        return $data;
    }
}

$sendJft = new Jft;
$sendJft->sendJftToSlack('GHSEJFNLWEASFDwefdsbhjkMv7xB9cTjkfnsvJKFWD', 'just-for-today'); # MVANA
$sendJft->sendJftToSlack('JHGVJDShjbdSFDwefdsbhjkhkfbsvRJFSJNEADalqp', 'just-for-today'); # CRNA
$sendJft->sendJftToSlack('EROSFVJNSDJNDDwefdsbhjkMvwefWJFNJNWFNJAEqw', 'just-for-today'); # BMLT
/*
 * This takes two arguments first a slack incoming webhook api token and second the channel to which we want to send to
 * you can send to as many workspaces and channels as you want.
 */
