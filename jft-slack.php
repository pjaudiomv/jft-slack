<?php

class Jft
{

    public function sendJftToSlack($token, $channel)
    {
        $data = $this->get_jft();
        return $this->generateSlackHook($token, $channel, $data);
    }

    public function get_jft()
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
        $k = 1;

        $jftArray = [];

        foreach ($d->getElementsByTagName('tr') as $element) {
            if ($i != 5) {
                $formated_element = trim($element->nodeValue);
                $jftArray[$jftKeys[$i]] = $formated_element;
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
                $break_array = preg_replace('/<br[^>]*>/i', ' ', $values[5]);
                $jftArray["content"] = $break_array[0];
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
                    "value" => $jft["source"] . "\n\n\n" . strip_tags($jft["content"]) . "\n\n\n" . $jft["thought"],
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
$sendJft->sendJftToSlack('GHSEJFNLWEASFDwefdsbhjkMv7xB9cTUMiD6QDytel', 'just-for-today');
