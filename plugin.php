<?php 
/**
Plugin Name: Lightweight HTML Minify
Plugin URI: https://wordpress.org/plugins/lightweight-html-minify/
Description: Lightweight HTML Minify plugin provide feature to install and minify HTML, Means you don't have to worry about any setting it will automatically minify HTML code in single line.
Author: Girdhari choyal
Version: 1.0
Author URI: https://www.ninjatechnician.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Minify HTML

class LHM_HTML_Compression
{
    // Settings
    protected $lhm_compress_css = true;
    protected $lhm_compress_js = true;
    protected $lhm_info_comment = false;
    protected $lhm_remove_comments = true;

    // Variables
    protected $html;
    public function __construct($html)
    {
   	 if (!empty($html))
   	 {
   		 $this->lhm_parseHTML($html);
   	 }
    }
    public function __toString()
    {
   	 return $this->html;
    }
    protected function lhm_bottomComment($raw, $compressed)
    {
   	 $raw = strlen($raw);
   	 $compressed = strlen($compressed);
   	 
   	 $savings = ($raw-$compressed) / $raw * 100;
   	 
   	 $savings = round($savings, 2);
   	 
   	 return '<!--HTML compressed, size saved '.$savings.'%. From '.$raw.' bytes, now '.$compressed.' bytes-->';
    }
    protected function lhm_minifyHTML($html)
    {
   	 $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
   	 preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
   	 $overriding = false;
   	 $raw_tag = false;
   	 // Variable reused for output
   	 $html = '';
   	 foreach ($matches as $token)
   	 {
   		 $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
   		 
   		 $content = $token[0];
   		 
   		 if (is_null($tag))
   		 {
   			 if ( !empty($token['script']) )
   			 {
   				 $strip = $this->lhm_compress_js;
   			 }
   			 else if ( !empty($token['style']) )
   			 {
   				 $strip = $this->lhm_compress_css;
   			 }
   			 else if ($content == '<!--wp-html-compression no compression-->')
   			 {
   				 $overriding = !$overriding;
   				 
   				 // Don't print the comment
   				 continue;
   			 }
   			 else if ($this->lhm_remove_comments)
   			 {
   				 if (!$overriding && $raw_tag != 'textarea')
   				 {
   					 // Remove any HTML comments, except MSIE conditional comments
   					 $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
   				 }
   			 }
   		 }
   		 else
   		 {
   			 if ($tag == 'pre' || $tag == 'textarea')
   			 {
   				 $raw_tag = $tag;
   			 }
   			 else if ($tag == '/pre' || $tag == '/textarea')
   			 {
   				 $raw_tag = false;
   			 }
   			 else
   			 {
   				 if ($raw_tag || $overriding)
   				 {
   					 $strip = false;
   				 }
   				 else
   				 {
   					 $strip = true;
   					 
   					 // Remove any empty attributes, except:
   					 // action, alt, content, src
   					 $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
   					 
   					 // Remove any space before the end of self-closing XHTML tags
   					 // JavaScript excluded
   					 $content = str_replace(' />', '/>', $content);
   				 }
   			 }
   		 }
   		 
   		 if ($strip)
   		 {
   			 $content = $this->lhm_removeWhiteSpace($content);
   		 }
   		 
   		 $html .= $content;
   	 }
   	 
   	 return $html;
    }
   	 
    public function lhm_parseHTML($html)
    {
   	 $this->html = $this->lhm_minifyHTML($html);
   	 
   	 if ($this->lhm_info_comment)
   	 {
   		 $this->html .= "\n" . $this->lhm_bottomComment($html, $this->html);
   	 }
    }
    
    protected function lhm_removeWhiteSpace($str)
    {
   	 $str = str_replace("\t", ' ', $str);
   	 $str = str_replace("\n",  '', $str);
   	 $str = str_replace("\r",  '', $str);
   	 
   	 while (stristr($str, '  '))
   	 {
   		 $str = str_replace('  ', ' ', $str);
   	 }
   	 
   	 return $str;
    }
}

function lhm_wp_html_compression_finish($html)
{
	$html = new LHM_HTML_Compression($html);
    return lhm_remove_html_comments($html);
}

function lhm_remove_html_comments($content = '') {
	return preg_replace('/<!--(.|\s)*?-->/', '', $content);
}

function lhm_wp_html_compression_start()
{
    ob_start('lhm_wp_html_compression_finish');
}
add_action('get_header', 'lhm_wp_html_compression_start');