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

  $html = preg_replace('/>(?:(.*?))</s', '><text>$1</text><', $html);
  $html = preg_replace('/<!--(?:(.*?))-->/s', '<comment>$1</comment>', $html);

  echo $html;

  // Match all tags, including those with colons and non-Latin characters
  preg_match_all('/<(\/?)(\w[\w\p{L}\-\:\.]*)(?:\s+([^>]*))?(\/)?>/u', $html, $matches, PREG_SET_ORDER);

  $index = 0;
  foreach ($matches as $match) {
    $is_closing_tag = ($match[1] === '/');
    $tag = $match[2];
    $text = isset($text_parts[$index + 1]) ? trim($text_parts[$index + 1]) : '';
    $attributes = isset($match[3]) ? $match[3] : '';
    $is_self_closing = (isset($match[3]) && strlen($match[3]) && $match[3][strlen($match[3]) - 1] === '/')
      || in_array(strtolower($tag), $self_closing_tags);
    if ($tag !== 'text' && $tag !== 'comment') {
      if (!$is_closing_tag) {
        var_dump($match);
        // var_dump($tag . ' value is "' . $text . '"');
        // var_dump($tag . ' is ' . ($is_self_closing ? 'self-closing' : 'not self-closing'));
        // Opening tag
        $obj = new stdClass();
        $obj->tag = $tag;
        $obj->attributes = parse_attributes($attributes);
        if (!$is_self_closing) {
          $obj->children = new stdClass();
        }

        if (!isset($current->$tag)) {
          $current->$tag = $obj;
        } else {
          if (!is_array($current->$tag)) {
            $current->$tag = array($current->$tag);
          }
          $current->$tag[] = $obj;
        }

        if (!$is_self_closing) {
          array_push($stack, array(&$current, $tag));
          $current = &$obj->children;
        }
      } else {
        // Closing tag
        if (!empty($stack)) {
          list($parent, $last_tag) = array_pop($stack);
          if ($last_tag === $tag) {
            $current = &$parent;
          } else {
            // Mismatched closing tag, add it back to the stack
            array_push($stack, array($parent, $last_tag));
          }
        }
      }
    }

    $index += 1;
  }
  // var_dump($text_parts);

  // Process text content
  // $text_parts = preg_split('/<[^>]+>/', $html);
  // foreach ($text_parts as $index => $text) {
  //   $text = trim($text);
  //   if ($text !== '') {
  //     $key = '_text_' . $index;
  //     $current->$key = $text;
  //   }
  // }

  return $result;
}

// ... rest of the code remains unchanged ...

function parse_attributes($attributes_string) {
  $attrs = array();
  if ($attributes_string) {
    preg_match_all('/(\w[\w\p{L}\-\:\.]*+)\s*=\s*(["\'])((?:(?!\2).)*)\2/u', $attributes_string, $attr_matches, PREG_SET_ORDER);
    foreach ($attr_matches as $attr_match) {
      $attrs[$attr_match[1]] = $attr_match[3];
    }
  }
  return $attrs;
}

// ... rest of the code remains unchanged ...

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

var_dump($obj);
