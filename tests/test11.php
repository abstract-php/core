<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function html_to_object($html) {
  $result = new stdClass();
  $stack = array();
  $current = &$result;

  // List of known self-closing tags
  $self_closing_tags = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr');

  // Match all tags, including those with colons and non-Latin characters
  preg_match_all('/>(?:(.*?))</s', $html, $matches, PREG_SET_ORDER);
  var_dump($matches);

  $html = preg_replace('/>(?:(.*?))</s', '><text>$1</text><', $html);
  $html = preg_replace('/<!--(?:(.*?))-->/s', '<comment>$1</comment>', $html);
  echo $html;

  // foreach ($matches as $match) {
  //   $tag = $match[1];
  //   $attributes = isset($match[2]) ? $match[2] : '';
  //   $content = isset($match[3]) ? $match[3] : '';

  //   // Parse attributes, including those with colons
  //   $attrs = array();
  //   if ($attributes) {
  //     preg_match_all('/(\w[\w\p{L}\-\:\.]*+)\s*=\s*(["\'])((?:(?!\2).)*)\2/u', $attributes, $attr_matches, PREG_SET_ORDER);
  //     foreach ($attr_matches as $attr_match) {
  //       $attrs[$attr_match[1]] = $attr_match[3];
  //     }
  //   }

  //   // Create object for this tag
  //   $obj = new stdClass();
  //   $obj->tag = $tag;
  //   $obj->attributes = $attrs;

  //   // Recursively process nested content
  //   if (trim($content) !== '') {
  //     if (strpos($content, '<') !== false) {
  //       $obj->children = html_to_object($content);
  //     } else {
  //       $obj->content = $content;
  //     }
  //   }

  //   // Add to result
  //   if (!isset($result->$tag)) {
  //     $result->$tag = $obj;
  //   } else {
  //     if (!is_array($result->$tag)) {
  //       $result->$tag = array($result->$tag);
  //     }
  //     $result->$tag[] = $obj;
  //   }
  // }

  return $result;
}

$html = '<html 
data-a11y-animated-images="system" 
data-a11y-link-underlines="true"
>
I know
<link crossorigin="anonymous" media="all" rel="stylesheet" href="https://github.githubassets.com/assets/code-5fa42525dfce.css" />
<independence />
<echo test.okay.:string.:int="1" helo="555" x class="text-center hold">
  9999999
  656565
  <hello>123</hello>
  <test hell="555">
    <okay>123</okay>
  </test>
  <Timothée></Timothée>
  <x:กรรม>ทดสอบ</x:กรรม>
  ทดสอบ555
  <hj ทดสอบ="20">123</hj>
  <:string>456</:string>
  <:string>6666</:string>
  <AllInOne>233</AllInOne>
  <ec><ab><act><act><act>898989</act></act></act></ab></ec>
  <!-- 9000 -->
  <!-- ABS -->
  <:string>999</:string>
  <:string>101010</:string>
  123456
  <:string>101010</:string>
  <:int>900</:int>
  <:string>789</:string>
  <!-- <:string>XXXXXX</:string> -->
  <br />
  <!-- tets -->
  <:string>GOF</:string>
  555
  <xsl:if>GOF</xsl:if>
</echo>
</html>';
$obj = html_to_object($html);

print_r($obj);
