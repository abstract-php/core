<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function html_to_object($html) {

  // List of known self-closing tags
  $self_closing_tags = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr');

  // $html = preg_replace('/>(?:(.*?))</s', '><text>$1</text><', $html);
  $html = preg_replace('/<!--(?:(.*?))-->/s', '<comment>$1</comment>', $html);

  // echo $html;

  $tokens = array_map(
    function ($token) {
      return (object) [
        'complete' => false,
        'value' => $token,
      ];
    },
    preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)
  );


  return parse_tokens($tokens);
}

function parse_tokens($tokens, $closed = false) {
  $result = new stdClass();
  $stack = array();
  $current = &$result;

  $parsedHTML = array_reduce(
    $tokens,
    function ($tokensAsset, $token) use (&$result, &$stack, &$current) {
      if (!$token->complete) {
        // $token->complete = true;
        $value = trim($token->value);
        preg_match_all(
          '/<(\/?)([\w\p{L}\-\:\.]*)(?:\s+([^>]*))?(\/)?>/u',
          $value,
          $matches,
          PREG_SET_ORDER
        );
        $isClosingTag = isset($matches[0]) && !empty($matches[0][1]);
        if ($isClosingTag) {
          if ($tokensAsset->current !== null) {
            $tokensAsset->current['closed'] = true;
            $tokensAsset->objects[] = $tokensAsset->current;
            $tokensAsset->current = null;
          }
        } else {
          $object = isset($matches[0])
            ? [
              'name' => $matches[0][2],
              'closed' => false,
              'attributes' => isset($matches[0]) && isset($matches[0][3]) ? $matches[0][3] : null,
              'value' => null,
              'children' => [],
            ]
            : [
              'name' => 'text',
              'closed' => true,
              'attributes' => null,
              'value' => $value,
              'children' => [],
            ];
          
          if (isset($matches[0])) {
            $object = [
              'name' => $matches[0][2],
              'closed' => false,
              'attributes' => isset($matches[0]) && isset($matches[0][3]) ? $matches[0][3] : null,
              'value' => null,
              'children' => [],
            ];
            // $tokensAsset->objects[] = $tokensAsset->current;
            // $tokensAsset->current = $object;
            // if ($tokensAsset->current !== null) {
            //   $tokensAsset->current['children'][] = $object;
            // }
            // var_dump($object);
            // $tokensAsset->current = $object;
          } else {
            if (!empty($value)) {
              $object = [
                'name' => 'text',
                'closed' => true,
                'attributes' => null,
                'value' => $value,
                'children' => [],
              ];
              // if ($tokensAsset->current !== null) {
              //   $tokensAsset->current['children'][] = $object;
              // } else {
              //   $tokensAsset->objects[] = $object;
              // }
            }
          }
        }
      }
      return $tokensAsset;
    },
    (object) array(
      'current' => null,
      'objects' => []
    )
  )->objects;

  // echo implode('', $parsedHTML);

  return $parsedHTML;
}

// ... rest of the code remains unchanged ...

$html = '<html 
data-a11y-animated-images="system" 
data-a11y-link-underlines="true"
>
I know
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
  <:string>999</:string>
  <:string>101010</:string>
  123456
  <:string>101010</:string>
  <:int>900</:int>
  <:string>789</:string>
  <:string>GOF</:string>
  555
  <xsl:if>GOF</xsl:if>
</echo>
</html>';
$obj = html_to_object($html);

var_dump($obj);
