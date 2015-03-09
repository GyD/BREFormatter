<?php

namespace GyD\BREFormatter;

/**
 * Class to format URLs from text into bootstrap friendly embed code
 *
 * Class BootstrapResponsiveEmbedFormatter
 */
class BREF
{

    /**
     * Text to be parced
     * @var string
     */
    private $text = '';

    /**
     * Contain already parsed urls
     *
     * @var array
     */
    private $cache = array();

    /**
     * Format text
     *
     * @param $text
     * @return string
     */
    public function format($text)
    {
        $this->text = $text;
        foreach ($this->parseURLs(true) as $url) {
            foreach ($this->getRules() as $pattern => $mediaName) {
                $matches = array();

                if (preg_match($pattern, $url, $matches)) {
                    $method = 'parse' . ucfirst($mediaName);
                    $this->{$method}($matches, $url);
                    continue;
                }
            }
        }

        return $this->text;
    }

    /**
     * Parse URLs from text
     *
     * @param bool $reset
     * @return array
     */
    private function parseURLs($reset = false)
    {
        static $urls;

        if (null == $urls || $reset === true) {
            $urls = $matches = array();
            preg_match_all("`(?:(</?)([!a-z]+))|(/?\s*>)|((?:https?|ftp)://[\@a-z0-9\x21\x23-\x27\x2a-\x2e\x3a\x3b\/;\x3f-\x7a\x7e\x3d]+)`i", $this->text, $matches);
            if (array_key_exists(4, $matches)) {
                $urls = $matches[4];
            }
        }

        return $urls;
    }

    /**
     * Get rules and the method used
     *
     * @return array
     */
    private function getRules()
    {
        return array(
          '/(?:https?|ftp):\/\/(?:player.|www.)?(?:youtu(?:be\.com|\.be|be\.googleapis\.com))\/(?:video\/|embed\/|watch\?v=|v\/)?(?P<ID>[A-Za-z0-9._%-]*)(?P<HasTime>[#|?]t=(?P<Time>[0-9a-z]+))?/' => 'youtube',
          '/(?:https?|ftp):\/\/(?:player.|www.)?(?:vimeo.com)\/(?:video\/|embed\/|watch\?v=|v\/)?(?P<ID>[A-Za-z0-9._%-]*)/' => 'vimeo',
          '/https?:\/\/(:?www)?soundcloud.com.*/' => 'soundcloud',
        );
    }

    /**
     * Parse Soundcloud urls into iframe
     *
     * @param $matches
     * @param $url
     */
    private function parseSoundcloud($matches, $url)
    {
        if (array_key_exists($url, $this->cache)) {
            return $this->replace($url, $this->cache[$url]);
        }
        $json = file_get_contents('http://soundcloud.com/oembed?format=json&url=' . urlencode($url) . '&iframe=true');
        if (!empty($json)) {
            if ($json = json_decode($json)) {
                $soundcloudFrame = $json->html;
                $matches = array();
                preg_match('<iframe.+src="(?P<url>[^"]+)".*>', $soundcloudFrame, $matches);
                if (!empty($matches['url'])) {
                    $soundcloudFrame = $this->generateEmbedItem('iframe', array(
                      'src' => $matches['url'],
                      'frameborder' => '0',
                      'allowfullscreen' => '1',
                    ));
                }

                $this->cache[$url] = $soundcloudFrame;
                $this->replace($url, $soundcloudFrame);

            }
        }
    }

    /**
     * Parse Vimeo Vidéos into <iframe />
     * @param $matches
     * @param $url
     */
    private function parseVimeo($matches, $url)
    {
        $this->replace($url, $this->generateEmbedItem('iframe', array(
          'src' => 'https://player.vimeo.com/video/' . $matches['ID'],
          'frameborder' => '0',
          'allowfullscreen' => '1',
        )));
    }

    /**
     * Youtube parse function
     *
     * @param $matches
     * @param $url
     */
    private function parseYoutube($matches, $url)
    {
        $src = 'https://www.youtube.com/embed/' . $matches['ID'];
        if (!empty($matches['HasTime'])) {
            preg_match('/(?:(?P<Hours>[0-9]+)h)*(?:(?P<Minutes>[0-9]+)m)*(?P<Seconds>[0-9]+s?)*/g', $matches['Time'], $time_matches);

            $time = array('Hours' => 0, 'Minutes' => 0, 'Seconds' => 0);
            foreach ($time_matches as $t => $i) {
                $time[$t] += $i;
            }


            $src .= '?start=' . (($time['Hours'] * 3600) + ($time['Minutes'] * 60) + $time['Seconds']);
        }
        $this->replace($url, $this->generateEmbedItem('iframe', array(
          'src' => $src,
          'frameborder' => "0",
          'allowfullscreen' => '1'
        )));
    }

    /**
     * Simple replace function
     *
     * @param string $url
     * @param string $replace
     * @param string $format
     */
    private function replace($url, $replace, $format = '16by9')
    {
        $container = $this->generateHtmlTag('div', array(
          'class' => array(
            'embed-responsive',
            'embed-responsive-' . $format
          ),
        ),
          $replace
        );
        $this->text = str_ireplace($url, $container, $this->text);
    }

    /**
     * Generate an Html Tag
     *
     * @param $tagName
     * @param array $attributes
     * @param null $content
     * @return string
     */
    private function generateHtmlTag($tagName, $attributes = array(), $content = null)
    {
        return '<' . $tagName . $this->generateAttributes($attributes) . '>' . $content . '</' . $tagName . '>';
    }

    /**
     * Generate Html Tag Attributes
     *
     * @param array $attributes
     * @return string
     */
    private function generateAttributes(array $attributes = array())
    {
        foreach ($attributes as $attribute => &$data) {
            $data = implode(' ', (array) $data);
            $data = $attribute . '="' . htmlspecialchars($data, ENT_QUOTES, 'UTF-8') . '"';
        }

        return $attributes ? ' ' . implode(' ', $attributes) : '';
    }

    /**
     * Generate the embed item
     *
     * @param $tagName
     * @param array $attributes
     * @param null $content
     * @return string
     */
    private function generateEmbedItem($tagName, $attributes = array(), $content = null)
    {
        if (!array_key_exists('class', $attributes)) {
            $attributes['class'] = array();
        }
        $attributes['class'][] = 'embed-responsive-item';

        return $this->generateHtmlTag($tagName, $attributes, $content);
    }

    /**
     * @param array $cache
     */
    public function setCache(array $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return array
     */
    public function getCache(){
        return $this->cache;
    }

}