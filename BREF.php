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
          '`(?:https?|ftp)://(www\.)?youtube\.com\/watch\?(.*)?v=(?P<ID>[^&#]+)([^#]*)(?P<HasTime>#t=(?P<Time>[0-9]+))?`' => 'youtube',
          '`(?:https?)://(www\.)?youtu\.be\/(?P<ID>[^&#]+)(?P<HasTime>#t=(?P<Time>[0-9]+))?`' => 'youtube',
        );
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
            $src .= '?start=' . $matches['HasTime'];
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
     * @param $url
     * @param $replace
     */
    private function replace($url, $replace)
    {
        $container = $this->generateHtmlTag('div', array(
          'class' => array(
            'embed-responsive',
            'embed-responsive-16by9'
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

}